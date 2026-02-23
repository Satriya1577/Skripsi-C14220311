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
        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->id();
    
            // KUNCI PENGHUBUNG (Foreign Key ke Header)
            $table->foreignId('sales_order_id')->constrained('sales_orders')->onDelete('cascade');
            
            // Data Produk (Dipindah dari sales)
            $table->foreignId('product_id')->constrained('products');

            // Snapshot
            $table->string('product_name_snapshot')->nullable(); // ex: "Product A"
            $table->string('product_packaging_snapshot')->nullable(); // ex: "24 x 300gr"
            $table->decimal('cogs_snapshot', 15, 2)->nullable();
            
            $table->integer('quantity'); // ex: 10 pcs
            $table->decimal('unit_price', 15, 2)->nullable(); // ex: 5000 per pcs
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->nullable(); // ex: 50000
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_order_items');
    }
};
