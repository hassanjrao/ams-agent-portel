<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Airline;
use App\Models\FlightGroup;
use App\Models\GroupType;
use App\Presenters\GroupPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GroupController extends Controller
{
    /**
     * Everything on sale, soonest first, filtered by category, airline, month and route.
     */
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'type' => ['nullable', 'integer'],
            'airline' => ['nullable', 'integer'],
            'month' => ['nullable', 'date_format:Y-m'],
            'search' => ['nullable', 'string', 'max:40'],
        ]);

        $groups = FlightGroup::query()
            ->bookable()
            ->withSeatsTaken()
            ->with(['airline:id,name,code', 'groupType:id,name', 'legs'])
            ->when($filters['type'] ?? null, fn (Builder $q, $type) => $q->where('group_type_id', $type))
            ->when($filters['airline'] ?? null, fn (Builder $q, $airline) => $q->where('airline_id', $airline))
            ->when($filters['month'] ?? null, fn (Builder $q, $month) => $q
                ->whereYear('departure_date', substr($month, 0, 4))
                ->whereMonth('departure_date', substr($month, 5, 2)))
            // "JED", "mux-jed" or "SV801": the route, or a flight on it.
            ->when($filters['search'] ?? null, fn (Builder $q, $search) => $q->where(fn (Builder $q) => $q
                ->where('sector', 'like', '%'.str_replace(' ', '-', trim($search)).'%')
                ->orWhereHas('legs', fn (Builder $q) => $q->where('flight_number', 'like', '%'.str_replace(' ', '', $search).'%'))))
            ->orderBy('departure_date')
            ->orderBy('id')
            ->paginate(12)
            ->withQueryString();

        $open = fn (Builder $q) => $q->bookable();

        return Inertia::render('agent/groups/Index', [
            'groups' => $groups->through(fn (FlightGroup $g) => GroupPresenter::card($g)),
            'filters' => $filters,
            // Category tabs carry how many groups each has on sale.
            'groupTypes' => GroupType::query()
                ->where('is_active', true)
                ->withCount(['flightGroups as open_count' => $open])
                ->orderBy('sort_order')
                ->get(['id', 'name']),
            'airlines' => Airline::query()
                ->whereHas('flightGroups', $open)
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'months' => FlightGroup::query()
                ->bookable()
                ->orderBy('departure_date')
                ->pluck('departure_date')
                ->map(fn ($date) => $date->format('Y-m'))
                ->unique()
                ->values(),
        ]);
    }

    /**
     * One group with its booking form. Only groups on sale can be opened here.
     */
    public function show(Request $request, FlightGroup $flightGroup): Response
    {
        abort_unless($flightGroup->isBookable(), 404);

        $flightGroup->load(['airline:id,name,code', 'groupType:id,name', 'legs'])->loadSum(['bookings as seats_taken' => fn ($q) => $q->holdingSeats()], 'seats');

        return Inertia::render('agent/groups/Show', [
            'group' => GroupPresenter::card($flightGroup),
            'infantsTakeSeat' => (bool) config('portal.infants_take_seat'),
        ]);
    }
}
