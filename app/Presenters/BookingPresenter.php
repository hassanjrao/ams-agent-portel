<?php

namespace App\Presenters;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingPassenger;
use App\Models\Payment;
use App\Models\User;
use App\Services\FreePlan;

class BookingPresenter
{
    /**
     * A line in a bookings list. Load flightGroup (and agency for AMS) first.
     *
     * @return array<string, mixed>
     */
    public static function row(Booking $booking): array
    {
        return [
            'id' => $booking->id,
            'reference' => $booking->reference,
            'sector' => $booking->flightGroup->sector,
            'departure_date' => $booking->flightGroup->departure_date->toDateString(),
            'adults' => $booking->adults,
            'children' => $booking->children,
            'infants' => $booking->infants,
            'passengers' => $booking->passengerCount(),
            'total_amount' => (int) $booking->total_amount,
            'status' => self::status($booking),
            'pnr' => $booking->pnr,
            'seconds_left' => self::secondsLeft($booking),
            'created_at' => $booking->created_at->toIso8601String(),
            'agency' => $booking->relationLoaded('agency') ? $booking->agency->only('id', 'name', 'city') : null,
        ];
    }

    /**
     * The full booking page, for the agency or for AMS. Load flightGroup (with airline, groupType
     * and legs), passengers, payments.bankAccount, agency and user first.
     *
     * @return array<string, mixed>
     */
    public static function detail(Booking $booking, User $viewer): array
    {
        $holdActive = $booking->isHoldActive();
        // An agency beyond the free plan sees its bookings but can't act on them.
        $agencyMayAct = $viewer->isAdmin() || FreePlan::covers($booking->agency);

        return [
            ...self::row($booking),
            'adult_fare' => $booking->adult_fare,
            'child_fare' => $booking->child_fare,
            'infant_fare' => $booking->infant_fare,
            'discount_amount' => (int) $booking->discount_amount,
            'seats' => $booking->seats,
            'hold_expires_at' => $booking->hold_expires_at?->toIso8601String(),
            'confirmed_at' => $booking->confirmed_at?->toIso8601String(),
            'cancelled_at' => $booking->cancelled_at?->toIso8601String(),
            'cancel_reason' => $booking->cancel_reason,
            'agent_remarks' => $booking->agent_remarks,
            'booked_by' => $booking->user->name,
            'agency' => $booking->agency->only('id', 'name', 'city', 'phone', 'email', 'contact_person'),
            'group' => GroupPresenter::card($booking->flightGroup),
            'passengers' => $booking->passengers->map(fn (BookingPassenger $p) => [
                'id' => $p->id,
                'type' => $p->type->value,
                'title' => $p->title,
                'given_name' => $p->given_name,
                'surname' => $p->surname,
                'passport_number' => $p->passport_number,
                'nationality' => $p->nationality,
                'date_of_birth' => $p->date_of_birth?->toDateString(),
                'passport_expiry' => $p->passport_expiry?->toDateString(),
                'ticket_number' => $p->ticket_number,
            ])->all(),
            'payments' => $booking->payments->map(fn (Payment $p) => PaymentPresenter::row($p))->all(),
            'can' => [
                // An agency edits names and cancels only while its hold runs; AMS until it's cancelled.
                // Agencies may add or change names any time on a running hold or a confirmed booking.
                'edit_passengers' => $viewer->isAdmin()
                    ? in_array($booking->status, [BookingStatus::OnHold, BookingStatus::Confirmed], true)
                    : ($holdActive || $booking->status === BookingStatus::Confirmed) && $agencyMayAct,
                'cancel' => $viewer->isAdmin()
                    ? in_array($booking->status, [BookingStatus::OnHold, BookingStatus::Confirmed], true)
                    : $holdActive && $agencyMayAct,
                'confirm' => $viewer->isAdmin() && in_array($booking->status, [BookingStatus::OnHold, BookingStatus::Expired], true),
                // An expired hold can still be paid: AMS confirms it if its seats are still free.
                'pay' => ! $viewer->isAdmin() && $agencyMayAct && $booking->status !== BookingStatus::Cancelled,
            ],
        ];
    }

    /**
     * A hold that has run out reads as expired straight away, even before the minute job marks it.
     */
    public static function status(Booking $booking): string
    {
        return $booking->status === BookingStatus::OnHold && ! $booking->isHoldActive()
            ? BookingStatus::Expired->value
            : $booking->status->value;
    }

    public static function secondsLeft(Booking $booking): ?int
    {
        return $booking->isHoldActive() ? (int) max(0, now()->diffInSeconds($booking->hold_expires_at, false)) : null;
    }
}
