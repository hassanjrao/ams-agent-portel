<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\FlightGroup;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Deleting a group hides it from the portal (a soft delete); bookings made on it keep their group.
 */
class DeleteGroupTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_an_admin_deletes_a_group_and_it_disappears_but_stays_in_the_database(): void
    {
        $group = FlightGroup::factory()->create();
        $agent = User::factory()->agent()->create();

        $this->actingAs($this->admin)->delete("/admin/groups/{$group->id}")->assertRedirect('/admin/groups');

        $this->assertSoftDeleted($group);
        $this->actingAs($this->admin)->get('/admin/groups')->assertInertia(fn (AssertableInertia $page) => $page->has('groups.data', 0));
        $this->actingAs($this->admin)->get("/admin/groups/{$group->id}/edit")->assertNotFound();
        $this->actingAs($agent)->get('/groups')->assertInertia(fn (AssertableInertia $page) => $page->has('groups.data', 0));
        $this->actingAs($agent)->get("/groups/{$group->id}")->assertNotFound();
    }

    public function test_a_group_with_live_bookings_cannot_be_deleted(): void
    {
        $group = FlightGroup::factory()->create();
        app(BookingService::class)->hold(User::factory()->agent()->create(), $group, adults: 1);

        $this->actingAs($this->admin)->from('/admin/groups')->delete("/admin/groups/{$group->id}")
            ->assertSessionHasErrors('group');

        $this->assertNotSoftDeleted($group);
    }

    public function test_old_bookings_keep_their_group_after_it_is_deleted(): void
    {
        $group = FlightGroup::factory()->create(['sector' => 'MUX-JED-MUX']);
        $agent = User::factory()->agent()->create();
        $booking = app(BookingService::class)->hold($agent, $group, adults: 1);
        app(BookingService::class)->cancel($booking, $this->admin, 'Agency changed plans');

        $this->actingAs($this->admin)->delete("/admin/groups/{$group->id}")->assertRedirect('/admin/groups');

        $this->assertSoftDeleted($group);
        $this->assertSame('MUX-JED-MUX', Booking::query()->find($booking->id)->flightGroup->sector);
        $this->actingAs($this->admin)->get("/admin/bookings/{$booking->id}")->assertOk();
        $this->actingAs($this->admin)->get('/admin/bookings')->assertOk();
        $this->actingAs($agent)->get("/bookings/{$booking->id}")->assertOk();
        $this->actingAs($agent)->get('/bookings')->assertOk();
    }

    public function test_agents_cannot_delete_groups(): void
    {
        $group = FlightGroup::factory()->create();

        $this->actingAs(User::factory()->agent()->create())->delete("/admin/groups/{$group->id}")->assertForbidden();

        $this->assertNotSoftDeleted($group);
    }
}
