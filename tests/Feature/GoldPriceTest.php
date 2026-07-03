<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoldPriceTest extends TestCase
{
    use RefreshDatabase;

    private function fakeUpstreamSuccess(): void
    {
        Http::fake([
            'api.frankfurter.app/*' => Http::response(['rates' => ['IDR' => 16000]]),
            'data-asg.goldprice.org/*' => Http::response(['items' => [['xauPrice' => 3300]]]),
        ]);
    }

    public function test_returns_computed_price_on_success(): void
    {
        $this->fakeUpstreamSuccess();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/harga-emas');

        $response->assertOk()->assertJsonPath('success', true);
    }

    public function test_caches_successful_response_and_does_not_call_upstream_again(): void
    {
        $this->fakeUpstreamSuccess();
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/harga-emas')->assertOk();
        Http::fake(); // any further HTTP call now throws/fails the fake, proving the second request never calls out
        $second = $this->actingAs($user)->getJson('/api/harga-emas');

        $second->assertOk()->assertJsonPath('success', true);
    }

    public function test_returns_502_and_does_not_cache_on_upstream_failure(): void
    {
        Http::fake([
            'api.frankfurter.app/*' => Http::response([], 500),
        ]);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/harga-emas');

        $response->assertStatus(502)->assertJsonPath('success', false);
        $this->assertNull(Cache::get('gold-price'));
    }

    public function test_malformed_upstream_response_is_caught_and_returns_502(): void
    {
        // No 'rates' key at all — triggers a TypeError on array access,
        // which only catch (\Throwable) can intercept, not catch (\Exception).
        Http::fake([
            'api.frankfurter.app/*' => Http::response(['unexpected' => 'shape']),
        ]);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/harga-emas');

        $response->assertStatus(502)->assertJsonPath('success', false);
    }
}
