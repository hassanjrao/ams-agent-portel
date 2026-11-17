<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReceiptController extends Controller
{
    /**
     * Shows a payment's receipt in the browser, for the agency that sent it and for AMS.
     */
    public function __invoke(Payment $payment): StreamedResponse
    {
        Gate::authorize('view', $payment);

        $disk = Storage::disk(PaymentService::RECEIPT_DISK);
        abort_unless($disk->exists($payment->receipt_path), 404);

        $name = "receipt-{$payment->id}.".pathinfo($payment->receipt_path, PATHINFO_EXTENSION);

        return $disk->response($payment->receipt_path, $name, [
            'Cache-Control' => 'private, max-age=600',
            'X-Content-Type-Options' => 'nosniff',
        ], 'inline');
    }
}
