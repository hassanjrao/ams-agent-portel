<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\PaymentReviewed;
use App\Notifications\PaymentSubmitted;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class PaymentService
{
    // Receipts sit on the private disk, never under public/: only the agency and AMS can open them.
    public const RECEIPT_DISK = 'local';

    public function __construct(private LedgerService $ledger) {}

    /**
     * An agency reports a bank transfer and attaches the receipt. Nothing is credited until AMS
     * has matched it against the bank statement and approved it.
     *
     * @param  array{bank_account_id: int, amount: int, paid_on: string, booking_id?: int|null, description?: string|null}  $data
     */
    public function submit(User $agent, array $data, UploadedFile $receipt): Payment
    {
        $agency = $agent->agency;
        if (! $agency || ! $agency->isActive()) {
            throw ValidationException::withMessages(['amount' => 'Your agency must be approved before sending payments.']);
        }

        $path = $receipt->store("receipts/{$agency->id}/".now()->format('Y-m'), self::RECEIPT_DISK);
        if ($path === false) {
            throw ValidationException::withMessages(['receipt' => 'The receipt could not be saved. Please try again.']);
        }

        try {
            $payment = new Payment([
                'bank_account_id' => $data['bank_account_id'],
                'booking_id' => $data['booking_id'] ?? null,
                'amount' => $data['amount'],
                'paid_on' => $data['paid_on'],
                'description' => $data['description'] ?? null,
                'receipt_path' => $path,
            ]);
            $payment->agency_id = $agency->id;
            $payment->user_id = $agent->id;
            $payment->status = PaymentStatus::Pending;
            $payment->save();
        } catch (Throwable $e) {
            // Don't leave an orphaned receipt behind when the row couldn't be written.
            Storage::disk(self::RECEIPT_DISK)->delete($path);

            throw $e;
        }

        Notification::send(User::query()->admins()->get(), new PaymentSubmitted($payment));

        return $payment;
    }

    /**
     * AMS has seen the money arrive: credit it to the agency's ledger.
     */
    public function approve(Payment $payment, User $admin, ?string $remarks = null): Payment
    {
        $payment = DB::transaction(function () use ($payment, $admin, $remarks) {
            $payment = $this->lockPending($payment);

            $payment->status = PaymentStatus::Approved;
            $payment->reviewed_at = now();
            $payment->reviewed_by = $admin->id;
            $payment->admin_remarks = $remarks;
            $payment->save();

            $this->ledger->creditPayment($payment, $admin);

            return $payment;
        });

        Notification::send($payment->agency->users, new PaymentReviewed($payment));

        return $payment;
    }

    public function reject(Payment $payment, User $admin, string $remarks): Payment
    {
        $payment = DB::transaction(function () use ($payment, $admin, $remarks) {
            $payment = $this->lockPending($payment);

            $payment->status = PaymentStatus::Rejected;
            $payment->reviewed_at = now();
            $payment->reviewed_by = $admin->id;
            $payment->admin_remarks = $remarks;
            $payment->save();

            return $payment;
        });

        Notification::send($payment->agency->users, new PaymentReviewed($payment));

        return $payment;
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
