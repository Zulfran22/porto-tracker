<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_keuangan_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/keuangan')->assertOk();
    }

    public function test_transaction_can_be_created(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/keuangan', [
            'tanggal' => '2026-07-01',
            'type' => 'expense',
            'kategori' => 'Makan',
            'jumlah' => 25000,
            'catatan' => 'makan siang',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'kategori' => 'Makan',
            'jumlah' => 25000,
        ]);
    }

    public function test_transaction_type_must_be_income_or_expense(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/keuangan', [
            'tanggal' => '2026-07-01',
            'type' => 'not-a-type',
            'kategori' => 'Makan',
            'jumlah' => 25000,
        ])->assertSessionHasErrors('type');
    }

    public function test_transaction_can_be_deleted_only_by_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $transaction = Transaction::create([
            'user_id' => $owner->id,
            'tanggal' => '2026-07-01',
            'type' => 'expense',
            'kategori' => 'Makan',
            'jumlah' => 25000,
        ]);

        $this->actingAs($other)->delete("/keuangan/{$transaction->id}")->assertForbidden();
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id]);

        $this->actingAs($owner)->delete("/keuangan/{$transaction->id}");
        $this->assertSoftDeleted('transactions', ['id' => $transaction->id]);
    }

    public function test_budget_can_be_stored_and_updated_without_duplicating(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/keuangan/budget', ['kategori' => 'Makan', 'limit_jumlah' => 500000]);
        $this->actingAs($user)->post('/keuangan/budget', ['kategori' => 'Makan', 'limit_jumlah' => 750000]);

        $this->assertSame(1, Budget::where('user_id', $user->id)->where('kategori', 'Makan')->count());
        $this->assertDatabaseHas('budgets', ['user_id' => $user->id, 'kategori' => 'Makan', 'limit_jumlah' => 750000]);
    }

    public function test_budget_can_be_destroyed(): void
    {
        $user = User::factory()->create();
        Budget::create(['user_id' => $user->id, 'kategori' => 'Makan', 'limit_jumlah' => 500000]);

        $this->actingAs($user)->delete('/keuangan/budget/Makan');

        $this->assertDatabaseMissing('budgets', ['user_id' => $user->id, 'kategori' => 'Makan']);
    }
}
