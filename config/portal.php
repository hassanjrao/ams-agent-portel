<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Booking holds
    |--------------------------------------------------------------------------
    |
    | Minutes an unpaid booking keeps its seats before they go back to the
    | block. A group can override it with its own hold_minutes.
    |
    */

    'hold_minutes' => (int) env('PORTAL_HOLD_MINUTES', 60),

    /*
    |--------------------------------------------------------------------------
    | Seats
    |--------------------------------------------------------------------------
    |
    | Whether an infant takes a seat from the block. Airlines seat infants on
    | an adult's lap, so by default they only add their fare.
    |
    */

    'infants_take_seat' => (bool) env('PORTAL_INFANTS_TAKE_SEAT', false),

    // Prefix on booking references (AMS-26110001) and ledger vouchers (AMSV-26110001).
    'reference_prefix' => env('PORTAL_REFERENCE_PREFIX', 'AMS'),

];
