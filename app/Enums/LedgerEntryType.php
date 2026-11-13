<?php

namespace App\Enums;

enum LedgerEntryType: string
{
    // Debit: a booking was confirmed.
    case Booking = 'booking';
    // Credit: a payment was approved.
    case Payment = 'payment';
    // Either side: a manual correction by AMS.
    case Adjustment = 'adjustment';
    // Credit: money owed back to the agency, e.g. a confirmed booking cancelled.
    case Refund = 'refund';
}
