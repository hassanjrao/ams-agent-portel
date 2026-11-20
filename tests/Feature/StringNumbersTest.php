<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\FlightGroup;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;
use PDO;
use Tests\TestCase;

/**
 * The live server's MySQL driver returns every number as a string. Pages must still get real
 * numbers: "1" + "0" + "0" once showed a one-adult booking as "100 names still to add".
 */
class StringNumbersTest extends TestCase
{
    use RefreshDatabase;

    public function test_numbers_reach_the_pages_as_numbers_even_when_the_driver_returns_strings(): void
    {
        Notification::fake();
        $agent = User::factory()->agent()->create();
        $group = FlightGroup::factory()->create(['adult_fare' => 180000, 'discount_per_booking' => 2000]);
        $booking = app(BookingService::class)->hold($agent, $group, adults: 1);

        // Behave like the server's driver from here on.
        DB::connection()->getPdo()->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, true);

        $fresh = Booking::query()->find($booking->id);
        $this->assertSame(1, $fresh->adults);
        $this->assertSame(0, $fresh->children);
        $this->assertSame(178000, $fresh->total_amount);

        $this->actingAs($agent)->get("/bookings/{$booking->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('booking.adults', 1)
                ->where('booking.children', 0)
                ->where('booking.infants', 0)
                ->where('booking.total_amount', 178000)
                ->where('booking.discount_amount', 2000));

        $this->actingAs($agent)->get('/groups')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('groups.data.0.adult_fare', 180000)
                ->where('groups.data.0.seats_available', 9)
                ->where('groups.data.0.discount', 2000));
    }
}
