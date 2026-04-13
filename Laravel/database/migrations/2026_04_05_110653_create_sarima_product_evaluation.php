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
        Schema::create('sarima_product_evaluation', function (Blueprint $table) {
            $table->id();
            $table->string('product_code')->unique(); 
            $table->string('product_name'); 

            $table->integer('raw_order_p')->default(1);
            $table->integer('raw_order_d')->default(0);
            $table->integer('raw_order_q')->default(1);
            $table->integer('raw_seasonal_P')->default(0);
            $table->integer('raw_seasonal_D')->default(1);
            $table->integer('raw_seasonal_Q')->default(0);
            $table->integer('raw_seasonal_s')->default(12);
            $table->timestamp('last_trained_at')->nullable();

            $table->string('raw_rmse')->nullable();
            $table->string('raw_mape')->nullable();

            $table->string('ma_rmse')->nullable();
            $table->string('ma_mape')->nullable();

            $table->string('sg_rmse')->nullable();
            $table->string('sg_mape')->nullable();

            $table->string('bc_rmse')->nullable();
            $table->string('bc_mape')->nullable();

            $table->string('yj_rmse')->nullable();
            $table->string('yj_mape')->nullable();
           
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sarima_product_evaluation');
    }
};
