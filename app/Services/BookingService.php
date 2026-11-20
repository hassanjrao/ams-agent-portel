<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PassengerType;
use App\Models\Booking;
use App\Models\BookingPassenger;
use App\Models\FlightGroup;
use App\Models\User;
use App\Notifications\BookingCancelled;
use App\Notifications\BookingConfirmed;
use App\Notifications\PassengersChanged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Holds, confirms, cancels and expires bookings. Every change to how many seats a group has left
 * goes through here, under a row lock on the group, so two agents can never take the same seat.
 */
class BookingService
{
    public function __construct(private LedgerService $ledger) {}

    /**
     * Reserve seats for an agent's agency until the group's hold time runs out.
     *
     * @param  list<array<string, mixed>>  $passengers  optional names, one per passenger, matching the counts
     *
     * @throws ValidationException when the agency can't book, the group isn't bookable, or too few seats are left
     */
    public function hold(User $agent, FlightGroup $group, int $adults, int $children = 0, int $infants = 0, ?string $remarks = null, array $passengers = []): Booking
    {
        $agency = $agent->agency;
        if (! $agency || ! $agency->isActive()) {
            throw ValidationException::withMessages(['agency' => 'Your agency is not approved for booking yet.']);
        }
        if (! FreePlan::covers($agency)) {
            throw ValidationException::withMessages(['agency' => FreePlan::lockedMessage()]);
        }
        if ($adults < 1) {
            throw ValidationException::withMessages(['adults' => 'A booking needs at least one adult.']);
        }
        if ($infants > $adults) {
            throw ValidationException::withMessages(['infants' => 'Each infant must travel with an adult.']);
        }
        $passengers = array_values($passengers);
        if ($passengers) {
            self::assertPassengersMatch($passengers, $adults, $children, $infants);
        }

        $seats = self::seatsFor($adults, $children, $infants);

        return DB::transaction(function () use ($agent, $group, $adults, $children, $infants, $remarks, $seats, $passengers) {
            // Lock the group so concurrent bookings for it queue up behind this one.
            $group = FlightGroup::query()->whereKey($group->id)->lockForUpdate()->firstOrFail();

            if (! $group->isBookable()) {
                throw ValidationException::withMessages(['group' => 'This group is no longer open for booking.']);
            }
            if ($infants > 0 && ! $group->takesInfants()) {
                throw ValidationException::withMessages(['infants' => "This group doesn't take infants."]);
            }
            $available = $group->seatsAvailable();
            if ($seats > $available) {
                throw ValidationException::withMessages(['adults' => $available === 0
                    ? 'This group is sold out.'
                    : "Only {$available} seats are left in this group."]);
            }

            $booking = new Booking;
            $booking->reference = 'TMP-'.bin2hex(random_bytes(6));
            $booking->agency_id = $agent->agency_id;
            $booking->user_id = $agent->id;
            $booking->flight_group_id = $group->id;
            $booking->adults = $adults;
            $booking->children = $children;
            $booking->infants = $infants;
            $booking->seats = $seats;
            // Price is always the group's, read here on the server: never taken from the request.
            $booking->adult_fare = $group->adult_fare;
            $booking->child_fare = $group->childFare();
            $booking->infant_fare = $group->infant_fare ?? 0;
            $gross = $adults * $group->adult_fare + $children * $booking->child_fare + $infants * $booking->infant_fare;
            // The group's flat discount comes off the booking, never below zero; the booking keeps it.
            $booking->discount_amount = min((int) $group->discount_per_booking, $gross);
            $booking->total_amount = $gross - $booking->discount_amount;
            $booking->status = BookingStatus::OnHold;
            $booking->hold_expires_at = now()->addMinutes($group->holdMinutes());
            $booking->agent_remarks = $remarks;
            $booking->save();

            $booking->reference = ReferenceNumber::make(config('portal.reference_prefix'), $booking->id);
            $booking->save();

            $this->writePassengers($booking, $passengers, withTickets: false);

            return $booking;
        });
    }

    /**
     * Replace a booking's passenger list. An agency can change names only while its hold is running;
     * AMS can change them (and add PNR and ticket numbers) on any booking that still holds seats.
     *
     * @param  list<array<string, mixed>>  $passengers
     */
    public function updatePassengers(Booking $booking, array $passengers, User $by, ?string $pnr = null): Booking
    {
        return DB::transaction(function () use ($booking, $passengers, $by, $pnr) {
            $booking = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if ($by->isAdmin()) {
                if (! in_array($booking->status, [BookingStatus::OnHold, BookingStatus::Confirmed], true)) {
                    throw ValidationException::withMessages(['passengers' => "Passengers can't be changed on a {$booking->status->label()} booking."]);
                }
            } elseif (! $booking->isHoldActive() && $booking->status !== BookingStatus::Confirmed) {
                throw ValidationException::withMessages(['passengers' => "Names can't be changed on a {$booking->status->label()} booking."]);
            }

            $passengers = array_values($passengers);
            self::assertPassengersMatch($passengers, $booking->adults, $booking->children, $booking->infants);

            // An agency can't set ticket numbers, so its edit keeps the ones AMS entered, in order.
            $tickets = $by->isAdmin() ? [] : $booking->passengers()->orderBy('id')->get()
                ->groupBy(fn (BookingPassenger $p) => $p->type->value)
                ->map(fn ($group) => $group->pluck('ticket_number')->all())
                ->all();

            $before = $this->nameList($booking);
            $booking->passengers()->delete();
            $this->writePassengers($booking, $passengers, withTickets: $by->isAdmin(), keepTickets: $tickets);

            // Names changed after confirmation may not match tickets already issued: tell AMS.
            if (! $by->isAdmin() && $booking->status === BookingStatus::Confirmed && $before !== $this->nameList($booking)) {
                $booking->load(['agency', 'flightGroup']);
                Notification::send(User::query()->admins()->get(), new PassengersChanged($booking));
            }

            if ($by->isAdmin() && $pnr !== null) {
                $booking->pnr = $pnr ?: null;
                $booking->save();
            }

            return $booking->load('passengers');
        });
    }

    /**
     * AMS confirms the seats (normally once payment is in) and the booking is charged to the agency.
     * A hold that has already run out can still be confirmed if its seats are still free.
     */
    public function confirm(Booking $booking, User $admin, ?string $pnr = null): Booking
    {
        $booking = DB::transaction(function () use ($booking, $admin, $pnr) {
            FlightGroup::query()->whereKey($booking->flight_group_id)->lockForUpdate()->firstOrFail();
            $booking = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if (! in_array($booking->status, [BookingStatus::OnHold, BookingStatus::Expired], true)) {
                throw ValidationException::withMessages(['status' => "A {$booking->status->label()} booking can't be confirmed."]);
            }
            if (! $booking->isHoldActive()) {
                // Its seats went back to the block when the hold ran out: they must still be free.
                $available = $booking->flightGroup->seatsAvailable();
                if ($booking->seats > $available) {
                    throw ValidationException::withMessages(['status' => "The hold expired and only {$available} seats are left now."]);
                }
            }

            $booking->status = BookingStatus::Confirmed;
            $booking->confirmed_at = now();
            $booking->confirmed_by = $admin->id;
            $booking->hold_expires_at = null;
            $booking->pnr = $pnr ?: $booking->pnr;
            $booking->save();

            $this->ledger->chargeBooking($booking, $admin);

            return $booking;
        });

        Notification::send($booking->agency->users, new BookingConfirmed($booking));

        return $booking;
    }

    /**
     * Cancel a booking and free its seats. A confirmed booking is refunded in full on the ledger;
     * any airline penalty is charged separately as an adjustment.
     */
    public function cancel(Booking $booking, User $by, ?string $reason = null): Booking
    {
        $wasConfirmed = false;

        $booking = DB::transaction(function () use ($booking, $by, $reason, &$wasConfirmed) {
            $booking = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if (! in_array($booking->status, [BookingStatus::OnHold, BookingStatus::Confirmed], true)) {
                throw ValidationException::withMessages(['status' => "A {$booking->status->label()} booking can't be cancelled."]);
            }
            if ($booking->status === BookingStatus::Confirmed && ! $by->isAdmin()) {
                throw ValidationException::withMessages(['status' => 'Please contact AMS to cancel a confirmed booking.']);
            }

            $wasConfirmed = $booking->status === BookingStatus::Confirmed;
            $booking->status = BookingStatus::Cancelled;
            $booking->cancelled_at = now();
            $booking->cancelled_by = $by->id;
            $booking->cancel_reason = $reason;
            $booking->hold_expires_at = null;
            $booking->save();

            if ($wasConfirmed) {
                $this->ledger->refundBooking($booking, $by);
            }

            return $booking;
        });

        // An agency cancelling its own hold already knows; tell it only when AMS did it.
        if ($by->isAdmin()) {
            Notification::send($booking->agency->users, new BookingCancelled($booking, $wasConfirmed));
        }

        return $booking;
    }

    /**
     * Mark holds that ran out as expired. Seat counts already ignore them; this keeps statuses honest.
     *
     * @return int how many bookings expired
     */
    public function expireHolds(): int
    {
        return Booking::query()
            ->where('status', BookingStatus::OnHold)
            ->where('hold_expires_at', '<=', now())
            ->update(['status' => BookingStatus::Expired, 'updated_at' => now()]);
    }

    public static function seatsFor(int $adults, int $children, int $infants): int
    {
        return $adults + $children + (config('portal.infants_take_seat') ? $infants : 0);
    }

    /**
     * One name per passenger booked: as many adult, child and infant rows as the booking has.
     *
     * @param  list<array<string, mixed>>  $passengers
     */
    private static function assertPassengersMatch(array $passengers, int $adults, int $children, int $infants): void
    {
        $counts = array_count_values(array_map(fn (array $p) => (string) ($p['type'] ?? ''), $passengers));

        // Names are optional, so fewer than the booking's passengers is fine; more is not.
        foreach ([PassengerType::Adult->value => $adults, PassengerType::Child->value => $children, PassengerType::Infant->value => $infants] as $type => $expected) {
            if (($counts[$type] ?? 0) > $expected) {
                throw ValidationException::withMessages(['passengers' => "This booking has {$expected} {$type} ".($expected === 1 ? 'place' : 'places').'.']);
            }
        }
    }

    /**
     * Who is on the booking, for spotting whether an edit changed anything.
     *
     * @return list<string>
     */
    private function nameList(Booking $booking): array
    {
        return $booking->passengers()->orderBy('id')->get()
            ->map(fn (BookingPassenger $p) => "{$p->type->value}|{$p->title}|{$p->given_name}|{$p->surname}")
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $passengers
     * @param  array<string, list<string|null>>  $keepTickets  ticket numbers to carry over, by type and position
     */
    private function writePassengers(Booking $booking, array $passengers, bool $withTickets, array $keepTickets = []): void
    {
        // Adults first, then children, then infants: the order airlines list them on a PNR.
        $order = [PassengerType::Adult->value => 0, PassengerType::Child->value => 1, PassengerType::Infant->value => 2];
        usort($passengers, fn (array $a, array $b) => $order[$a['type']] <=> $order[$b['type']]);

        $seen = [];
        foreach ($passengers as $p) {
            $position = $seen[$p['type']] = ($seen[$p['type']] ?? -1) + 1;
            $passenger = $booking->passengers()->make([
                'type' => $p['type'],
                'title' => $p['title'],
                'given_name' => mb_strtoupper(trim($p['given_name'])),
                'surname' => mb_strtoupper(trim($p['surname'])),
                'passport_number' => isset($p['passport_number']) && $p['passport_number'] !== '' ? mb_strtoupper(trim($p['passport_number'])) : null,
                'nationality' => $p['nationality'] ?? null ?: null,
                'date_of_birth' => $p['date_of_birth'] ?? null ?: null,
                'passport_expiry' => $p['passport_expiry'] ?? null ?: null,
            ]);
            if ($withTickets) {
                $passenger->ticket_number = $p['ticket_number'] ?? null ?: null;
            } else {
                $passenger->ticket_number = $keepTickets[$p['type']][$position] ?? null;
            }
            $passenger->save();
        }
    }
}
