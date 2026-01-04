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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->date('transaction_date');
            $table->integer('quantity_sold');
            
            // --- PENAMBAHAN KOLOM HARGA ---
            $table->decimal('price_per_unit', 15, 2)->default(0); // Harga satuan saat transaksi terjadi
            $table->decimal('total_price', 15, 2)->default(0);    // quantity_sold * price_per_unit
            
            // -----------------------------
            $table->string('nama_distributor', 255)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
