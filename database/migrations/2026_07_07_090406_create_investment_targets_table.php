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
        Schema::create('investment_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type_name', 50);
            // decimal(15,4) covers both a gram target (needs fractional precision)
            // and a large Rupiah target in one column without a unit-conditional split.
            $table->decimal('target_value', 15, 4);
            $table->timestamps();
            $table->unique(['user_id', 'type_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment_targets');
    }
};
