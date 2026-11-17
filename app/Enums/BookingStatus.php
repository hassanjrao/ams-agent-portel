<?php

namespace App\Enums;

enum BookingStatus: string
{
    // Seats reserved until hold_expires_at, waiting for payment.
    case OnHold = 'on_hold';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    // Hold ran out unpaid; the seats went back to the block.
    case Expired = 'expired';

    /**
     * Statuses whose seats are taken from the block (on_hold only until it expires).
     *
     * @return list<self>
     */
    public static function holdingSeats(): array
    {
        return [self::OnHold, self::Confirmed];
    }

    // "on hold", for messages.
    public function label(): string
    {
        return str_replace('_', ' ', $this->value);
    }
}
