<?php

namespace App\Notifications;

use App\Enums\PaymentStatus;
use App\Models\Payment;

// To the agency: AMS approved (credited) or rejected a payment it sent.
class PaymentReviewed extends PortalMessage
{
    public function __construct(public Payment $payment) {}

    protected function subject(): string
    {
        return $this->approved()
            ? "Payment of PKR {$this->amount()} received"
            : "Payment of PKR {$this->amount()} could not be verified";
    }

    protected function lines(): array
    {
        $lines = $this->approved()
            ? ["Your payment of PKR {$this->amount()} on {$this->payment->paid_on->format('d M Y')} has been credited to your account."]
            : ["We could not verify your payment of PKR {$this->amount()} on {$this->payment->paid_on->format('d M Y')}, so it has not been credited."];
        if ($this->payment->admin_remarks) {
            $lines[] = "AMS note: {$this->payment->admin_remarks}";
        }

        return $lines;
    }

    protected function action(): array
    {
        return $this->approved()
            ? ['View statement', route('ledger')]
            : ['View payments', route('payments.index')];
    }

    private function approved(): bool
    {
        return $this->payment->status === PaymentStatus::Approved;
    }

    private function amount(): string
    {
        return number_format($this->payment->amount);
    }
}
