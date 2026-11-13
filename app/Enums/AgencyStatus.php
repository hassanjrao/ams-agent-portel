<?php

namespace App\Enums;

enum AgencyStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
}
