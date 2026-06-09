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
        // Masukkan SEMUA nilai enum lama, ditambah nilai yang baru (cost_adjustment)
        // Pastikan nama tabelnya benar (biasanya product_transactions)
        DB::statement("ALTER TABLE product_transactions MODIFY COLUMN type ENUM('production_in', 'sales_out', 'return_in', 'adjustment', 'cost_adjustment') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kembalikan ke nilai enum semula (tanpa cost_adjustment)
        DB::statement("ALTER TABLE product_transactions MODIFY COLUMN type ENUM('production_in', 'sales_out', 'return_in', 'adjustment') NOT NULL");
    }
};
