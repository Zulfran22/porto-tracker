<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A plain unique(user_id, bulan) also covers soft-deleted rows (MySQL has
     * no partial/filtered index support), so re-creating a soft-deleted
     * month must go through restore() rather than a fresh insert — see
     * PortofolioController::store(). That keeps this constraint portable
     * across the mysql/pgsql/sqlite drivers this app runs on.
     */
    public function up(): void
    {
        Schema::table('portofolios', function (Blueprint $table) {
            // Also serves as the composite index every lookup on this table filters/sorts by.
            $table->unique(['user_id', 'bulan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('portofolios', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'bulan']);
        });
    }
};
