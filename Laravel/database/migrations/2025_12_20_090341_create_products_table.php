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
            $table->string('packaging')->nullable();
            $table->integer('current_stock')->default(0)->comment('Physical Stock / On Hand'); 
            
            // Stok Reserved: Barang yang sudah di-booking SO tapi belum dikirim
            // Kolom ini Baru
            // Dihitung sistem
            $table->integer('committed_stock')->default(0)->comment('Reserved Stock');

            // Dihitung sistem
            $table->integer('safety_stock')->default(0);
            
            // Lead time management
            $table->integer('min_lead_time_days')->default(1);
            $table->integer('max_lead_time_days')->default(3);
            $table->float('lead_time_average', 8, 2)->default(2);
            $table->enum('is_manual_lead_time', ['manual', 'automatic'])->default('automatic');

            // Product batch size
            $table->integer('batch_size')->default(50);

            // Parameter Model (Hasil Output Python)
            $table->integer('order_p')->default(1);
            $table->integer('order_d')->default(0);
            $table->integer('order_q')->default(1);
            $table->integer('seasonal_P')->default(0);
            $table->integer('seasonal_D')->default(1);
            $table->integer('seasonal_Q')->default(0);
            $table->integer('seasonal_s')->default(12);
            $table->float('rmse', 8, 2)->nullable();
            $table->float('mape', 8, 2)->nullable();
            $table->timestamp('last_trained_at')->nullable();
            $table->enum('pre_processing', ['raw', 'ma', 'sg', 'bc', 'yj'])->default('raw');

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
