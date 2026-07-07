<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Root shows the onboarding carousel to guests.
     */
    public function test_root_shows_onboarding_to_guests(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page->component('Onboarding'));
    }

    /**
     * Finishing onboarding marks the session and lands on the login page.
     */
    public function test_onboarding_continue_leads_to_login(): void
    {
        $response = $this->followingRedirects()->post(route('onboarding.continue'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page->component('Auth/Login'));
    }

    /**
     * A guest who finished onboarding before (cookie) skips the carousel
     * server-side and can open the login page directly.
     */
    public function test_returning_guest_skips_onboarding_via_cookie(): void
    {
        $response = $this->withCookie('onboarding_seen', '1')->get('/');
        $response->assertRedirect(route('login'));

        $response = $this->withCookie('onboarding_seen', '1')->get('/login');
        $response->assertOk();
    }
}
