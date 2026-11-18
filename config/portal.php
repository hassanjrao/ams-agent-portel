<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Booking holds
    |--------------------------------------------------------------------------
    |
    | The payment window: minutes an unpaid booking keeps its seats before they
    | go back to the block. Admins set it per group (on the group form or from
    | the groups list); this is what a group uses when it has none of its own.
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

    /*
    |--------------------------------------------------------------------------
    | Free plan
    |--------------------------------------------------------------------------
    |
    | How many agencies may use the portal for free: the first ones to sign up.
    | Later agencies can still sign up and log in, but cannot book, pay or change
    | bookings, and AMS staff are shown that the limit is reached. Set to 0 (or
    | leave empty) to lift the limit once the portal is upgraded.
    |
    */

    'free_agency_limit' => (int) env('PORTAL_FREE_AGENCY_LIMIT', 50),

    // Who to contact to lift the limit, shown to AMS staff in the "limit reached" notice.
    'upgrade_contact' => [
        'name' => 'Hassan Javaid Rao',
        'whatsapp' => '923035209409',
    ],

];
