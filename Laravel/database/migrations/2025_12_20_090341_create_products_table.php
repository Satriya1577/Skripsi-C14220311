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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); 
            $table->string('name'); 
            $table->integer('current_stock')->default(0); 
            $table->integer('safety_stock')->default(0); 
            // Tabel products
            $table->decimal('price', 15, 2)->default(0);       // Harga Jual (Selling Price) -> Input User
            $table->decimal('cost_price', 15, 2)->default(0);  // HPP Rata-rata (Moving Average) -> Otomatis
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
