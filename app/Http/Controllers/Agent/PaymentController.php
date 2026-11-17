<?php

namespace App\Http\Controllers\Agent;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\StorePaymentRequest;
use App\Models\BankAccount;
use App\Models\Booking;
use App\Models\Payment;
use App\Presenters\PaymentPresenter;
use App\Services\FreePlan;
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
            'status' => ['nullable', Rule::enum(PaymentStatus::class)],
            // Opened from a booking's "Send payment": start the form on that booking.
            'booking' => ['nullable', 'integer'],
        ]);
        $agency = $request->user()->agency;

        $payments = $agency->payments()
            ->with(['bankAccount:id,bank_name', 'booking:id,reference'])
            ->when($filters['status'] ?? null, fn (Builder $q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        // Bookings worth paying for: running holds and confirmed ones.
        $bookings = $agency->bookings()
            ->with('flightGroup:id,sector,departure_date')
            ->where(fn (Builder $q) => $q
                ->where('status', BookingStatus::Confirmed)
                ->orWhere(fn (Builder $q) => $q->where('status', BookingStatus::OnHold)->where('hold_expires_at', '>', now())))
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (Booking $b) => [
                'id' => $b->id,
                'reference' => $b->reference,
                'label' => "{$b->reference} · {$b->flightGroup->sector} · {$b->flightGroup->departure_date->format('d M')}",
                'total_amount' => (int) $b->total_amount,
            ]);

        return Inertia::render('agent/payments/Index', [
            'payments' => $payments->through(fn (Payment $p) => PaymentPresenter::row($p)),
            'filters' => $filters,
            'bankAccounts' => BankAccount::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'bank_name', 'account_title', 'account_number', 'iban', 'branch']),
            'bookings' => $bookings,
            'canPay' => $agency->isActive() && FreePlan::covers($agency),
            'pendingTotal' => (int) $agency->payments()->where('status', PaymentStatus::Pending)->sum('amount'),
        ]);
    }

    public function store(StorePaymentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $this->payments->submit($request->user(), $data, $request->file('receipt'));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Payment sent to AMS for checking. It is credited once approved.']);

        return to_route('payments.index');
    }
}
