<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

/**
 * An agency sees and manages only its own bookings; AMS staff see all of them. What may change
 * (names while on hold, cancelling a hold) is decided by BookingService from the booking's status.
 */
class BookingPolicy
{
    public function view(User $user, Booking $booking): bool
    {
        return $user->isAdmin() || ($user->agency_id !== null && $user->agency_id === $booking->agency_id);
    }

    public function update(User $user, Booking $booking): bool
    {
        return $this->view($user, $booking);
    }
}
