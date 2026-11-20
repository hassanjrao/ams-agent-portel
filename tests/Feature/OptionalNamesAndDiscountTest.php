<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\FlightGroup;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Notifications\PassengersChanged;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Agents can hold seats without passenger names and add or change them any time afterwards.
 * AMS can put a flat discount on a group, taken off every booking on it.
 */
class OptionalNamesAndDiscountTest extends TestCase
{
    use RefreshDatabase;

    private User $agent;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->agent = User::factory()->agent()->create();
        $this->admin = User::factory()->admin()->create();
    }

    /**
     * @return array<string, mixed>
     */
    private function passenger(string $type, string $given, string $surname = 'KHAN'): array
    {
        return ['type' => $type, 'title' => $type === 'adult' ? 'MR' : 'MSTR', 'given_name' => $given, 'surname' => $surname];
    }

    private function blank(string $type): array
    {
        return ['type' => $type, 'title' => 'MR', 'given_name' => '', 'surname' => '', 'passport_number' => null];
    }

    public function test_seats_can_be_held_without_any_names(): void
    {
        $group = FlightGroup::factory()->create();

        $this->actingAs($this->agent)->post("/groups/{$group->id}/bookings", [
            'adults' => 2, 'children' => 1, 'infants' => 0,
            'passengers' => [$this->blank('adult'), $this->blank('adult'), $this->blank('child')],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $booking = Booking::query()->sole();
        $this->assertSame(3, $booking->seats);
        $this->assertSame(0, $booking->passengers()->count());

        // No passenger list at all is fine too.
        $this->actingAs($this->agent)->post("/groups/{$group->id}/bookings", ['adults' => 1, 'children' => 0, 'infants' => 0])
            ->assertSessionHasNoErrors();
        $this->assertSame(2, Booking::query()->count());
    }

    public function test_some_names_now_and_the_rest_later(): void
    {
        $group = FlightGroup::factory()->create();

        $this->actingAs($this->agent)->post("/groups/{$group->id}/bookings", [
            'adults' => 2, 'children' => 0, 'infants' => 0,
            'passengers' => [$this->passenger('adult', 'Ali'), $this->blank('adult')],
        ])->assertSessionHasNoErrors();

        $booking = Booking::query()->sole();
        $this->assertSame(['ALI'], $booking->passengers()->pluck('given_name')->all());

        // A row with something in it still needs both names, and the error points at that row.
        $this->actingAs($this->agent)->put("/bookings/{$booking->id}/passengers", [
            'passengers' => [$this->passenger('adult', 'Ali'), ['type' => 'adult', 'title' => 'MR', 'given_name' => 'Sara', 'surname' => '']],
        ])->assertSessionHasErrors('passengers.1.surname');

        $this->actingAs($this->agent)->put("/bookings/{$booking->id}/passengers", [
            'passengers' => [$this->passenger('adult', 'Ali'), $this->passenger('adult', 'Sara')],
        ])->assertSessionHasNoErrors();
        $this->assertSame(['ALI', 'SARA'], $booking->passengers()->orderBy('id')->pluck('given_name')->all());
    }

    public function test_more_names_than_places_are_refused(): void
    {
        $group = FlightGroup::factory()->create();

        $this->actingAs($this->agent)->post("/groups/{$group->id}/bookings", [
            'adults' => 1, 'children' => 0, 'infants' => 0,
            'passengers' => [$this->passenger('adult', 'Ali'), $this->passenger('adult', 'Sara')],
        ])->assertSessionHasErrors('passengers');

        $this->assertSame(0, Booking::query()->count());
    }

    public function test_an_agent_changes_names_after_confirmation_and_ams_is_told(): void
    {
        $booking = app(BookingService::class)->hold($this->agent, FlightGroup::factory()->create(), adults: 1, passengers: [$this->passenger('adult', 'Ali')]);
        app(BookingService::class)->confirm($booking, $this->admin);
        app(BookingService::class)->updatePassengers($booking, [[...$this->passenger('adult', 'Ali'), 'ticket_number' => '065-1234567890']], $this->admin);

        $this->actingAs($this->agent)->get("/bookings/{$booking->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page->where('booking.can.edit_passengers', true));

        $this->actingAs($this->agent)->put("/bookings/{$booking->id}/passengers", [
            'passengers' => [$this->passenger('adult', 'Alee')],
        ])->assertSessionHasNoErrors();

        $passenger = $booking->passengers()->sole();
        $this->assertSame('ALEE', $passenger->given_name);
        $this->assertSame('065-1234567890', $passenger->ticket_number, 'the ticket AMS entered stays');
        Notification::assertSentTo($this->admin, PassengersChanged::class);
    }

    public function test_saving_the_same_names_tells_nobody_and_cancelled_bookings_are_locked(): void
    {
        $booking = app(BookingService::class)->hold($this->agent, FlightGroup::factory()->create(), adults: 1, passengers: [$this->passenger('adult', 'Ali')]);
        app(BookingService::class)->confirm($booking, $this->admin);

        $this->actingAs($this->agent)->put("/bookings/{$booking->id}/passengers", ['passengers' => [$this->passenger('adult', 'Ali')]])
            ->assertSessionHasNoErrors();
        Notification::assertNotSentTo($this->admin, PassengersChanged::class);

        app(BookingService::class)->cancel($booking, $this->admin, 'Plans changed');
        $this->actingAs($this->agent)->put("/bookings/{$booking->id}/passengers", ['passengers' => [$this->passenger('adult', 'Bilal')]])
            ->assertSessionHasErrors('passengers');
    }

    public function test_a_group_discount_comes_off_each_booking_and_the_charge(): void
    {
        $group = FlightGroup::factory()->create(['adult_fare' => 180000, 'child_fare' => 150000, 'discount_per_booking' => 5000]);

        $booking = app(BookingService::class)->hold($this->agent, $group, adults: 2, children: 1);

        $this->assertSame(5000, $booking->discount_amount);
        $this->assertSame(2 * 180000 + 150000 - 5000, $booking->total_amount);

        // A later change to the group's discount never reprices a booking already made.
        $group->update(['discount_per_booking' => 20000]);
        app(BookingService::class)->confirm($booking->fresh(), $this->admin);
        $this->assertSame(505000, (int) LedgerEntry::query()->where('booking_id', $booking->id)->value('debit'));

        $this->actingAs($this->agent)->get('/groups')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('groups.data.0.discount', 20000));
    }

    public function test_a_discount_never_makes_a_booking_negative(): void
    {
        $group = FlightGroup::factory()->create(['adult_fare' => 1000, 'discount_per_booking' => 5000]);

        $booking = app(BookingService::class)->hold($this->agent, $group, adults: 1);

        $this->assertSame(1000, $booking->discount_amount);
        $this->assertSame(0, $booking->total_amount);
    }

    public function test_an_admin_sets_the_discount_on_the_group_form(): void
    {
        $group = FlightGroup::factory()->create();
        $leg = ['flight_number' => 'SV801', 'origin' => 'MUX', 'destination' => 'JED', 'departs_at' => now()->addDays(20)->format('Y-m-d\TH:i'), 'arrives_at' => now()->addDays(20)->addHours(4)->format('Y-m-d\TH:i')];

        $this->actingAs($this->admin)->put("/admin/groups/{$group->id}", [
            ...$group->only(['group_type_id', 'airline_id', 'sector', 'total_seats', 'adult_fare', 'child_fare', 'infant_fare']),
            'status' => 'published', 'discount_per_booking' => 3000, 'legs' => [$leg],
        ])->assertSessionHasNoErrors();
        $this->assertSame(3000, $group->fresh()->discount_per_booking);

        $this->actingAs($this->admin)->put("/admin/groups/{$group->id}", [
            ...$group->only(['group_type_id', 'airline_id', 'sector', 'total_seats', 'adult_fare', 'child_fare', 'infant_fare']),
            'status' => 'published', 'discount_per_booking' => null, 'legs' => [$leg],
        ])->assertSessionHasNoErrors();
        $this->assertSame(0, $group->fresh()->discount_per_booking);
    }
}
