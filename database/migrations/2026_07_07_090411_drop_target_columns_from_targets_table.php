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
            $table->dropColumn(['target_emas', 'target_darurat', 'target_reksa']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('targets', function (Blueprint $table) {
            $table->decimal('target_emas', 8, 2)->default(10)->after('user_id');
            $table->bigInteger('target_darurat')->default(18000000)->after('target_emas');
            $table->bigInteger('target_reksa')->default(50000000)->after('target_darurat');
        });
    }
};
