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
        Schema::create('partner_pricelists', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('partner_id')->constrained('partners')->onDelete('cascade');
            $table->foreignId('material_id')->constrained('materials')->onDelete('cascade');
            
            // --- DATA PRICELIST ---
            // Harga beli material dari supplier ini (dalam satuan beli / purchase_unit)
            $table->decimal('price', 15, 2); 
            
            // Minimum Order Quantity (MOQ) - Biasanya supplier punya batas minimal pembelian
            // $table->decimal('minimum_order_qty', 10, 2)->default(1); 
            
            // Penanda apakah harga ini masih berlaku
            // $table->boolean('is_active')->default(true); 

            
            // Pastikan tidak ada duplikasi data (1 partner hanya punya 1 harga aktif untuk 1 material yang sama)
            $table->unique(['partner_id', 'material_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_pricelists');
    }
};
