<?php

namespace App\Enums;

enum GroupStatus: string
{
    // Being set up by AMS; agents can't see it.
    case Draft = 'draft';
    // Listed and bookable.
    case Published = 'published';
    // Still visible on existing bookings, no longer bookable.
    case Closed = 'closed';
}
