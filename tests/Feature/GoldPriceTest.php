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

    private function fakePegadaianSuccess(): void
    {
        Http::fake([
            'pegadaian.co.id/*' => Http::response([
                'data' => [
                    'tglBerlaku' => '2026-07-28',
                    'hargaBeli' => 23780,
                    'hargaJual' => 25040,
                    'unit' => '0.01',
                ],
            ]),
        ]);
    }

    private function fakeSpotSuccess(): void
    {
        Http::fake([
            'api.frankfurter.app/*' => Http::response(['rates' => ['IDR' => 16000]]),
            'data-asg.goldprice.org/*' => Http::response(['items' => [['xauPrice' => 3300]]]),
        ]);
    }

    public function test_returns_pegadaian_price_on_success(): void
    {
        $this->fakePegadaianSuccess();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/harga-emas');

        // hargaJual 25040 / unit 0.01 = 2.504.000 per gram (harga BELI
        // customer); hargaBeli 23780 / 0.01 = 2.378.000 (buyback).
        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('source', 'pegadaian')
            ->assertJsonPath('harga_jual', 2504000)
            ->assertJsonPath('harga_beli', 2378000);
    }

    public function test_caches_successful_response_and_does_not_call_upstream_again(): void
    {
        $this->fakePegadaianSuccess();
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/harga-emas')->assertOk();
        Http::fake(); // any further HTTP call now throws/fails the fake, proving the second request never calls out
        $second = $this->actingAs($user)->getJson('/api/harga-emas');

        $second->assertOk()->assertJsonPath('success', true)->assertJsonPath('source', 'pegadaian');
    }

    public function test_falls_back_to_spot_estimate_when_pegadaian_fails(): void
    {
        Http::fake([
            'pegadaian.co.id/*' => Http::response([], 500),
            'api.frankfurter.app/*' => Http::response(['rates' => ['IDR' => 16000]]),
            'data-asg.goldprice.org/*' => Http::response(['items' => [['xauPrice' => 3300]]]),
        ]);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/harga-emas');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('source', 'estimasi_spot')
            ->assertJsonPath('harga_beli', null);
    }

    public function test_returns_502_and_does_not_cache_when_pegadaian_and_spot_both_fail(): void
    {
        Http::fake([
            'pegadaian.co.id/*' => Http::response([], 500),
            'api.frankfurter.app/*' => Http::response([], 500),
        ]);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/harga-emas');

        $response->assertStatus(502)->assertJsonPath('success', false);
        $this->assertNull(Cache::get('gold-price'));
    }

    public function test_malformed_pegadaian_response_falls_back_to_spot_estimate(): void
    {
        // No 'data' key at all — triggers a TypeError on array access,
        // which only catch (\Throwable) can intercept, not catch (\Exception).
        Http::fake([
            'pegadaian.co.id/*' => Http::response(['unexpected' => 'shape']),
        ]);
        $this->fakeSpotSuccess();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/harga-emas');

        $response->assertOk()->assertJsonPath('source', 'estimasi_spot');
    }
}
