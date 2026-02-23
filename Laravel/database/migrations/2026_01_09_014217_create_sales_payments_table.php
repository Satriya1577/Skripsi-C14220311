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
        Schema::create('sales_payments', function (Blueprint $table) {
            $table->id();
    
            // PERUBAHAN DISINI: Mengarah ke 'sales_orders'
            $table->foreignId('sales_order_id')
                ->constrained('sales_orders')
                ->onDelete('cascade'); 
                
            $table->date('payment_date');
            
            // Jumlah yang dibayar PADA SAAT ITU (bukan total)
            $table->decimal('amount', 15, 2); 
            
            $table->string('payment_method')->nullable(); // Transfer, Tunai, Giro
            $table->string('reference_number')->nullable(); // No Bukti Transfer / No Giro
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_payments');
    }
};
