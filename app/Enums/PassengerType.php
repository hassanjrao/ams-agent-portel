<?php

namespace App\Enums;

enum PassengerType: string
{
    case Adult = 'adult';
    case Child = 'child';
    case Infant = 'infant';
}
