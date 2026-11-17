<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Presenters\PaymentPresenter;
use App\Services\PaymentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $payments) {}

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            // The queue to work through is the default view.
            'status' => ['nullable', Rule::enum(PaymentStatus::class)],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $status = $filters['status'] ?? PaymentStatus::Pending->value;

        $payments = Payment::query()
            ->with(['bankAccount:id,bank_name', 'booking:id,reference', 'agency:id,name,city', 'user:id,name'])
            ->where('status', $status)
            ->when($filters['search'] ?? null, fn (Builder $q, $search) => $q->where(fn (Builder $q) => $q
                ->whereHas('agency', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"))
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('amount', preg_replace('/\D/', '', $search) ?: -1)))
            // Oldest first while there is a queue to clear; newest first in the history.
            ->orderBy('created_at', $status === PaymentStatus::Pending->value ? 'asc' : 'desc')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('admin/payments/Index', [
            'payments' => $payments->through(fn (Payment $p) => PaymentPresenter::row($p)),
            'filters' => [...$filters, 'status' => $status],
            'counts' => Payment::query()->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function approve(Request $request, Payment $payment): RedirectResponse
    {
        $data = $request->validate(['remarks' => ['nullable', 'string', 'max:255']]);

        $this->payments->approve($payment, $request->user(), $data['remarks'] ?? null);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'PKR '.number_format($payment->amount)." credited to {$payment->agency->name}."]);

        return back();
    }

    public function reject(Request $request, Payment $payment): RedirectResponse
    {
        $data = $request->validate(['remarks' => ['required', 'string', 'max:255']], [
            'remarks.required' => 'Tell the agency why the payment was rejected.',
        ]);

        $this->payments->reject($payment, $request->user(), $data['remarks']);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Payment rejected. The agency has been told why.']);

        return back();
    }
}
