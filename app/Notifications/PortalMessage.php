<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

/**
 * A portal email: a subject, a few lines and one button. Queued, so a slow or failing mail server
 * never holds up (or breaks) the booking, payment or approval that triggered it.
 */
abstract class PortalMessage extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    abstract protected function subject(): string;

    /**
     * @return list<string>
     */
    abstract protected function lines(): array;

    /**
     * @return array{0: string, 1: string} button label and link
     */
    abstract protected function action(): array;

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->subject())
            ->greeting("Dear {$notifiable->name},");

        foreach ($this->lines() as $line) {
            $mail->line($line);
        }

        [$label, $url] = $this->action();

        return $mail->action($label, $url)->salutation('Regards,  '."\n".'AMS Global Travel & Tours');
    }
}
