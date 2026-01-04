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
        Schema::create('sarima_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            
            // Parameter Model (Hasil Output Python)
            $table->integer('order_p');
            $table->integer('order_d');
            $table->integer('order_q');
            $table->integer('seasonal_P');
            $table->integer('seasonal_D');
            $table->integer('seasonal_Q');
            $table->integer('seasonal_s');
            
            // Skor Akurasi
            $table->float('rmse', 15, 4); 
            $table->float('mape', 15, 4); 
            
            $table->timestamp('last_trained_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sarima_configs');
    }
};
