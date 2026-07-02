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
        Schema::create('kontrak_cicilan_emas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('nomor_kontrak');
            $table->string('cabang')->nullable();
            $table->string('no_rekening')->nullable();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->unsignedSmallInteger('tenor_bulan')->default(12);
            $table->decimal('total_gram', 8, 4);
            $table->bigInteger('angsuran_bulan');
            $table->bigInteger('sewa_modal')->nullable();
            $table->bigInteger('biaya_admin')->nullable();
            $table->string('status')->default('aktif'); // aktif | lunas | wanprestasi
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kontrak_cicilan_emas');
    }
};
