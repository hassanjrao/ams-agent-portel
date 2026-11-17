<?php

namespace App\Notifications;

use App\Models\Payment;

// To AMS staff: an agency uploaded a receipt that needs checking against the bank.
class PaymentSubmitted extends PortalMessage
{
    public function __construct(public Payment $payment) {}

    protected function subject(): string
    {
        return "Payment to review: PKR {$this->amount()} from {$this->payment->agency->name}";
    }

    protected function lines(): array
    {
        $lines = [
            "{$this->payment->agency->name} reports paying PKR {$this->amount()} into {$this->payment->bankAccount->bank_name} on {$this->payment->paid_on->format('d M Y')}.",
        ];
        if ($this->payment->booking) {
            $lines[] = "It is for booking {$this->payment->booking->reference}.";
        }
        $lines[] = 'Check the receipt against the bank statement, then approve or reject it.';

        return $lines;
    }

    protected function action(): array
    {
        return ['Review payments', route('admin.payments.index')];
    }

    private function amount(): string
    {
        return number_format($this->payment->amount);
    }
}
