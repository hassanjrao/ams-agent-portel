<?php

namespace App\Notifications;

use App\Models\Booking;

// To the agency: AMS confirmed the seats and charged the booking to its account.
class BookingConfirmed extends PortalMessage
{
    public function __construct(public Booking $booking) {}

    protected function subject(): string
    {
        return "Booking {$this->booking->reference} confirmed";
    }

    protected function lines(): array
    {
        $group = $this->booking->flightGroup;
        $lines = [
            "Your booking {$this->booking->reference} for {$group->sector} on {$group->departure_date->format('d M Y')} ({$this->booking->passengerCount()} passengers) is confirmed.",
        ];
        if ($this->booking->pnr) {
            $lines[] = "PNR: {$this->booking->pnr}";
        }
        $lines[] = 'PKR '.number_format($this->booking->total_amount).' has been charged to your account.';

        return $lines;
    }

    protected function action(): array
    {
        return ['View booking', route('bookings.show', $this->booking)];
    }
}
