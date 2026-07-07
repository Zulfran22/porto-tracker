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
        Schema::table('targets', function (Blueprint $table) {
            // Budget saving bulanan — satu sumber untuk estimasi di Dashboard,
            // Target, dan Info agar tidak ada angka hardcode yang saling beda.
            $table->unsignedBigInteger('budget_bulanan')->default(3000000)->after('target_reksa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('targets', function (Blueprint $table) {
            $table->dropColumn('budget_bulanan');
        });
    }
};
