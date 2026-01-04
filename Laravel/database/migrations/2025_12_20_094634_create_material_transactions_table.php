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
        Schema::create('material_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materials')->onDelete('cascade');
            $table->enum('type', ['in', 'out', 'adjustment']); 
            
            // Base Unit (Gram/ML)
            $table->decimal('qty', 15, 2); 

            // --- PENAMBAHAN KOLOM HARGA ---
            // Penting untuk valuasi stok (FIFO/Average)
            $table->decimal('price_per_unit', 15, 2)->nullable(); // Nullable jika tipe 'out' (mengikuti HPP)
            $table->decimal('total_price', 15, 2)->nullable();    // qty * price_per_unit
            // -----------------------------
            
            $table->date('transaction_date');
            $table->foreignId('production_realization_id')
            ->nullable()
            ->constrained('production_realizations')
            ->nullOnDelete();
            
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_transactions');
    }
};
