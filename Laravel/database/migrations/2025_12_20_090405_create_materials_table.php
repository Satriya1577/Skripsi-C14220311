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
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); 
            $table->string('name'); 
            
            // --- MANAJEMEN STOK & SATUAN ---
            // Disimpan dalam satuan terkecil (Base Unit)
            $table->decimal('current_stock', 15, 2)->default(0); 
            
            // Satuan Dasar (misal: 'gram', 'ml', 'pcs') -> Digunakan di Resep & Stok
            $table->string('unit')->default('gram'); 
            
            // Satuan Pembelian (misal: 'kg', 'liter', 'karung') -> Digunakan user saat input 'IN'
            $table->string('purchase_unit')->nullable(); 

            $table->decimal('packaging_size', 10, 2)->nullable()
                  ->comment('Input angka kemasan (cth: 25)');
            
            // Kolom untuk menyimpan satuan input (misal: kg, liter, dozen)
            $table->string('packaging_unit', 20)->nullable()
                  ->comment('Input satuan kemasan (cth: kg)');
            
            // Rumus: 1 Purchase Unit = X Base Unit (misal: 1000)
            $table->decimal('conversion_factor', 15, 4)->default(1); 
            // -----------------------------

            // --- VALUASI HARGA (Moving Average) ---
            // Harga per SATUAN DASAR (misal: Harga per Gram)
            $table->decimal('price_per_unit', 15, 4)->default(0); 
            // -------------------------------------

            $table->enum('category_type', ['mass', 'volume', 'unit']); 
            $table->integer('lead_time_days')->default(1);

            // --- STATUS MATERIAL ---
            // true = Aktif (Bisa dibeli/dipakai), false = Non-Aktif (Disembunyikan)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
