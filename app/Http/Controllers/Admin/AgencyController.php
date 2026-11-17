<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AgencyStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StatementRequest;
use App\Models\Agency;
use App\Models\Booking;
use App\Services\AgencyService;
use App\Services\LedgerService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AgencyController extends Controller
{
    public function __construct(private AgencyService $agencies) {}

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(AgencyStatus::class)],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $agencies = Agency::query()
            ->withCount('bookings')
            ->withSum('ledgerEntries as total_debit', 'debit')
            ->withSum('ledgerEntries as total_credit', 'credit')
            ->when($filters['status'] ?? null, fn (Builder $q, $status) => $q->where('status', $status))
            ->when($filters['search'] ?? null, fn (Builder $q, $search) => $q->where(fn (Builder $q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('contact_person', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('city', 'like', "%{$search}%")))
            // New sign-ups waiting for approval first.
            ->orderByRaw('status = ? DESC', [AgencyStatus::Pending->value])
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('admin/agencies/Index', [
            'agencies' => $agencies->through(fn (Agency $a) => [
                ...$a->only(['id', 'name', 'contact_person', 'phone', 'email', 'city', 'bookings_count']),
                'status' => $a->status->value,
                'balance' => (int) $a->total_debit - (int) $a->total_credit,
                'created_at' => $a->created_at->toDateString(),
            ]),
            'filters' => $filters,
        ]);
    }

    public function show(Agency $agency): Response
    {
        $agency->load('users:id,agency_id,name,email,phone,created_at');

        return Inertia::render('admin/agencies/Show', [
            'agency' => [
                ...$agency->only(['id', 'name', 'contact_person', 'phone', 'email', 'city', 'country', 'address', 'admin_notes']),
                'status' => $agency->status->value,
                'approved_at' => $agency->approved_at?->toDateTimeString(),
                'created_at' => $agency->created_at->toDateTimeString(),
                'balance' => $agency->balance(),
                'users' => $agency->users,
            ],
            'recentBookings' => $agency->bookings()
                ->with('flightGroup:id,sector,departure_date')
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn (Booking $b) => [
                    'id' => $b->id,
                    'reference' => $b->reference,
                    'sector' => $b->flightGroup->sector,
                    'departure_date' => $b->flightGroup->departure_date->toDateString(),
                    'passengers' => $b->passengerCount(),
                    'total_amount' => $b->total_amount,
                    'status' => $b->status->value,
                ]),
        ]);
    }

    public function updateStatus(Request $request, Agency $agency): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([AgencyStatus::Active->value, AgencyStatus::Suspended->value])],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->agencies->setStatus($agency, AgencyStatus::from($data['status']), $request->user(), $data['admin_notes'] ?? $agency->admin_notes);

        Inertia::flash('toast', ['type' => 'success', 'message' => $agency->status === AgencyStatus::Active ? "{$agency->name} can now book." : "{$agency->name} is suspended."]);

        return back();
    }

    /**
     * The agency's statement, as the agency sees it, with AMS's manual adjustments.
     */
    public function ledger(StatementRequest $request, Agency $agency, LedgerService $ledger): Response
    {
        [$from, $to] = $request->range();

        return Inertia::render('admin/agencies/Ledger', [
            'agency' => [...$agency->only(['id', 'name', 'city']), 'status' => $agency->status->value],
            'statement' => $ledger->statement($agency, $from, $to),
            'filters' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
        ]);
    }

    /**
     * A manual correction: an airline penalty or service charge (charge), or a discount or
     * write-off (credit). Entries are never edited; a mistake is undone with the opposite entry.
     */
    public function adjust(Request $request, Agency $agency, LedgerService $ledger): RedirectResponse
    {
        $data = $request->validate([
            'direction' => ['required', Rule::in(['charge', 'credit'])],
            'amount' => ['required', 'integer', 'min:1', 'max:100000000'],
            'description' => ['required', 'string', 'max:200'],
        ]);

        $ledger->adjust(
            $agency,
            $data['direction'] === 'charge' ? (int) $data['amount'] : -(int) $data['amount'],
            $data['description'],
            $request->user(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Adjustment posted to '.$agency->name.'.']);

        return back();
    }
}
