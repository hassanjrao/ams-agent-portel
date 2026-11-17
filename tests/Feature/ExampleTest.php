<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_a_successful_response()
    {
        // The portal has no public home page: the address goes to the dashboard (or login).
        $this->get(route('home'))->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertRedirect(route('login'));
    }
}
