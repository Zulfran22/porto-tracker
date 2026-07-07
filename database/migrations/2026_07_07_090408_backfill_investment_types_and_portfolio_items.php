<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        $userIds = DB::table('portofolios')->distinct()->pluck('user_id');
        foreach ($userIds as $userId) {
            if (DB::table('investment_types')->where('user_id', $userId)->exists()) {
                continue;
            }
            DB::table('investment_types')->insert([
                ['user_id' => $userId, 'name' => 'Emas Tunai',   'unit' => 'gram',   'is_default' => true, 'urutan' => 1, 'created_at' => $now, 'updated_at' => $now],
                ['user_id' => $userId, 'name' => 'Dana Darurat', 'unit' => 'rupiah', 'is_default' => true, 'urutan' => 2, 'created_at' => $now, 'updated_at' => $now],
                ['user_id' => $userId, 'name' => 'Reksa Dana',   'unit' => 'rupiah', 'is_default' => true, 'urutan' => 3, 'created_at' => $now, 'updated_at' => $now],
                ['user_id' => $userId, 'name' => 'SBN',          'unit' => 'rupiah', 'is_default' => true, 'urutan' => 4, 'created_at' => $now, 'updated_at' => $now],
            ]);
        }

        // Fan every existing portofolio row's 4 fixed columns out into portfolio_items,
        // always creating all 4 (even at 0) to preserve historical continuity.
        DB::table('portofolios')->orderBy('id')->chunkById(200, function ($rows) use ($now) {
            $items = [];
            foreach ($rows as $row) {
                $items[] = ['portofolio_id' => $row->id, 'type_name' => 'Emas Tunai',   'unit' => 'gram',   'gram' => $row->emas_gram, 'jumlah' => null, 'created_at' => $now, 'updated_at' => $now];
                $items[] = ['portofolio_id' => $row->id, 'type_name' => 'Dana Darurat', 'unit' => 'rupiah', 'gram' => null, 'jumlah' => $row->dana_darurat, 'created_at' => $now, 'updated_at' => $now];
                $items[] = ['portofolio_id' => $row->id, 'type_name' => 'Reksa Dana',   'unit' => 'rupiah', 'gram' => null, 'jumlah' => $row->reksa_dana, 'created_at' => $now, 'updated_at' => $now];
                $items[] = ['portofolio_id' => $row->id, 'type_name' => 'SBN',          'unit' => 'rupiah', 'gram' => null, 'jumlah' => $row->sbn, 'created_at' => $now, 'updated_at' => $now];
            }
            DB::table('portfolio_items')->insert($items);
        });
    }

    /**
     * Reverse the migrations.
     *
     * Intentionally best-effort/lossy: only the 4 known default type_names map
     * back onto fixed columns. Any custom rupiah type a user added after this
     * shipped has no column to return to and its value is dropped. Acceptable
     * for a personal project — rolling back a shipped schema change is a rare
     * deliberate action, not a routine operation.
     *
     * Relies on emas_gram/dana_darurat/reksa_dana/sbn already existing on
     * portofolios, which is true because migrations roll back in reverse
     * order — the migration that drops those columns rolls back before this
     * one runs, re-adding them first.
     */
    public function down(): void
    {
        DB::table('portofolios')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                $items = DB::table('portfolio_items')->where('portofolio_id', $row->id)->get()->keyBy('type_name');
                DB::table('portofolios')->where('id', $row->id)->update([
                    'emas_gram'    => $items['Emas Tunai']->gram ?? 0,
                    'dana_darurat' => $items['Dana Darurat']->jumlah ?? 0,
                    'reksa_dana'   => $items['Reksa Dana']->jumlah ?? 0,
                    'sbn'          => $items['SBN']->jumlah ?? 0,
                ]);
            }
        });
        DB::table('portfolio_items')->delete();
        DB::table('investment_types')->where('is_default', true)->delete();
    }
};
