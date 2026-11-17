<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    // The receipt and its details: the agency that sent the payment, and AMS staff.
    public function view(User $user, Payment $payment): bool
    {
        return $user->isAdmin() || ($user->agency_id !== null && $user->agency_id === $payment->agency_id);
    }
}
