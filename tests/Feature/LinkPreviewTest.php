<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Links sent to agents over WhatsApp should preview with the AMS logo and say what the portal is.
 */
class LinkPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_register_link_previews_with_the_logo_image_and_a_description(): void
    {
        config(['app.url' => 'https://portal.amsglobal.pk']);

        $this->get('/register')->assertOk()
            ->assertSee('<meta property="og:title" content="Register your travel agency | AMS Global Agent Portal">', false)
            ->assertSee('<meta property="og:image" content="https://portal.amsglobal.pk/images/og-portal.jpg?v=1">', false)
            ->assertSee('property="og:description" content="Travel agents: register your agency', false);

        $this->assertFileExists(public_path('images/og-portal.jpg'));
    }

    public function test_other_pages_get_the_general_preview(): void
    {
        $this->get('/login')->assertSee('<meta property="og:title" content="Agent login | AMS Global Agent Portal">', false);
        $this->get('/forgot-password')->assertSee('<meta property="og:title" content="AMS Global Agent Portal">', false);
    }
}
