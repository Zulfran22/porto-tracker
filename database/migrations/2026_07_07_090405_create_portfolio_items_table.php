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
        Schema::create('portfolio_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portofolio_id')->constrained('portofolios')->cascadeOnDelete();
            // Snapshot of the type's name at entry time, not an FK to investment_types.id
            // (same pattern as Transaction.kategori/Budget.kategori) — deleting a type
            // never orphans or cascade-deletes historical portfolio_items.
            $table->string('type_name', 50);
            $table->string('unit', 10);
            $table->decimal('gram', 8, 4)->nullable();
            $table->bigInteger('jumlah')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portfolio_items');
    }
};
