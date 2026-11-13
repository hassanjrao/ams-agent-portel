<?php

namespace App\Services;

use App\Enums\LedgerEntryType;
use App\Models\Agency;
use App\Models\Booking;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\User;
use InvalidArgumentException;

/**
 * The only writer of ledger entries. Entries are append-only: to correct one, post an adjustment.
 */
class LedgerService
{
    public function chargeBooking(Booking $booking, ?User $by): LedgerEntry
    {
        return $this->post($booking->agency_id, LedgerEntryType::Booking, $this->bookingLine('Booking confirmed', $booking), debit: $booking->total_amount, booking: $booking, by: $by);
    }

    public function refundBooking(Booking $booking, ?User $by): LedgerEntry
    {
        return $this->post($booking->agency_id, LedgerEntryType::Refund, $this->bookingLine('Booking cancelled', $booking), credit: $booking->total_amount, booking: $booking, by: $by);
    }

    public function creditPayment(Payment $payment, ?User $by): LedgerEntry
    {
        $line = "Payment received: {$payment->bankAccount->bank_name}".($payment->description ? " - {$payment->description}" : '');

        return $this->post($payment->agency_id, LedgerEntryType::Payment, $line, credit: $payment->amount, booking: $payment->booking, payment: $payment, by: $by);
    }

    /**
     * A manual correction by AMS: a positive amount is charged to the agency, a negative one credited.
     */
    public function adjust(Agency $agency, int $amount, string $description, User $by): LedgerEntry
    {
        if ($amount === 0) {
            throw new InvalidArgumentException('An adjustment needs a non-zero amount.');
        }

        return $this->post($agency->id, LedgerEntryType::Adjustment, $description, debit: max($amount, 0), credit: max(-$amount, 0), by: $by);
    }

    private function post(int $agencyId, LedgerEntryType $type, string $description, int $debit = 0, int $credit = 0, ?Booking $booking = null, ?Payment $payment = null, ?User $by = null): LedgerEntry
    {
        $entry = new LedgerEntry;
        $entry->agency_id = $agencyId;
        // Placeholder until the id is known; replaced below in the same request.
        $entry->voucher_number = 'TMP-'.bin2hex(random_bytes(6));
        $entry->entry_date = today();
        $entry->type = $type;
        $entry->description = $description;
        $entry->debit = $debit;
        $entry->credit = $credit;
        $entry->booking_id = $booking?->id;
        $entry->payment_id = $payment?->id;
        $entry->created_by = $by?->id;
        $entry->save();

        $entry->voucher_number = ReferenceNumber::make(config('portal.reference_prefix').'V', $entry->id);
        $entry->save();

        return $entry;
    }

    private function bookingLine(string $what, Booking $booking): string
    {
        $group = $booking->flightGroup;

        return "{$what}: {$booking->reference} | {$group->sector} | {$group->departure_date->format('d M Y')} | {$booking->passengerCount()} pax";
    }
}
