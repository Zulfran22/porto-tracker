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

        // No SBN row migrated — no prior target_sbn column ever existed
        // (pre-existing gap in the old fixed-column design, now moot once
        // Target.vue iterates investment_types dynamically).
        DB::table('targets')->orderBy('id')->chunkById(200, function ($rows) use ($now) {
            $inserts = [];
            foreach ($rows as $row) {
                $inserts[] = ['user_id' => $row->user_id, 'type_name' => 'Emas Tunai',   'target_value' => $row->target_emas,    'created_at' => $now, 'updated_at' => $now];
                $inserts[] = ['user_id' => $row->user_id, 'type_name' => 'Dana Darurat', 'target_value' => $row->target_darurat, 'created_at' => $now, 'updated_at' => $now];
                $inserts[] = ['user_id' => $row->user_id, 'type_name' => 'Reksa Dana',   'target_value' => $row->target_reksa,   'created_at' => $now, 'updated_at' => $now];
            }
            DB::table('investment_targets')->insert($inserts);
        });
    }

    /**
     * Reverse the migrations. Same lossy-rollback tradeoff as the earlier
     * backfill migration — only the 3 known default type_names map back.
     */
    public function down(): void
    {
        DB::table('targets')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                $t = DB::table('investment_targets')->where('user_id', $row->user_id)->get()->keyBy('type_name');
                DB::table('targets')->where('id', $row->id)->update([
                    'target_emas' => $t['Emas Tunai']->target_value ?? 10,
                    'target_darurat' => $t['Dana Darurat']->target_value ?? 18000000,
                    'target_reksa' => $t['Reksa Dana']->target_value ?? 50000000,
                ]);
            }
        });
        DB::table('investment_targets')->delete();
    }
};
