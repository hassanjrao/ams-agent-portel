<?php

namespace Tests\Feature\Admin;

use App\Enums\GroupSourcing;
use App\Models\Airline;
use App\Models\FlightGroup;
use App\Models\GroupType;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Whether AMS bought a group directly or outsourced it, and from which supplier: admin-only records.
 */
class SuppliersTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    /**
     * @return array<string, mixed>
     */
    private function groupPayload(array $overrides = []): array
    {
        return [
            'group_type_id' => GroupType::factory()->create()->id,
            'airline_id' => Airline::factory()->create()->id,
            'sector' => 'MUX-JED',
            'total_seats' => 20,
            'adult_fare' => 90000,
            'child_fare' => 80000,
            'infant_fare' => 30000,
            'status' => 'published',
            'legs' => [['flight_number' => 'SV801', 'origin' => 'MUX', 'destination' => 'JED', 'departs_at' => now()->addDays(20)->format('Y-m-d\TH:i'), 'arrives_at' => now()->addDays(20)->addHours(4)->format('Y-m-d\TH:i')]],
            ...$overrides,
        ];
    }

    public function test_an_admin_manages_suppliers(): void
    {
        $this->actingAs($this->admin)->post('/admin/suppliers', [
            'name' => 'Al Haram Travels', 'contact_person' => 'Bilal', 'phone' => '0300 1234567', 'city' => 'Lahore', 'is_active' => true,
        ])->assertRedirect();

        $supplier = Supplier::query()->sole();
        $this->assertSame('Al Haram Travels', $supplier->name);

        $this->actingAs($this->admin)->put("/admin/suppliers/{$supplier->id}", [
            'name' => 'Al Haram Travels', 'phone' => 'call me', 'is_active' => false,
        ])->assertSessionHasErrors('phone');

        $this->actingAs($this->admin)->put("/admin/suppliers/{$supplier->id}", ['name' => 'Al Haram Travels', 'is_active' => false])->assertRedirect();
        $this->assertFalse($supplier->fresh()->is_active);

        $this->actingAs($this->admin)->get('/admin/suppliers')->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('admin/suppliers/Index')->has('suppliers', 1));
    }

    public function test_agents_cannot_see_or_change_suppliers(): void
    {
        $agent = User::factory()->agent()->create();

        $this->actingAs($agent)->get('/admin/suppliers')->assertForbidden();
        $this->actingAs($agent)->post('/admin/suppliers', ['name' => 'X'])->assertForbidden();
        $this->assertSame(0, Supplier::query()->count());
    }

    public function test_an_outsourced_group_needs_its_supplier(): void
    {
        $supplier = Supplier::factory()->create();

        $this->actingAs($this->admin)->post('/admin/groups', $this->groupPayload(['sourcing' => 'outsourced']))
            ->assertSessionHasErrors('supplier_id');

        $this->actingAs($this->admin)->post('/admin/groups', $this->groupPayload(['sourcing' => 'outsourced', 'supplier_id' => $supplier->id]))
            ->assertRedirect('/admin/groups');

        $group = FlightGroup::query()->sole();
        $this->assertSame(GroupSourcing::Outsourced, $group->sourcing);
        $this->assertSame($supplier->id, $group->supplier_id);
    }

    public function test_a_direct_group_drops_any_supplier_and_is_the_default(): void
    {
        $supplier = Supplier::factory()->create();

        $this->actingAs($this->admin)->post('/admin/groups', $this->groupPayload(['sourcing' => 'direct', 'supplier_id' => $supplier->id]));
        $this->actingAs($this->admin)->post('/admin/groups', $this->groupPayload());

        $this->assertSame(2, FlightGroup::query()->where('sourcing', GroupSourcing::Direct)->whereNull('supplier_id')->count());
    }

    public function test_the_groups_list_filters_by_source_and_supplier(): void
    {
        [$a, $b] = Supplier::factory()->count(2)->create();
        $direct = FlightGroup::factory()->create();
        $fromA = FlightGroup::factory()->create(['sourcing' => GroupSourcing::Outsourced, 'supplier_id' => $a->id]);
        $fromB = FlightGroup::factory()->create(['sourcing' => GroupSourcing::Outsourced, 'supplier_id' => $b->id]);

        $ids = fn (string $query) => $this->actingAs($this->admin)->get("/admin/groups{$query}")->viewData('page')['props']['groups']['data'];

        $this->assertEqualsCanonicalizing([$direct->id], array_column($ids('?sourcing=direct'), 'id'));
        $this->assertEqualsCanonicalizing([$fromA->id, $fromB->id], array_column($ids('?sourcing=outsourced'), 'id'));
        $this->assertSame([$fromB->id], array_column($ids("?sourcing=outsourced&supplier={$b->id}"), 'id'));

        $row = collect($ids(''))->firstWhere('id', $fromA->id);
        $this->assertSame('outsourced', $row['sourcing']);
        $this->assertSame($a->name, $row['supplier']);
    }

    public function test_agencies_never_see_where_a_group_came_from(): void
    {
        $supplier = Supplier::factory()->create(['name' => 'Secret Supplier Co']);
        $group = FlightGroup::factory()->create(['sourcing' => GroupSourcing::Outsourced, 'supplier_id' => $supplier->id]);
        $agent = User::factory()->agent()->create();

        foreach (['/groups', "/groups/{$group->id}", '/dashboard'] as $url) {
            $response = $this->actingAs($agent)->get($url)->assertOk();
            $this->assertStringNotContainsString('Secret Supplier Co', $response->getContent());
            $this->assertStringNotContainsString('outsourced', $response->getContent());
        }
    }
}
