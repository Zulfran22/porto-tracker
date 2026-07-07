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
        Schema::table('portofolios', function (Blueprint $table) {
            $table->bigInteger('harga_emas')->nullable()->default(null)->change();
            $table->bigInteger('cicilan')->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('portofolios', function (Blueprint $table) {
            $table->bigInteger('harga_emas')->default(0)->change();
            $table->bigInteger('cicilan')->default(1032662)->change();
        });
    }
};
