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
            $table->dropColumn(['emas_gram', 'dana_darurat', 'reksa_dana', 'sbn']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('portofolios', function (Blueprint $table) {
            $table->decimal('emas_gram', 8, 4)->default(0)->after('bulan');
            $table->bigInteger('dana_darurat')->default(0)->after('cicilan');
            $table->bigInteger('reksa_dana')->default(0)->after('dana_darurat');
            $table->bigInteger('sbn')->default(0)->after('reksa_dana');
        });
    }
};
