<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('kategori');
            $table->bigInteger('limit_jumlah');
            $table->timestamps();
            $table->unique(['user_id', 'kategori']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
