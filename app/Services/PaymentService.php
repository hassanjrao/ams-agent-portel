<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(private LedgerService $ledger) {}

    /**
     * AMS has seen the money arrive: credit it to the agency's ledger.
     */
    public function approve(Payment $payment, User $admin, ?string $remarks = null): Payment
    {
        return DB::transaction(function () use ($payment, $admin, $remarks) {
            $payment = $this->lockPending($payment);

            $payment->status = PaymentStatus::Approved;
            $payment->reviewed_at = now();
            $payment->reviewed_by = $admin->id;
            $payment->admin_remarks = $remarks;
            $payment->save();

            $this->ledger->creditPayment($payment, $admin);

            return $payment;
        });
    }

    public function reject(Payment $payment, User $admin, string $remarks): Payment
    {
        return DB::transaction(function () use ($payment, $admin, $remarks) {
            $payment = $this->lockPending($payment);

            $payment->status = PaymentStatus::Rejected;
            $payment->reviewed_at = now();
            $payment->reviewed_by = $admin->id;
            $payment->admin_remarks = $remarks;
            $payment->save();

            return $payment;
        });
    }

    /**
     * Re-read the payment under a lock so a double click can't approve it (and credit it) twice.
     */
    private function lockPending(Payment $payment): Payment
    {
        $payment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
        if ($payment->status !== PaymentStatus::Pending) {
            throw ValidationException::withMessages(['status' => "This payment is already {$payment->status->value}."]);
        }

        return $payment;
    }
}
