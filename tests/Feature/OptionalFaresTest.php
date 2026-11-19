<?php

namespace Tests\Feature;

use App\Models\Airline;
use App\Models\FlightGroup;
use App\Models\GroupType;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Child and infant fares are optional: no child fare means children pay the adult fare,
 * no infant fare means the group takes no infants. Flight dates must be real ones.
 */
class OptionalFaresTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $departs, array $overrides = []): array
    {
        return [
            'group_type_id' => GroupType::factory()->create()->id,
            'airline_id' => Airline::factory()->create()->id,
            'sector' => 'MUX-JED',
            'total_seats' => 20,
            'adult_fare' => 180000,
            'status' => 'published',
            'legs' => [['flight_number' => 'SV801', 'origin' => 'MUX', 'destination' => 'JED', 'departs_at' => $departs, 'arrives_at' => $departs]],
            ...$overrides,
        ];
    }

    public function test_a_group_can_be_saved_without_child_and_infant_fares(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/admin/groups', $this->payload(now()->addDays(15)->format('Y-m-d\TH:i')))
            ->assertSessionHasNoErrors()->assertRedirect('/admin/groups');

        $group = FlightGroup::query()->sole();
        $this->assertNull($group->child_fare);
        $this->assertNull($group->infant_fare);
        $this->assertSame(180000, $group->childFare());
        $this->assertFalse($group->takesInfants());
    }

    public function test_children_pay_the_adult_fare_and_infants_are_refused(): void
    {
        $group = FlightGroup::factory()->create(['adult_fare' => 180000, 'child_fare' => null, 'infant_fare' => null]);
        $agent = User::factory()->agent()->create();

        $booking = app(BookingService::class)->hold($agent, $group, adults: 2, children: 1);
        $this->assertSame(180000, $booking->child_fare);
        $this->assertSame(3 * 180000, $booking->total_amount);

        $this->expectException(ValidationException::class);
        app(BookingService::class)->hold($agent, $group, adults: 1, infants: 1);
    }

    public function test_agencies_see_the_child_price_and_no_infant_fare(): void
    {
        $group = FlightGroup::factory()->create(['adult_fare' => 180000, 'child_fare' => null, 'infant_fare' => null]);

        $this->actingAs(User::factory()->agent()->create())->get("/groups/{$group->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('group.child_fare', 180000)
                ->where('group.infant_fare', null));
    }

    public function test_a_new_group_cannot_depart_in_the_past_or_years_ahead(): void
    {
        $admin = User::factory()->admin()->create();

        foreach (['1983-12-13T10:00', now()->subDay()->format('Y-m-d\TH:i'), now()->addYears(3)->format('Y-m-d\TH:i')] as $date) {
            $this->actingAs($admin)->post('/admin/groups', $this->payload($date))->assertSessionHasErrors('legs.0.departs_at');
        }

        $this->assertSame(0, FlightGroup::query()->count());
    }
}
