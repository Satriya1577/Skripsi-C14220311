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
        Schema::create('forecasting_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->date('target_period'); // Periode yang ingin diramal (2025-11-01)
            
            // Status Pengerjaan
            // pending: Baru dibuat controller, belum disentuh Python
            // processing: Python sedang training (User bisa lihat loading bar)
            // completed: Sukses, data forecast sudah masuk
            // failed: Python error (crash/data tidak cukup)
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            
            // Menyimpan pesan error jika failed, atau log durasi jika sukses
            $table->text('message')->nullable(); 
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forecasting_jobs');
    }
};
