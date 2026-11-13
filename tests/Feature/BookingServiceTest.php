<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\LedgerEntryType;
use App\Enums\PaymentStatus;
use App\Models\Agency;
use App\Models\BankAccount;
use App\Models\FlightGroup;
use App\Models\Payment;
use App\Models\User;
use App\Services\BookingService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    private BookingService $bookings;

    private User $agent;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bookings = app(BookingService::class);
        $this->agent = User::factory()->agent()->create();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_a_hold_takes_seats_at_the_groups_price(): void
    {
        $group = FlightGroup::factory()->create(['total_seats' => 10, 'adult_fare' => 169000, 'child_fare' => 150000, 'infant_fare' => 40000]);

        $booking = $this->bookings->hold($this->agent, $group, adults: 3, children: 1, infants: 1);

        $this->assertSame(BookingStatus::OnHold, $booking->status);
        $this->assertSame(4, $booking->seats, 'infants travel on a lap and take no seat');
        $this->assertSame(3 * 169000 + 150000 + 40000, $booking->total_amount);
        $this->assertMatchesRegularExpression('/^AMS-\d{4}\d{5}$/', $booking->reference);
        $this->assertTrue($booking->hold_expires_at->between(now()->addMinutes(59), now()->addMinutes(61)));
        $this->assertSame(6, $group->fresh()->seatsAvailable());
    }

    public function test_it_refuses_more_seats_than_are_left(): void
    {
        $group = FlightGroup::factory()->create(['total_seats' => 5]);
        $this->bookings->hold($this->agent, $group, adults: 4);

        try {
            $this->bookings->hold($this->agent, $group, adults: 2);
            $this->fail('Overbooking was allowed.');
        } catch (ValidationException $e) {
            $this->assertSame('Only 1 seats are left in this group.', $e->errors()['adults'][0]);
        }

        $this->assertSame(1, $group->fresh()->seatsAvailable());
    }

    public function test_an_expired_hold_frees_its_seats_before_the_job_runs(): void
    {
        $group = FlightGroup::factory()->create(['total_seats' => 5]);
        $this->bookings->hold($this->agent, $group, adults: 5);
        $this->assertSame(0, $group->fresh()->seatsAvailable());

        $this->travel(61)->minutes();

        $this->assertSame(5, $group->fresh()->seatsAvailable());
        $this->assertSame(1, $this->bookings->expireHolds());
        $this->assertSame(0, $this->bookings->expireHolds());
    }

    public function test_confirming_charges_the_agency_and_cancelling_refunds_it(): void
    {
        $group = FlightGroup::factory()->create(['adult_fare' => 100000]);
        $booking = $this->bookings->hold($this->agent, $group, adults: 2);

        $this->bookings->confirm($booking, $this->admin, pnr: 'ABC123');

        $agency = $this->agent->agency;
        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);
        $this->assertSame(200000, $agency->balance());
        $this->assertSame(8, $group->fresh()->seatsAvailable());

        $this->bookings->cancel($booking, $this->admin, 'Visa refused');

        $this->assertSame(0, $agency->balance());
        $this->assertSame(10, $group->fresh()->seatsAvailable());
        $this->assertSame([LedgerEntryType::Booking, LedgerEntryType::Refund], $agency->ledgerEntries()->orderBy('id')->get()->pluck('type')->all());
    }

    public function test_an_agent_cannot_cancel_a_confirmed_booking(): void
    {
        $booking = $this->bookings->hold($this->agent, FlightGroup::factory()->create(), adults: 1);
        $this->bookings->confirm($booking, $this->admin);

        $this->expectException(ValidationException::class);
        $this->bookings->cancel($booking, $this->agent);
    }

    public function test_an_expired_hold_is_confirmed_only_if_its_seats_are_still_free(): void
    {
        $group = FlightGroup::factory()->create(['total_seats' => 3]);
        $late = $this->bookings->hold($this->agent, $group, adults: 2);
        $this->travel(61)->minutes();
        $this->bookings->hold(User::factory()->agent()->create(), $group, adults: 2);

        $this->expectException(ValidationException::class);
        $this->bookings->confirm($late, $this->admin);
    }

    public function test_pending_agencies_and_closed_groups_cannot_book(): void
    {
        $pending = User::factory()->agent(Agency::factory()->pending()->create())->create();

        try {
            $this->bookings->hold($pending, FlightGroup::factory()->create(), adults: 1);
            $this->fail('A pending agency booked.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('agency', $e->errors());
        }

        $this->expectException(ValidationException::class);
        $this->bookings->hold($this->agent, FlightGroup::factory()->draft()->create(), adults: 1);
    }

    public function test_an_approved_payment_is_credited_once(): void
    {
        $bank = BankAccount::query()->create(['bank_name' => 'Meezan Bank', 'account_title' => 'AMS Global', 'account_number' => '0123']);
        $payment = new Payment(['bank_account_id' => $bank->id, 'amount' => 50000, 'paid_on' => today(), 'receipt_path' => 'receipts/a.jpg']);
        $payment->agency_id = $this->agent->agency_id;
        $payment->user_id = $this->agent->id;
        $payment->save();

        $service = app(PaymentService::class);
        $service->approve($payment, $this->admin);

        $this->assertSame(PaymentStatus::Approved, $payment->fresh()->status);
        $this->assertSame(-50000, $this->agent->agency->balance());

        $this->expectException(ValidationException::class);
        $service->approve($payment, $this->admin);
    }
}
