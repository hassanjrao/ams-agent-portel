<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

// Laravel's password reset message, sent from the queue so a bad address can't break the form.
class QueuedResetPassword extends ResetPassword implements ShouldQueue
{
    use Queueable;
}
