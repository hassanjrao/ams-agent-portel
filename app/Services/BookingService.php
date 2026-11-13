<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\FlightGroup;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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
     * @throws ValidationException when the agency can't book, the group isn't bookable, or too few seats are left
     */
    public function hold(User $agent, FlightGroup $group, int $adults, int $children = 0, int $infants = 0, ?string $remarks = null): Booking
    {
        $agency = $agent->agency;
        if (! $agency || ! $agency->isActive()) {
            throw ValidationException::withMessages(['agency' => 'Your agency is not approved for booking yet.']);
        }
        if ($adults < 1) {
            throw ValidationException::withMessages(['adults' => 'A booking needs at least one adult.']);
        }
        if ($infants > $adults) {
            throw ValidationException::withMessages(['infants' => 'Each infant must travel with an adult.']);
        }

        $seats = self::seatsFor($adults, $children, $infants);

        return DB::transaction(function () use ($agent, $group, $adults, $children, $infants, $remarks, $seats) {
            // Lock the group so concurrent bookings for it queue up behind this one.
            $group = FlightGroup::query()->whereKey($group->id)->lockForUpdate()->firstOrFail();

            if (! $group->isBookable()) {
                throw ValidationException::withMessages(['group' => 'This group is no longer open for booking.']);
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
            $booking->child_fare = $group->child_fare;
            $booking->infant_fare = $group->infant_fare;
            $booking->total_amount = $adults * $group->adult_fare + $children * $group->child_fare + $infants * $group->infant_fare;
            $booking->status = BookingStatus::OnHold;
            $booking->hold_expires_at = now()->addMinutes($group->holdMinutes());
            $booking->agent_remarks = $remarks;
            $booking->save();

            $booking->reference = ReferenceNumber::make(config('portal.reference_prefix'), $booking->id);
            $booking->save();

            return $booking;
        });
    }

    /**
     * AMS confirms the seats (normally once payment is in) and the booking is charged to the agency.
     * A hold that has already run out can still be confirmed if its seats are still free.
     */
    public function confirm(Booking $booking, User $admin, ?string $pnr = null): Booking
    {
        return DB::transaction(function () use ($booking, $admin, $pnr) {
            FlightGroup::query()->whereKey($booking->flight_group_id)->lockForUpdate()->firstOrFail();
            $booking = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if (! in_array($booking->status, [BookingStatus::OnHold, BookingStatus::Expired], true)) {
                throw ValidationException::withMessages(['status' => "A {$booking->status->value} booking can't be confirmed."]);
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
            $booking->pnr = $pnr ?? $booking->pnr;
            $booking->save();

            $this->ledger->chargeBooking($booking, $admin);

            return $booking;
        });
    }

    /**
     * Cancel a booking and free its seats. A confirmed booking is refunded in full on the ledger;
     * any airline penalty is charged separately as an adjustment.
     */
    public function cancel(Booking $booking, User $by, ?string $reason = null): Booking
    {
        return DB::transaction(function () use ($booking, $by, $reason) {
            $booking = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if (! in_array($booking->status, [BookingStatus::OnHold, BookingStatus::Confirmed], true)) {
                throw ValidationException::withMessages(['status' => "A {$booking->status->value} booking can't be cancelled."]);
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
}
