<?php

namespace Tests\Feature;

use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplyRecurringTransactionsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_applies_recurring_transactions_for_every_user_with_active_recurrings(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $noRecurrings = User::factory()->create();

        RecurringTransaction::create(['user_id' => $alice->id, 'type' => 'expense', 'kategori' => 'Makan', 'jumlah' => 25000, 'aktif' => true]);
        RecurringTransaction::create(['user_id' => $bob->id, 'type' => 'income', 'kategori' => 'Gaji', 'jumlah' => 5000000, 'aktif' => true]);
        RecurringTransaction::create(['user_id' => $bob->id, 'type' => 'expense', 'kategori' => 'Belanja', 'jumlah' => 10000, 'aktif' => false]);

        $this->artisan('recurring:apply')->assertSuccessful();

        $this->assertSame(1, Transaction::where('user_id', $alice->id)->count());
        $this->assertSame(1, Transaction::where('user_id', $bob->id)->count());
        $this->assertSame(0, Transaction::where('user_id', $noRecurrings->id)->count());
    }

    public function test_command_is_idempotent_across_runs(): void
    {
        $user = User::factory()->create();
        RecurringTransaction::create(['user_id' => $user->id, 'type' => 'expense', 'kategori' => 'Makan', 'jumlah' => 25000, 'aktif' => true]);

        $this->artisan('recurring:apply');
        $this->artisan('recurring:apply');

        $this->assertSame(1, Transaction::where('user_id', $user->id)->count());
    }
}
