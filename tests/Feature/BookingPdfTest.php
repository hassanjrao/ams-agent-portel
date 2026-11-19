<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\FlightGroup;
use App\Models\User;
use App\Services\BookingPdf;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * A booking as a PDF, with or without the fares, for AMS and for the agency that made it.
 */
class BookingPdfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function test_admin_and_the_agency_download_the_pdf_others_cannot(): void
    {
        $agency = Agency::factory()->create(['name' => 'Madina Travels']);
        $agent = User::factory()->agent($agency)->create(['name' => 'Ahmad Saleem']);
        $booking = app(BookingService::class)->hold($agent, FlightGroup::factory()->create(), adults: 2);

        $this->get("/bookings/{$booking->id}/pdf")->assertRedirect('/login');

        foreach ([User::factory()->admin()->create(), $agent] as $user) {
            $response = $this->actingAs($user)->get("/bookings/{$booking->id}/pdf")->assertOk();
            $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
            $this->assertStringStartsWith('%PDF', $response->getContent());
            $date = $booking->flightGroup->departure_date->format('d-M-Y');
            $this->assertStringContainsString("Madina-Travels_MUX-JED-MUX_{$date}_{$booking->reference}.pdf", $response->headers->get('Content-Disposition'));
        }

        $this->actingAs($agent)->get("/bookings/{$booking->id}/pdf?price=0")->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="Madina-Travels_MUX-JED-MUX_'.$booking->flightGroup->departure_date->format('d-M-Y')."_{$booking->reference}_without-price.pdf\"");

        $this->actingAs(User::factory()->agent()->create())->get("/bookings/{$booking->id}/pdf")->assertForbidden();
    }

    public function test_the_pdf_shows_the_agency_and_agent_and_hides_fares_when_asked(): void
    {
        $agency = Agency::factory()->create(['name' => 'Madina Travels']);
        $agent = User::factory()->agent($agency)->create(['name' => 'Ahmad Saleem']);
        $booking = app(BookingService::class)->hold($agent, FlightGroup::factory()->create(['adult_fare' => 183000]), adults: 2);

        $with = view('pdf.booking', app(BookingPdf::class)->data($booking, true))->render();
        $without = view('pdf.booking', app(BookingPdf::class)->data($booking, false))->render();

        foreach ([$with, $without] as $html) {
            $this->assertStringContainsString('Madina Travels', $html);
            $this->assertStringContainsString('Ahmad Saleem', $html);
            $this->assertStringContainsString($booking->reference, $html);
        }
        $this->assertStringContainsString('PKR 366,000', $with);
        $this->assertStringContainsString('Total', $with);
        $this->assertStringNotContainsString('PKR 366,000', $without);
        $this->assertStringNotContainsString('PKR 183,000', $without);
        $this->assertStringNotContainsString('Total', $without);
    }
}
