<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\GroupSourcing;
use App\Enums\GroupStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FlightGroupRequest;
use App\Models\Airline;
use App\Models\FlightGroup;
use App\Models\GroupType;
use App\Models\Supplier;
use App\Services\FlightGroupService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FlightGroupController extends Controller
{
    public function __construct(private FlightGroupService $groups) {}

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'type' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::enum(GroupStatus::class)],
            'search' => ['nullable', 'string', 'max:60'],
            'sourcing' => ['nullable', Rule::enum(GroupSourcing::class)],
            'supplier' => ['nullable', 'integer'],
            // Past departures are hidden unless asked for: the list is mostly about what's still selling.
            'past' => ['nullable', 'boolean'],
        ]);

        $groups = FlightGroup::query()
            ->with(['airline:id,name,code', 'groupType:id,name', 'supplier:id,name'])
            ->withSeatsTaken()
            ->withSum(['bookings as seats_confirmed' => fn (Builder $q) => $q->where('status', BookingStatus::Confirmed)], 'seats')
            ->when($filters['type'] ?? null, fn (Builder $q, $type) => $q->where('group_type_id', $type))
            ->when($filters['status'] ?? null, fn (Builder $q, $status) => $q->where('status', $status))
            ->when($filters['sourcing'] ?? null, fn (Builder $q, $sourcing) => $q->where('sourcing', $sourcing))
            ->when($filters['supplier'] ?? null, fn (Builder $q, $supplier) => $q->where('supplier_id', $supplier))
            ->when($filters['search'] ?? null, fn (Builder $q, $search) => $q->where(fn (Builder $q) => $q
                ->where('sector', 'like', "%{$search}%")
                ->orWhere('supplier_pnr', 'like', "%{$search}%")
                ->orWhereHas('legs', fn (Builder $q) => $q->where('flight_number', 'like', '%'.str_replace(' ', '', $search).'%'))))
            ->unless($filters['past'] ?? false, fn (Builder $q) => $q->whereDate('departure_date', '>=', today()))
            ->orderBy('departure_date')
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('admin/groups/Index', [
            'groups' => $groups->through(fn (FlightGroup $g) => [
                'id' => $g->id,
                'sector' => $g->sector,
                'departure_date' => $g->departure_date->toDateString(),
                'return_date' => $g->return_date?->toDateString(),
                'duration_days' => $g->duration_days,
                'airline' => $g->airline->only('name', 'code'),
                'group_type' => $g->groupType->name,
                'total_seats' => $g->total_seats,
                'seats_confirmed' => (int) $g->seats_confirmed,
                'seats_held' => (int) $g->seats_taken - (int) $g->seats_confirmed,
                'seats_available' => $g->seatsAvailable(),
                'adult_fare' => $g->adult_fare,
                'hold_minutes' => $g->holdMinutes(),
                'sourcing' => $g->sourcing->value,
                'supplier' => $g->supplier?->name,
                'status' => $g->status->value,
            ]),
            'filters' => $filters,
            'groupTypes' => GroupType::query()->orderBy('sort_order')->get(['id', 'name']),
            'defaultHoldMinutes' => config('portal.hold_minutes'),
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/groups/Form', [
            'group' => null,
            ...$this->formOptions(),
        ]);
    }

    public function store(FlightGroupRequest $request): RedirectResponse
    {
        $group = $this->groups->save(new FlightGroup, $request->groupAttributes(), $request->legs(), $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Group {$group->sector} on {$group->departure_date->format('d M Y')} saved."]);

        return to_route('admin.groups.index');
    }

    public function edit(FlightGroup $flightGroup): Response
    {
        $flightGroup->load('legs');

        return Inertia::render('admin/groups/Form', [
            'group' => [
                ...$flightGroup->only([
                    'id', 'group_type_id', 'airline_id', 'sector', 'duration_days', 'total_seats',
                    'adult_fare', 'child_fare', 'infant_fare', 'supplier_pnr', 'supplier_id', 'hold_minutes', 'notes',
                ]),
                'sourcing' => $flightGroup->sourcing->value,
                'status' => $flightGroup->status->value,
                'seats_taken' => $flightGroup->total_seats - $flightGroup->seatsAvailable(),
                'legs' => $flightGroup->legs->map(fn ($leg) => [
                    'flight_number' => $leg->flight_number,
                    'origin' => $leg->origin,
                    'destination' => $leg->destination,
                    // The format <input type="datetime-local"> expects.
                    'departs_at' => $leg->departs_at->format('Y-m-d\TH:i'),
                    'arrives_at' => $leg->arrives_at->format('Y-m-d\TH:i'),
                    'baggage' => $leg->baggage,
                    'meal' => $leg->meal,
                ]),
            ],
            ...$this->formOptions($flightGroup),
        ]);
    }

    public function update(FlightGroupRequest $request, FlightGroup $flightGroup): RedirectResponse
    {
        $this->groups->save($flightGroup, $request->groupAttributes(), $request->legs(), $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Group updated.']);

        return to_route('admin.groups.index');
    }

    public function updateStatus(Request $request, FlightGroup $flightGroup): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::enum(GroupStatus::class)]]);

        $flightGroup->status = GroupStatus::from($data['status']);
        $flightGroup->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Group is now {$flightGroup->status->value}."]);

        return back();
    }

    public function destroy(FlightGroup $flightGroup): RedirectResponse
    {
        $this->groups->delete($flightGroup);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Group {$flightGroup->sector} on {$flightGroup->departure_date->format('d M Y')} deleted."]);

        return to_route('admin.groups.index');
    }

    /**
     * The payment window from the groups list. It applies to holds placed from now on: a hold
     * already running keeps the deadline it was given.
     */
    public function updateHoldMinutes(Request $request, FlightGroup $flightGroup): RedirectResponse
    {
        $data = $request->validate(
            ['hold_minutes' => ['required', 'integer', 'min:'.FlightGroup::MIN_HOLD_MINUTES, 'max:'.FlightGroup::MAX_HOLD_MINUTES]],
            [],
            ['hold_minutes' => 'payment window'],
        );

        $flightGroup->hold_minutes = $data['hold_minutes'];
        $flightGroup->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$flightGroup->sector}: agencies now have {$flightGroup->hold_minutes} minutes to pay."]);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(?FlightGroup $group = null): array
    {
        return [
            // Active suppliers, plus the group's own one if it has since been deactivated.
            'suppliers' => Supplier::query()
                ->where(fn (Builder $q) => $q->where('is_active', true)->when($group?->supplier_id, fn (Builder $q, $id) => $q->orWhere('id', $id)))
                ->orderBy('name')
                ->get(['id', 'name']),
            'groupTypes' => GroupType::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'name']),
            'airlines' => Airline::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
            'statuses' => array_column(GroupStatus::cases(), 'value'),
            'defaultHoldMinutes' => config('portal.hold_minutes'),
        ];
    }
}
