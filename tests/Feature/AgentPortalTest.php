<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\GroupStatus;
use App\Enums\LedgerEntryType;
use App\Enums\PaymentStatus;
use App\Models\Agency;
use App\Models\BankAccount;
use App\Models\Booking;
use App\Models\FlightGroup;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\BookingConfirmed;
use App\Notifications\PaymentReviewed;
use App\Notifications\PaymentSubmitted;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AgentPortalTest extends TestCase
{
    use RefreshDatabase;

    private User $agent;

    private User $admin;

    private FlightGroup $group;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Storage::fake('local');

        $this->agent = User::factory()->agent()->create();
        $this->admin = User::factory()->admin()->create();
        $this->group = FlightGroup::factory()->create(['total_seats' => 10, 'adult_fare' => 175000, 'child_fare' => 160000, 'infant_fare' => 45000]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function names(int $adults, int $children = 0): array
    {
        $list = [];
        for ($i = 0; $i < $adults; $i++) {
            $list[] = ['type' => 'adult', 'title' => 'MR', 'given_name' => "ali {$this->letter($i)}", 'surname' => 'khan', 'passport_number' => 'ab123456'.$i];
        }
        for ($i = 0; $i < $children; $i++) {
            $list[] = ['type' => 'child', 'title' => 'MSTR', 'given_name' => "omar {$this->letter($i)}", 'surname' => 'khan', 'date_of_birth' => '2018-05-01'];
        }

        return $list;
    }

    private function letter(int $i): string
    {
        return chr(ord('a') + $i);
    }

    private function book(int $adults = 2, int $children = 0): Booking
    {
        $this->actingAs($this->agent)->post("/groups/{$this->group->id}/bookings", [
            'adults' => $adults, 'children' => $children, 'infants' => 0,
            'passengers' => $this->names($adults, $children),
        ])->assertSessionHasNoErrors();

        return Booking::query()->latest('id')->firstOrFail();
    }

    public function test_every_agent_page_renders(): void
    {
        $booking = $this->book();
        BankAccount::query()->create(['bank_name' => 'Meezan Bank', 'account_title' => 'AMS', 'account_number' => '0101']);

        foreach (['/dashboard', '/groups', '/groups?search=jed', "/groups/{$this->group->id}", '/bookings', '/bookings?status=on_hold', "/bookings/{$booking->id}", '/payments', '/ledger', '/ledger?from=2026-01-01&to=2026-12-31'] as $url) {
            $this->actingAs($this->agent)->get($url)->assertOk();
        }
    }

    public function test_an_agent_holds_seats_with_passenger_names(): void
    {
        $booking = $this->book(adults: 2, children: 1);

        $this->assertSame(BookingStatus::OnHold, $booking->status);
        $this->assertSame(2 * 175000 + 160000, $booking->total_amount);
        $this->assertSame(3, $booking->passengers()->count());
        // Stored as airlines take them: upper case, adults before children.
        $first = $booking->passengers()->orderBy('id')->first();
        $this->assertSame('ALI A', $first->given_name);
        $this->assertSame('AB1234560', $first->passport_number);
        $this->assertSame(7, $this->group->fresh()->seatsAvailable());
    }

    public function test_names_must_match_the_passenger_counts(): void
    {
        $this->actingAs($this->agent)->post("/groups/{$this->group->id}/bookings", [
            'adults' => 2, 'children' => 0, 'infants' => 0,
            'passengers' => $this->names(1),
        ])->assertSessionHasErrors('passengers');

        $this->actingAs($this->agent)->post("/groups/{$this->group->id}/bookings", [
            'adults' => 1, 'children' => 0, 'infants' => 0,
            'passengers' => [['type' => 'adult', 'title' => 'MR', 'given_name' => 'علی', 'surname' => 'Khan']],
        ])->assertSessionHasErrors('passengers.0.given_name');

        $this->assertSame(0, Booking::query()->count());
    }

    public function test_a_pending_agency_can_look_but_not_book(): void
    {
        $pending = User::factory()->agent(Agency::factory()->pending()->create())->create();

        $this->actingAs($pending)->get('/groups')->assertOk();
        $this->actingAs($pending)->post("/groups/{$this->group->id}/bookings", [
            'adults' => 1, 'children' => 0, 'infants' => 0, 'passengers' => $this->names(1),
        ])->assertSessionHasErrors('agency');
    }

    public function test_draft_groups_are_hidden_from_agents(): void
    {
        $draft = FlightGroup::factory()->draft()->create();

        $this->actingAs($this->agent)->get("/groups/{$draft->id}")->assertNotFound();
        $this->actingAs($this->agent)->get('/groups')->assertInertia(fn (AssertableInertia $page) => $page
            ->component('agent/groups/Index')
            ->has('groups.data', 1)
            ->where('groups.data.0.id', $this->group->id)
            ->missing('groups.data.0.supplier_pnr'));
    }

    public function test_an_agency_only_sees_its_own_bookings_and_receipts(): void
    {
        $booking = $this->book();
        $payment = $this->pay(50000);
        $other = User::factory()->agent()->create();

        $this->actingAs($other)->get("/bookings/{$booking->id}")->assertForbidden();
        $this->actingAs($other)->post("/bookings/{$booking->id}/cancel")->assertForbidden();
        $this->actingAs($other)->get("/payments/{$payment->id}/receipt")->assertForbidden();

        $this->actingAs($this->agent)->get("/payments/{$payment->id}/receipt")->assertOk();
        $this->actingAs($this->admin)->get("/payments/{$payment->id}/receipt")->assertOk();
    }

    public function test_names_can_be_changed_only_while_the_hold_runs(): void
    {
        $booking = $this->book(adults: 1);
        $rename = ['passengers' => [['type' => 'adult', 'title' => 'MRS', 'given_name' => 'Ayesha', 'surname' => 'Bibi']]];

        $this->actingAs($this->agent)->put("/bookings/{$booking->id}/passengers", $rename)->assertSessionHasNoErrors();
        $this->assertSame('AYESHA', $booking->passengers()->first()->given_name);

        app(BookingService::class)->confirm($booking, $this->admin);

        $this->actingAs($this->agent)->put("/bookings/{$booking->id}/passengers", $rename)->assertSessionHasErrors('passengers');
    }

    public function test_an_agent_releases_its_own_hold(): void
    {
        $booking = $this->book(adults: 3);

        $this->actingAs($this->agent)->post("/bookings/{$booking->id}/cancel")->assertSessionHasNoErrors();

        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);
        $this->assertSame(10, $this->group->fresh()->seatsAvailable());
    }

    private function pay(int $amount, ?Booking $booking = null): Payment
    {
        $bank = BankAccount::query()->firstOrCreate(['account_number' => '0101'], ['bank_name' => 'Meezan Bank', 'account_title' => 'AMS']);

        $this->actingAs($this->agent)->post('/payments', [
            'bank_account_id' => $bank->id,
            'amount' => $amount,
            'paid_on' => today()->toDateString(),
            'booking_id' => $booking?->id,
            'description' => 'IBFT 123',
            'receipt' => UploadedFile::fake()->image('receipt.jpg'),
        ])->assertSessionHasNoErrors();

        return Payment::query()->latest('id')->firstOrFail();
    }

    public function test_a_payment_is_credited_once_ams_approves_it(): void
    {
        $booking = $this->book(adults: 2);
        $payment = $this->pay(350000, $booking);

        $this->assertSame(PaymentStatus::Pending, $payment->status);
        Storage::disk('local')->assertExists($payment->receipt_path);
        Notification::assertSentTo($this->admin, PaymentSubmitted::class);
        $this->assertSame(0, LedgerEntry::query()->count(), 'nothing is credited before approval');

        $this->actingAs($this->agent)->post("/admin/payments/{$payment->id}/approve")->assertForbidden();
        $this->actingAs($this->admin)->post("/admin/payments/{$payment->id}/approve", ['remarks' => 'Seen in Meezan'])->assertSessionHasNoErrors();
        // A double click can't credit it twice.
        $this->actingAs($this->admin)->post("/admin/payments/{$payment->id}/approve")->assertSessionHasErrors('status');

        $this->assertSame(PaymentStatus::Approved, $payment->fresh()->status);
        $this->assertSame(-350000, $this->agent->agency->balance());
        Notification::assertSentTo($this->agent, PaymentReviewed::class);
    }

    public function test_a_rejected_payment_needs_a_reason_and_credits_nothing(): void
    {
        $payment = $this->pay(10000);

        $this->actingAs($this->admin)->post("/admin/payments/{$payment->id}/reject")->assertSessionHasErrors('remarks');
        $this->actingAs($this->admin)->post("/admin/payments/{$payment->id}/reject", ['remarks' => 'Not received'])->assertSessionHasNoErrors();

        $this->assertSame(PaymentStatus::Rejected, $payment->fresh()->status);
        $this->assertSame(0, $this->agent->agency->balance());
    }

    public function test_a_receipt_must_be_a_photo_or_pdf_and_bookings_must_be_the_agencys_own(): void
    {
        $bank = BankAccount::query()->create(['bank_name' => 'Meezan Bank', 'account_title' => 'AMS', 'account_number' => '0101']);
        $othersBooking = app(BookingService::class)->hold(User::factory()->agent()->create(), $this->group, adults: 1);

        $this->actingAs($this->agent)->post('/payments', [
            'bank_account_id' => $bank->id,
            'amount' => 1000,
            'paid_on' => today()->toDateString(),
            'booking_id' => $othersBooking->id,
            'receipt' => UploadedFile::fake()->create('receipt.exe', 10, 'application/octet-stream'),
        ])->assertSessionHasErrors(['receipt', 'booking_id']);
    }

    public function test_ams_confirms_a_booking_with_its_pnr_and_charges_the_agency(): void
    {
        $booking = $this->book(adults: 2);

        $this->actingAs($this->admin)->post("/admin/bookings/{$booking->id}/confirm", ['pnr' => 'abc123'])->assertSessionHasNoErrors();

        $booking->refresh();
        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertSame('ABC123', $booking->pnr);
        $this->assertSame(350000, $this->agent->agency->balance());
        Notification::assertSentTo($this->agent, BookingConfirmed::class);

        // AMS adds ticket numbers after issuing.
        $passengers = $booking->passengers()->orderBy('id')->get()->map(fn ($p) => [
            'type' => $p->type->value, 'title' => $p->title, 'given_name' => $p->given_name, 'surname' => $p->surname,
            'ticket_number' => '065-'.$p->id,
        ])->all();
        $this->actingAs($this->admin)->put("/admin/bookings/{$booking->id}/passengers", ['passengers' => $passengers, 'pnr' => 'ABC123'])->assertSessionHasNoErrors();
        $this->assertNotNull($booking->passengers()->first()->ticket_number);
    }

    public function test_the_statement_runs_a_balance_from_the_opening(): void
    {
        $this->travelTo(now()->subMonths(5));
        $old = $this->book(adults: 1);
        app(BookingService::class)->confirm($old, $this->admin);
        $this->travelBack();

        $booking = $this->book(adults: 2);
        app(BookingService::class)->confirm($booking, $this->admin);
        $payment = $this->pay(100000);
        $this->actingAs($this->admin)->post("/admin/payments/{$payment->id}/approve");

        $this->actingAs($this->agent)->get('/ledger')->assertInertia(fn (AssertableInertia $page) => $page
            ->component('agent/Ledger')
            ->where('statement.opening', 175000)
            ->has('statement.entries', 2)
            ->where('statement.entries.0.balance', 175000 + 350000)
            ->where('statement.entries.1.balance', 175000 + 350000 - 100000)
            ->where('statement.closing', 425000));
    }

    public function test_ams_posts_an_adjustment(): void
    {
        $agency = $this->agent->agency;

        $this->actingAs($this->admin)->post("/admin/agencies/{$agency->id}/adjustments", [
            'direction' => 'charge', 'amount' => 5000, 'description' => 'Name change penalty',
        ])->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->post("/admin/agencies/{$agency->id}/adjustments", [
            'direction' => 'credit', 'amount' => 2000, 'description' => 'Goodwill',
        ])->assertSessionHasNoErrors();

        $this->assertSame(3000, $agency->balance());
        $this->assertSame(2, LedgerEntry::query()->where('type', LedgerEntryType::Adjustment)->count());
        $this->actingAs($this->agent)->post("/admin/agencies/{$agency->id}/adjustments", [
            'direction' => 'credit', 'amount' => 999999, 'description' => 'Free money',
        ])->assertForbidden();
    }

    public function test_admins_land_on_the_admin_side_and_agents_on_theirs(): void
    {
        $this->actingAs($this->admin)->get('/dashboard')->assertRedirect('/admin');
        $this->actingAs($this->agent)->get('/admin')->assertForbidden();
        $this->actingAs($this->agent)->get('/dashboard')->assertInertia(fn (AssertableInertia $page) => $page
            ->component('agent/Dashboard')
            ->where('myAgency.status', 'active')
            ->where('myAgency.balance', 0));
    }

    public function test_the_agency_header_is_not_shadowed_by_an_agency_page(): void
    {
        $agency = $this->agent->agency;

        // AMS's view of an agency passes its own "agency" prop; the header's prop must stay separate.
        $this->actingAs($this->admin)->get("/admin/agencies/{$agency->id}/ledger")->assertInertia(fn (AssertableInertia $page) => $page
            ->where('agency.id', $agency->id)
            ->where('myAgency', null));
    }

    public function test_a_closed_group_cannot_be_booked(): void
    {
        $this->group->update(['status' => GroupStatus::Closed]);

        $this->actingAs($this->agent)->get("/groups/{$this->group->id}")->assertNotFound();
        $this->actingAs($this->agent)->post("/groups/{$this->group->id}/bookings", [
            'adults' => 1, 'children' => 0, 'infants' => 0, 'passengers' => $this->names(1),
        ])->assertSessionHasErrors('group');
    }
}
