<?php

namespace App\Notifications;

use App\Enums\BookingStatus;
use App\Models\Booking;

// To the agency: AMS cancelled one of its bookings.
class BookingCancelled extends PortalMessage
{
    public function __construct(public Booking $booking, public bool $wasConfirmed) {}

    protected function subject(): string
    {
        return "Booking {$this->booking->reference} cancelled";
    }

    protected function lines(): array
    {
        $group = $this->booking->flightGroup;
        $lines = ["AMS has cancelled booking {$this->booking->reference} for {$group->sector} on {$group->departure_date->format('d M Y')}."];
        if ($this->booking->cancel_reason) {
            $lines[] = "Reason: {$this->booking->cancel_reason}";
        }
        if ($this->wasConfirmed && $this->booking->status === BookingStatus::Cancelled) {
            $lines[] = 'PKR '.number_format($this->booking->total_amount).' has been credited back to your account.';
        }

        return $lines;
    }

    protected function action(): array
    {
        return ['View booking', route('bookings.show', $this->booking)];
    }
}
