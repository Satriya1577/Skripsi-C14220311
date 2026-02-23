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
        Schema::create('product_transactions', function (Blueprint $table) {
            $table->id();
            // Produk apa yang bergerak
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->date('transaction_date');
            
            // Tipe Transaksi
            $table->enum('type', ['production_in', 'sales_out', 'return_in', 'adjustment']);
            
            $table->integer('qty'); // Positif/Negatif

            // --- TAMBAHAN SNAPSHOT TABEL PRODUK ---
            // Menyimpan HPP (Modal) saat transaksi terjadi.
            // BUKAN Harga Jual (Selling Price ada di sales_orders).
            $table->decimal('cost_price', 15, 2)->default(0);
            $table->integer('current_stock_balance'); // Saldo Berjalan
            // Agar jika master product diedit, history tetap akurat sesuai kondisi saat itu
            $table->string('product_name_snapshot'); 
            $table->string('product_packaging_snapshot')->nullable();
            
            // --- UPDATE FOREIGN KEYS ---
            
            // 1. GANTI sales_id MENJADI sales_order_id
            // Ini merujuk ke Header Nota (Sales Order)
            $table->foreignId('sales_order_id')
                ->nullable()
                ->constrained('sales_orders')
                ->onDelete('cascade'); 
                
            // 2. Produksi (Tetap)
            $table->foreignId('production_realization_id')
                ->nullable()
                ->constrained('production_realizations')
                ->onDelete('cascade');
                
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_transactions');
    }
};
