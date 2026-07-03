<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Every dashboard/catat/grafik/info/target page load calls
        // KontrakCicilanEmas::aktifUntuk(), which filters on all three of
        // these columns — the single hottest query path in the app.
        Schema::table('kontrak_cicilan_emas', function (Blueprint $table) {
            $table->index(['user_id', 'status', 'tanggal_mulai']);
        });

        // Covers the cashflow whereYear/whereMonth lookup (index still helps
        // narrow to the user's rows even though the function wrapper prevents
        // a pure range scan) and the ledger's orderBy('tanggal', 'desc').
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['user_id', 'tanggal']);
        });

        // Covers RecurringTransactionController::apply()'s active-recurrings lookup.
        Schema::table('recurring_transactions', function (Blueprint $table) {
            $table->index(['user_id', 'aktif']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * On MySQL, InnoDB adopts whichever index currently covers a foreign key
     * column as that constraint's supporting index — after up() runs, that's
     * these new composite indexes (their leftmost column is user_id), not the
     * original FK auto-index. Dropping them directly fails with "needed in a
     * foreign key constraint", so the FK has to be dropped and recreated
     * around each index drop to leave a valid supporting index in place.
     */
    public function down(): void
    {
        Schema::table('kontrak_cicilan_emas', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id', 'status', 'tanggal_mulai']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id', 'tanggal']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('recurring_transactions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id', 'aktif']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
