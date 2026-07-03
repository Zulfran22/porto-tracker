<?php

namespace Tests\Feature;

use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_recurring_transaction_can_be_created(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/keuangan/recurring', [
            'type' => 'expense',
            'kategori' => 'Makan',
            'jumlah' => 25000,
            'catatan' => 'Langganan makan siang',
        ])->assertRedirect();

        $this->assertDatabaseHas('recurring_transactions', [
            'user_id' => $user->id,
            'kategori' => 'Makan',
            'aktif' => true,
        ]);
    }

    public function test_recurring_transaction_can_be_toggled_only_by_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $recurring = RecurringTransaction::create([
            'user_id' => $owner->id, 'type' => 'expense', 'kategori' => 'Makan', 'jumlah' => 25000,
        ]);

        $this->actingAs($other)->patch("/keuangan/recurring/{$recurring->id}/toggle")->assertForbidden();
        $this->assertTrue($recurring->fresh()->aktif);

        $this->actingAs($owner)->patch("/keuangan/recurring/{$recurring->id}/toggle");
        $this->assertFalse($recurring->fresh()->aktif);
    }

    public function test_recurring_transaction_can_be_deleted_only_by_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $recurring = RecurringTransaction::create([
            'user_id' => $owner->id, 'type' => 'expense', 'kategori' => 'Makan', 'jumlah' => 25000,
        ]);

        $this->actingAs($other)->delete("/keuangan/recurring/{$recurring->id}")->assertForbidden();
        $this->actingAs($owner)->delete("/keuangan/recurring/{$recurring->id}");

        $this->assertDatabaseMissing('recurring_transactions', ['id' => $recurring->id]);
    }

    public function test_apply_creates_a_transaction_for_each_active_recurring(): void
    {
        $user = User::factory()->create();
        RecurringTransaction::create(['user_id' => $user->id, 'type' => 'expense', 'kategori' => 'Makan', 'jumlah' => 25000, 'aktif' => true]);
        RecurringTransaction::create(['user_id' => $user->id, 'type' => 'income', 'kategori' => 'Gaji', 'jumlah' => 5000000, 'aktif' => true]);
        RecurringTransaction::create(['user_id' => $user->id, 'type' => 'expense', 'kategori' => 'Belanja', 'jumlah' => 10000, 'aktif' => false]);

        $this->actingAs($user)->post('/keuangan/recurring/apply');

        // Only the two active recurrings are applied — the inactive one is skipped.
        $this->assertSame(2, Transaction::where('user_id', $user->id)->whereNotNull('recurring_transaction_id')->count());
    }

    public function test_apply_is_idempotent_and_skips_recurrings_already_applied_today(): void
    {
        $user = User::factory()->create();
        $recurring = RecurringTransaction::create(['user_id' => $user->id, 'type' => 'expense', 'kategori' => 'Makan', 'jumlah' => 25000, 'aktif' => true]);

        $this->actingAs($user)->post('/keuangan/recurring/apply');
        $this->actingAs($user)->post('/keuangan/recurring/apply');

        $this->assertSame(1, Transaction::where('user_id', $user->id)
            ->where('recurring_transaction_id', $recurring->id)
            ->count());
    }

    public function test_apply_does_not_duplicate_across_different_recurrings_on_the_same_day(): void
    {
        $user = User::factory()->create();
        RecurringTransaction::create(['user_id' => $user->id, 'type' => 'expense', 'kategori' => 'Makan', 'jumlah' => 25000, 'aktif' => true]);
        RecurringTransaction::create(['user_id' => $user->id, 'type' => 'expense', 'kategori' => 'Transport', 'jumlah' => 15000, 'aktif' => true]);

        $this->actingAs($user)->post('/keuangan/recurring/apply');
        $this->actingAs($user)->post('/keuangan/recurring/apply');

        $this->assertSame(2, Transaction::where('user_id', $user->id)->whereNotNull('recurring_transaction_id')->count());
    }
}
