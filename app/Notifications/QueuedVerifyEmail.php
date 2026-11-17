<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

// Laravel's "verify your email" message, sent from the queue instead of inside the sign-up request.
class QueuedVerifyEmail extends VerifyEmail implements ShouldQueue
{
    use Queueable;
}
