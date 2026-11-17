<?php

namespace App\Http\Controllers\Agent;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\StoreBookingRequest;
use App\Http\Requests\PassengersRequest;
use App\Models\BankAccount;
use App\Models\Booking;
use App\Models\FlightGroup;
use App\Presenters\BookingPresenter;
use App\Services\BookingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function __construct(private BookingService $bookings) {}

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(BookingStatus::class)],
            'search' => ['nullable', 'string', 'max:60'],
        ]);

        $bookings = $request->user()->agency->bookings()
            ->with('flightGroup:id,sector,departure_date')
            ->when($filters['status'] ?? null, fn (Builder $q, $status) => $status === BookingStatus::Expired->value
                // Holds that ran out but the minute job hasn't marked yet count as expired too.
                ? $q->where(fn (Builder $q) => $q->where('status', $status)->orWhere(fn (Builder $q) => $q->where('status', BookingStatus::OnHold)->where('hold_expires_at', '<=', now())))
                : ($status === BookingStatus::OnHold->value
                    ? $q->where('status', $status)->where('hold_expires_at', '>', now())
                    : $q->where('status', $status)))
            ->when($filters['search'] ?? null, fn (Builder $q, $search) => $q->where(fn (Builder $q) => $q
                ->where('reference', 'like', "%{$search}%")
                ->orWhere('pnr', 'like', "%{$search}%")
                ->orWhereHas('passengers', fn (Builder $q) => $q->where(fn (Builder $q) => $q->where('surname', 'like', "%{$search}%")->orWhere('given_name', 'like', "%{$search}%")))))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('agent/bookings/Index', [
            'bookings' => $bookings->through(fn (Booking $b) => BookingPresenter::row($b)),
            'filters' => $filters,
        ]);
    }

    public function store(StoreBookingRequest $request, FlightGroup $flightGroup): RedirectResponse
    {
        $data = $request->validated();

        $booking = $this->bookings->hold(
            $request->user(),
            $flightGroup,
            (int) $data['adults'],
            (int) $data['children'],
            (int) $data['infants'],
            $data['remarks'] ?? null,
            $data['passengers'],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => "Seats held: {$booking->reference}. Send payment before the hold runs out."]);

        return to_route('bookings.show', $booking);
    }

    public function show(Request $request, Booking $booking): Response
    {
        Gate::authorize('view', $booking);

        $booking->load(['flightGroup.airline:id,name,code', 'flightGroup.groupType:id,name', 'flightGroup.legs', 'passengers', 'payments.bankAccount', 'agency', 'user:id,name']);

        return Inertia::render('agent/bookings/Show', [
            'booking' => BookingPresenter::detail($booking, $request->user()),
            'bankAccounts' => BankAccount::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'bank_name', 'account_title', 'account_number', 'iban', 'branch']),
        ]);
    }

    public function updatePassengers(PassengersRequest $request, Booking $booking): RedirectResponse
    {
        Gate::authorize('update', $booking);

        $this->bookings->updatePassengers($booking, $request->validated('passengers'), $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Passenger names saved.']);

        return back();
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        Gate::authorize('update', $booking);

        $this->bookings->cancel($booking, $request->user(), 'Cancelled by the agency');

        Inertia::flash('toast', ['type' => 'success', 'message' => "Booking {$booking->reference} cancelled and its seats released."]);

        return back();
    }
}
