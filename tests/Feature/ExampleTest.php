<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Root redirects to the dashboard, which in turn redirects a guest to login.
     */
    public function test_root_redirects_guests_to_login(): void
    {
        $response = $this->followingRedirects()->get('/');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page->component('Auth/Login'));
    }
}
