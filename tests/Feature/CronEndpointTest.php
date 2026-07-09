<?php

namespace Tests\Feature;

use App\Models\RecurringTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Webhook /cron/recurring-apply menggantikan schedule:work di host free-tier
// yang scale-to-zero (Koyeb) — dipanggil harian oleh cron-job.org dengan token.
class CronEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_cron_endpoint_applies_recurring_transactions_with_valid_token(): void
    {
        config(['cron.token' => 'secret-token']);

        $user = User::factory()->create();
        RecurringTransaction::create([
            'user_id' => $user->id,
            'type' => 'expense',
            'kategori' => 'Makan',
            'jumlah' => 50000,
            'aktif' => true,
        ]);

        $this->get('/cron/recurring-apply?token=secret-token')
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'kategori' => 'Makan',
            'jumlah' => 50000,
        ]);
    }

    public function test_cron_endpoint_rejects_wrong_token(): void
    {
        config(['cron.token' => 'secret-token']);

        $this->get('/cron/recurring-apply?token=salah')->assertForbidden();
        $this->get('/cron/recurring-apply')->assertForbidden();
    }

    public function test_cron_endpoint_is_disabled_when_token_not_configured(): void
    {
        // Token kosong = endpoint mati total — jangan sampai lupa set CRON_TOKEN
        // malah membuka endpoint tanpa autentikasi.
        config(['cron.token' => null]);

        $this->get('/cron/recurring-apply')->assertForbidden();
        $this->get('/cron/recurring-apply?token=')->assertForbidden();
    }
}
