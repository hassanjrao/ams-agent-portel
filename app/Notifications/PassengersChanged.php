<?php

namespace App\Notifications;

use App\Models\Booking;

// To AMS staff: an agency changed passenger names on a booking AMS has already confirmed, so
// tickets issued on the old names may need reissuing.
class PassengersChanged extends PortalMessage
{
    public function __construct(public Booking $booking) {}

    protected function subject(): string
    {
        return "Names changed on confirmed booking {$this->booking->reference}";
    }

    protected function lines(): array
    {
        $group = $this->booking->flightGroup;

        return [
            "{$this->booking->agency->name} changed the passenger names on {$this->booking->reference} ({$group->sector}, {$group->departure_date->format('d M Y')}) after AMS confirmed it.",
            'Check the names against any tickets already issued.',
        ];
    }

    protected function action(): array
    {
        return ['View booking', route('admin.bookings.show', $this->booking)];
    }
}
