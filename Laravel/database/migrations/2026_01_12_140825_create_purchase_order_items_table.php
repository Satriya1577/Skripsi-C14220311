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
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            
            // Relasi
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->onDelete('cascade');
            $table->foreignId('material_id')->constrained('materials');

            // --- SNAPSHOT (Untuk Integritas Data) ---
            $table->string('material_name_snapshot')->nullable(); 
            
            // GANTI 'unit' MENJADI 'unit_snapshot' AGAR KONSISTEN
            $table->string('unit_snapshot')->nullable(); 
            
            $table->decimal('conversion_factor_snapshot', 15, 4)->nullable();

            // --- DATA TRANSAKSI (SAMAKAN DENGAN SALES ORDER) ---
            // GANTI 'qty_ordered' MENJADI 'quantity'
            $table->decimal('quantity', 15, 2); 
            
            // GANTI 'price_per_unit' MENJADI 'unit_price' (Harga Beli per satuan)
            $table->decimal('unit_price', 15, 2)->default(0); 
            
            // TAMBAHKAN KOLOM INI (Yang menyebabkan error selanjutnya jika tidak ada)
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
