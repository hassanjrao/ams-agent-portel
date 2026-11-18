<?php

namespace Tests\Feature\Admin;

use App\Enums\AgencyStatus;
use App\Enums\GroupStatus;
use App\Models\Agency;
use App\Models\Airline;
use App\Models\FlightGroup;
use App\Models\GroupType;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AdminGroupsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_agents_cannot_open_the_admin_side(): void
    {
        $agent = User::factory()->agent()->create();

        $this->actingAs($agent)->get('/admin/groups')->assertForbidden();
        $this->actingAs($agent)->get('/dashboard')->assertOk();
        $this->actingAs($this->admin)->get('/dashboard')->assertRedirect('/admin');
    }

    public function test_every_admin_page_renders(): void
    {
        $group = FlightGroup::factory()->create();
        $agency = Agency::factory()->create();
        $booking = app(BookingService::class)->hold(User::factory()->agent($agency)->create(), $group, adults: 2);

        foreach ([
            '/admin', '/admin/groups', '/admin/groups/create', "/admin/groups/{$group->id}/edit",
            '/admin/agencies', "/admin/agencies/{$agency->id}", "/admin/agencies/{$agency->id}/ledger",
            '/admin/bookings', '/admin/bookings?status=on_hold', "/admin/bookings/{$booking->id}",
            '/admin/payments', '/admin/payments?status=approved',
            '/admin/airlines', '/admin/group-types', '/admin/bank-accounts',
        ] as $url) {
            $this->actingAs($this->admin)->get($url)->assertOk();
        }
    }

    public function test_an_admin_creates_a_group_with_its_flights(): void
    {
        $type = GroupType::factory()->create();
        $airline = Airline::factory()->create();

        $this->actingAs($this->admin)->post('/admin/groups', [
            'group_type_id' => $type->id,
            'airline_id' => $airline->id,
            'sector' => 'mux-jed-mux',
            'duration_days' => 21,
            'total_seats' => 30,
            'adult_fare' => 175000,
            'child_fare' => 160000,
            'infant_fare' => 45000,
            'status' => 'published',
            // Out of order on purpose: dates come from the earliest and latest flight.
            'legs' => [
                ['flight_number' => 'sv 800', 'origin' => 'jed', 'destination' => 'mux', 'departs_at' => '2026-12-11T08:35', 'arrives_at' => '2026-12-11T14:55', 'baggage' => '23+23', 'meal' => true],
                ['flight_number' => 'SV801', 'origin' => 'MUX', 'destination' => 'JED', 'departs_at' => '2026-11-20T16:35', 'arrives_at' => '2026-11-20T19:55', 'baggage' => '23+7', 'meal' => true],
            ],
        ])->assertRedirect('/admin/groups');

        $group = FlightGroup::query()->with('legs')->sole();
        $this->assertSame('MUX-JED-MUX', $group->sector);
        $this->assertSame('2026-11-20', $group->departure_date->toDateString());
        $this->assertSame('2026-12-11', $group->return_date->toDateString());
        $this->assertSame(['SV801', 'SV800'], $group->legs->pluck('flight_number')->all());
        $this->assertSame(GroupStatus::Published, $group->status);
    }

    public function test_a_route_must_be_airport_codes_and_flights_are_required(): void
    {
        $this->actingAs($this->admin)->post('/admin/groups', [
            'sector' => 'Multan to Jeddah',
            'legs' => [],
        ])->assertSessionHasErrors(['sector', 'legs', 'group_type_id', 'airline_id', 'total_seats']);
    }

    public function test_seats_cannot_drop_below_what_is_already_sold(): void
    {
        $group = FlightGroup::factory()->create(['total_seats' => 10]);
        app(BookingService::class)->hold(User::factory()->agent()->create(), $group, adults: 6);

        $this->actingAs($this->admin)->put("/admin/groups/{$group->id}", [
            ...$group->only(['group_type_id', 'airline_id', 'sector', 'adult_fare', 'child_fare', 'infant_fare']),
            'status' => 'published',
            'total_seats' => 5,
            'legs' => [['flight_number' => 'SV801', 'origin' => 'MUX', 'destination' => 'JED', 'departs_at' => now()->addDays(20)->format('Y-m-d\TH:i'), 'arrives_at' => now()->addDays(20)->addHours(4)->format('Y-m-d\TH:i')]],
        ])->assertSessionHasErrors('total_seats');

        $this->assertSame(10, $group->fresh()->total_seats);
    }

    public function test_the_list_shows_seats_left(): void
    {
        $group = FlightGroup::factory()->create(['total_seats' => 10]);
        app(BookingService::class)->hold(User::factory()->agent()->create(), $group, adults: 3);

        $this->actingAs($this->admin)->get('/admin/groups')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('admin/groups/Index')
                ->where('groups.data.0.seats_available', 7)
                ->where('groups.data.0.seats_held', 3));
    }

    public function test_the_payment_window_defaults_to_sixty_minutes(): void
    {
        $group = FlightGroup::factory()->create(['hold_minutes' => null]);

        $this->assertSame(60, $group->holdMinutes());
        $this->actingAs($this->admin)->get('/admin/groups')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('groups.data.0.hold_minutes', 60)
                ->where('defaultHoldMinutes', 60));
    }

    public function test_an_admin_changes_the_payment_window_from_the_list(): void
    {
        $group = FlightGroup::factory()->create(['hold_minutes' => null, 'total_seats' => 10]);
        $agent = User::factory()->agent()->create();
        $earlier = app(BookingService::class)->hold($agent, $group, adults: 1);

        $this->actingAs($this->admin)->from('/admin/groups')
            ->patch("/admin/groups/{$group->id}/hold-minutes", ['hold_minutes' => 120])
            ->assertRedirect('/admin/groups');

        $this->assertSame(120, $group->fresh()->hold_minutes);

        // New holds get the new window; the one already running keeps its deadline.
        $this->travelTo(now()->startOfMinute());
        $later = app(BookingService::class)->hold($agent, $group->fresh(), adults: 1);
        $this->assertEquals(now()->addMinutes(120), $later->hold_expires_at);
        $this->assertEquals($earlier->hold_expires_at, $earlier->fresh()->hold_expires_at);
    }

    public function test_the_payment_window_is_bounded_and_admin_only(): void
    {
        $group = FlightGroup::factory()->create(['hold_minutes' => 50]);

        $this->actingAs($this->admin)->patch("/admin/groups/{$group->id}/hold-minutes", ['hold_minutes' => 2])
            ->assertSessionHasErrors('hold_minutes');
        $this->actingAs($this->admin)->patch("/admin/groups/{$group->id}/hold-minutes", ['hold_minutes' => 5000])
            ->assertSessionHasErrors('hold_minutes');
        $this->actingAs(User::factory()->agent()->create())->patch("/admin/groups/{$group->id}/hold-minutes", ['hold_minutes' => 90])
            ->assertForbidden();

        $this->assertSame(50, $group->fresh()->hold_minutes);
    }

    public function test_approving_a_pending_agency_lets_it_book(): void
    {
        $agency = Agency::factory()->pending()->create();

        $this->actingAs($this->admin)->patch("/admin/agencies/{$agency->id}/status", ['status' => 'active'])->assertRedirect();

        $agency->refresh();
        $this->assertSame(AgencyStatus::Active, $agency->status);
        $this->assertSame($this->admin->id, $agency->approved_by);
    }
}
