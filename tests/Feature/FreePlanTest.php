<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\BankAccount;
use App\Models\Booking;
use App\Models\FlightGroup;
use App\Models\Payment;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Only the first agencies to sign up (5 by default) may act; later ones can look but not act.
 */
class FreePlanTest extends TestCase
{
    use RefreshDatabase;

    private FlightGroup $group;

    /** @var list<User> */
    private array $agents = [];

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Storage::fake('local');
        config(['portal.free_agency_limit' => 5]);

        $this->group = FlightGroup::factory()->create(['total_seats' => 50]);
        // Six agencies sign up, in order: the sixth is beyond the free plan.
        for ($i = 0; $i < 6; $i++) {
            $this->agents[] = User::factory()->agent(Agency::factory()->create())->create();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function booking(): array
    {
        return [
            'adults' => 1, 'children' => 0, 'infants' => 0,
            'passengers' => [['type' => 'adult', 'title' => 'MR', 'given_name' => 'Ali', 'surname' => 'Khan']],
        ];
    }

    public function test_the_first_five_agencies_can_book(): void
    {
        $this->actingAs($this->agents[4])->post("/groups/{$this->group->id}/bookings", $this->booking())->assertSessionHasNoErrors();

        $this->assertSame(1, Booking::query()->count());
    }

    public function test_the_sixth_agency_can_log_in_and_look_but_not_act(): void
    {
        $locked = $this->agents[5];

        $this->actingAs($locked)->get('/dashboard')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->where('myAgency.locked', true));
        $this->actingAs($locked)->get('/groups')->assertOk();
        $this->actingAs($locked)->get("/groups/{$this->group->id}")->assertOk();
        $this->actingAs($locked)->get('/bookings')->assertOk();
        $this->actingAs($locked)->get('/payments')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->where('canPay', false));

        $this->actingAs($locked)->from("/groups/{$this->group->id}")
            ->post("/groups/{$this->group->id}/bookings", $this->booking())
            ->assertRedirect("/groups/{$this->group->id}");

        $bank = BankAccount::query()->create(['bank_name' => 'Meezan Bank', 'account_title' => 'AMS', 'account_number' => '0101']);
        $this->actingAs($locked)->post('/payments', [
            'bank_account_id' => $bank->id, 'amount' => 1000, 'paid_on' => today()->toDateString(),
            'receipt' => UploadedFile::fake()->image('receipt.jpg'),
        ]);

        $this->assertSame(0, Booking::query()->count());
        $this->assertSame(0, Payment::query()->count());
        // Settings stay open: it can still change its own password and profile.
        $this->actingAs($locked)->get('/settings/profile')->assertOk();
    }

    public function test_the_services_refuse_a_locked_agency_too(): void
    {
        $this->expectException(ValidationException::class);

        app(BookingService::class)->hold($this->agents[5], $this->group, adults: 1);
    }

    public function test_admins_are_told_the_free_limit_is_reached(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('freePlan.limit', 5)
            ->where('freePlan.agencies', 6)
            ->where('freePlan.locked', 1)
            ->where('freePlan.reached', true));

        $this->actingAs($admin)->get('/admin/agencies')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('agencies.data', fn ($rows) => collect($rows)->where('over_free_plan', true)->pluck('id')->all() === [$this->agents[5]->agency_id]));
    }

    public function test_agents_never_see_the_admin_notice_and_admins_see_none_below_the_limit(): void
    {
        $this->actingAs($this->agents[0])->get('/dashboard')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('freePlan', null)
            ->where('myAgency.locked', false));

        config(['portal.free_agency_limit' => 10]);
        $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('freePlan.reached', false));
    }

    public function test_setting_the_limit_to_zero_lifts_it(): void
    {
        config(['portal.free_agency_limit' => 0]);

        $this->actingAs($this->agents[5])->post("/groups/{$this->group->id}/bookings", $this->booking())->assertSessionHasNoErrors();

        $this->assertSame(1, Booking::query()->count());
        $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('freePlan', null));
    }
}
