<?php

namespace Tests\Feature\Auth;

use App\Enums\AgencyStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\NewAgencyRegistered;
use App\Notifications\QueuedVerifyEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_an_agency_signs_up_and_waits_for_approval()
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();

        $response = $this->post(route('register.store'), [
            'agency_name' => 'Al-Madina Travel & Tours',
            'city' => 'Multan',
            'phone' => '0300 1234567',
            'address' => 'Hussain Agahi, Multan',
            'name' => 'Test Agent',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->assertSame(UserRole::Agent, $user->role);
        $this->assertSame('Al-Madina Travel & Tours', $user->agency->name);
        $this->assertSame('Test Agent', $user->agency->contact_person);
        $this->assertSame(AgencyStatus::Pending, $user->agency->status);

        Notification::assertSentTo($admin, NewAgencyRegistered::class);
    }

    public function test_a_password_needs_only_six_characters()
    {
        Notification::fake();
        $form = [
            'agency_name' => 'Simple Travels', 'city' => 'Lahore', 'phone' => '03001234567',
            'name' => 'Agent', 'email' => 'simple@example.com',
        ];

        $this->post(route('register.store'), [...$form, 'password' => 'abc12', 'password_confirmation' => 'abc12'])
            ->assertSessionHasErrors('password');

        // No capitals, symbols or digits needed (the rule is the same in every environment).
        $this->post(route('register.store'), [...$form, 'password' => 'simple', 'password_confirmation' => 'simple'])
            ->assertSessionHasNoErrors();
        $this->assertAuthenticated();
    }

    public function test_the_verification_email_is_queued_so_a_bad_address_cannot_break_sign_up()
    {
        Notification::fake();

        $this->post(route('register.store'), [
            'agency_name' => 'Test Travels', 'city' => 'Multan', 'phone' => '03001234567',
            'name' => 'Agent', 'email' => 'nobody@m.com',
            'password' => 'secret', 'password_confirmation' => 'secret',
        ])->assertRedirect(route('dashboard', absolute: false));

        $user = User::query()->where('email', 'nobody@m.com')->firstOrFail();
        Notification::assertSentTo($user, QueuedVerifyEmail::class);
        $this->assertInstanceOf(ShouldQueue::class, new QueuedVerifyEmail);
    }

    public function test_sign_up_needs_the_agency_details()
    {
        $this->post(route('register.store'), [
            'name' => 'Test Agent',
            'email' => 'test@example.com',
            'phone' => 'call me',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors(['agency_name', 'city', 'phone']);

        $this->assertGuest();
    }
}
