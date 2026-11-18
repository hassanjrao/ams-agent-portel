<?php

namespace App\Enums;

// How AMS got a group's seats. Admin-only bookkeeping; agencies never see it.
enum GroupSourcing: string
{
    // AMS bought the block from the airline itself.
    case Direct = 'direct';
    // AMS took the seats from a supplier.
    case Outsourced = 'outsourced';
}
