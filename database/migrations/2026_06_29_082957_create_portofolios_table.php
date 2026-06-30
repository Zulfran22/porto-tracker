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
    Schema::create('portofolios', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->string('bulan');           // format: "2026-06"
        $table->decimal('emas_gram', 8, 4)->default(0);
        $table->bigInteger('harga_emas')->default(0);
        $table->bigInteger('cicilan')->default(1032662);
        $table->bigInteger('dana_darurat')->default(0);
        $table->bigInteger('reksa_dana')->default(0);
        $table->bigInteger('sbn')->default(0);
        $table->text('catatan')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portofolios');
    }
};
