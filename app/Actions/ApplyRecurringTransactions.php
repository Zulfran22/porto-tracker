<?php

namespace App\Actions;

use App\Models\RecurringTransaction;
use App\Models\Transaction;

// Diekstrak dari controller supaya bisa dipanggil baik dari tombol "Terapkan
// Bulan Ini" di Keuangan.vue maupun dari scheduler harian (lihat
// routes/console.php) — sebelumnya hanya bisa dipicu manual lewat HTTP.
class ApplyRecurringTransactions
{
    /**
     * Terapkan semua recurring aktif milik user ke hari ini.
     * Idempoten — melewati recurring yang sudah diterapkan hari ini.
     *
     * @return array{applied: int, skipped: int}
     */
    public function execute(int $userId): array
    {
        $today = now()->toDateString();

        $recurrings = RecurringTransaction::where('user_id', $userId)
            ->where('aktif', true)
            ->get();

        $sudahDiterapkan = Transaction::where('user_id', $userId)
            ->where('tanggal', $today)
            ->whereNotNull('recurring_transaction_id')
            ->pluck('recurring_transaction_id')
            ->all();

        $count = 0;
        foreach ($recurrings as $r) {
            if (in_array($r->id, $sudahDiterapkan)) {
                continue;
            }

            Transaction::create([
                'user_id' => $userId,
                'recurring_transaction_id' => $r->id,
                'tanggal' => $today,
                'type' => $r->type,
                'kategori' => $r->kategori,
                'jumlah' => $r->jumlah,
                'catatan' => $r->catatan ?? 'Transaksi berulang',
            ]);
            $count++;
        }

        return [
            'applied' => $count,
            'skipped' => $recurrings->count() - $count,
        ];
    }
}
