<?php

namespace App\Presenters;

use App\Models\Payment;

class PaymentPresenter
{
    /**
     * Load bankAccount (and booking, agency for AMS lists) first.
     *
     * @return array<string, mixed>
     */
    public static function row(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'amount' => (int) $payment->amount,
            'paid_on' => $payment->paid_on->toDateString(),
            'description' => $payment->description,
            'status' => $payment->status->value,
            'admin_remarks' => $payment->admin_remarks,
            'reviewed_at' => $payment->reviewed_at?->toIso8601String(),
            'created_at' => $payment->created_at->toIso8601String(),
            'bank' => $payment->bankAccount->bank_name,
            'receipt_url' => route('payments.receipt', $payment),
            'receipt_is_pdf' => str_ends_with(strtolower($payment->receipt_path), '.pdf'),
            'booking' => $payment->relationLoaded('booking') && $payment->booking
                ? ['id' => $payment->booking->id, 'reference' => $payment->booking->reference]
                : null,
            'agency' => $payment->relationLoaded('agency') ? $payment->agency->only('id', 'name', 'city') : null,
            'submitted_by' => $payment->relationLoaded('user') ? $payment->user->name : null,
        ];
    }
}
