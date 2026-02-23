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
            
            // Foreign Key
            $table->foreignId('material_id')->constrained('materials')->onDelete('cascade');
            
            $table->date('transaction_date');
            $table->enum('type', ['in', 'out', 'adjustment']); 
            
            // Kuantitas Transaksi (Base Unit)
            $table->decimal('qty', 15, 2); 

            // --- HARGA TRANSAKSI (NILAI SAAT INI) ---
            // Harga per unit saat beli atau HPP saat keluar
            $table->decimal('price_per_unit', 15, 2)->nullable(); 
            $table->decimal('total_price', 15, 2)->nullable(); 

            // --- SNAPSHOT DATA (TAMBAHAN BARU) ---
            // 1. Snapshot Identitas
            $table->string('material_name_snapshot'); 
            $table->decimal('material_packaging_size_snapshot', 10, 2);
            $table->string('material_packaging_unit_snapshot');
            $table->decimal('material_conversion_factor_snapshot', 15, 4);
            $table->string('purchase_unit_snapshot');
            $table->string('material_unit_snapshot'); // Penting agar tidak bingung satuan apa yg dipakai dulu

            // 2. Snapshot Saldo Berjalan (Post-Transaction)
            $table->decimal('current_stock_balance', 15, 2); 

            // --- RELASI DOKUMEN ---
            $table->foreignId('purchase_order_id')
                ->nullable()
                ->constrained('purchase_orders')
                ->onDelete('cascade'); 

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
