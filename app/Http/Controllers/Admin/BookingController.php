<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\PassengersRequest;
use App\Models\Booking;
use App\Models\FlightGroup;
use App\Presenters\BookingPresenter;
use App\Services\BookingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'group' => ['nullable', 'integer'],
        ]);

        $bookings = Booking::query()
            ->with(['flightGroup:id,sector,departure_date', 'agency:id,name,city'])
            ->when($filters['status'] ?? null, fn (Builder $q, $status) => match ($status) {
                // A hold that ran out is expired even before the minute job marks it.
                BookingStatus::OnHold->value => $q->where('status', $status)->where('hold_expires_at', '>', now()),
                BookingStatus::Expired->value => $q->where(fn (Builder $q) => $q->where('status', $status)
                    ->orWhere(fn (Builder $q) => $q->where('status', BookingStatus::OnHold)->where('hold_expires_at', '<=', now()))),
                default => $q->where('status', $status),
            })
            ->when($filters['group'] ?? null, fn (Builder $q, $group) => $q->where('flight_group_id', $group))
            ->when($filters['search'] ?? null, fn (Builder $q, $search) => $q->where(fn (Builder $q) => $q
                ->where('reference', 'like', "%{$search}%")
                ->orWhere('pnr', 'like', "%{$search}%")
                ->orWhereHas('agency', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"))
                ->orWhereHas('passengers', fn (Builder $q) => $q->where(fn (Builder $q) => $q->where('surname', 'like', "%{$search}%")->orWhere('given_name', 'like', "%{$search}%")))))
            // Running holds first: those are the ones waiting on AMS.
            ->orderByRaw('(status = ? AND hold_expires_at > ?) DESC', [BookingStatus::OnHold->value, now()])
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $group = isset($filters['group']) ? FlightGroup::query()->find($filters['group']) : null;

        return Inertia::render('admin/bookings/Index', [
            'bookings' => $bookings->through(fn (Booking $b) => BookingPresenter::row($b)),
            'filters' => $filters,
            'group' => $group ? [
                'id' => $group->id,
                'sector' => $group->sector,
                'departure_date' => $group->departure_date->toDateString(),
            ] : null,
        ]);
    }

    public function show(Request $request, Booking $booking): Response
    {
        $booking->load(['flightGroup.airline:id,name,code', 'flightGroup.groupType:id,name', 'flightGroup.legs', 'passengers', 'payments.bankAccount', 'agency', 'user:id,name']);

        return Inertia::render('admin/bookings/Show', [
            'booking' => [
                ...BookingPresenter::detail($booking, $request->user()),
                'agency_balance' => $booking->agency->balance(),
                'supplier_pnr' => $booking->flightGroup->supplier_pnr,
            ],
        ]);
    }

    public function confirm(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate(['pnr' => ['nullable', 'string', 'max:20', 'alpha_num']], [], ['pnr' => 'PNR']);

        $this->bookings->confirm($booking, $request->user(), isset($data['pnr']) ? strtoupper($data['pnr']) : null);

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$booking->reference} confirmed and charged to {$booking->agency->name}."]);

        return back();
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);

        $this->bookings->cancel($booking, $request->user(), $data['reason']);

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$booking->reference} cancelled."]);

        return back();
    }

    public function updatePassengers(PassengersRequest $request, Booking $booking): RedirectResponse
    {
        $pnr = $request->validated('pnr');

        $this->bookings->updatePassengers($booking, $request->validated('passengers'), $request->user(), $pnr === null ? '' : strtoupper($pnr));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Passengers and ticketing saved.']);

        return back();
    }
}
