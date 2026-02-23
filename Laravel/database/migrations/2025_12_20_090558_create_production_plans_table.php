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
        Schema::create('production_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->date('period'); 
            
            // Snapshot
            $table->integer('forecast_qty')->default(0); 
            $table->integer('current_stock_snapshot')->default(0); 
            $table->integer('safety_stock_snapshot')->default(0); 
            // Parameter Model (Hasil Output Python)
            $table->integer('order_p')->default(1);
            $table->integer('order_d')->default(0);
            $table->integer('order_q')->default(1);
            $table->integer('seasonal_P')->default(0);
            $table->integer('seasonal_D')->default(1);
            $table->integer('seasonal_Q')->default(0);
            $table->integer('seasonal_s')->default(12);

            // RMSE MAPE Skor Akurasi
            $table->float('rmse', 15, 4); 
            $table->float('mape', 15, 4);
            
            // Output
            $table->integer('recommended_production_qty')->default(0); // Rumus = Recommendation = (forecast_qty + safety_stock_snapshot) - current_stock_snapshot
            
            $table->integer('approved_production_qty')->nullable(); // Input User (Boleh diubah)
            $table->enum('status', ['draft', 'approved', 'rejected', 'completed'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Constraint Unik
            $table->unique(['product_id', 'period']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_plans');
    }
};
