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
        DB::statement("ALTER TABLE material_transactions MODIFY COLUMN type ENUM('in', 'out', 'adjustment', 'cost_adjustment') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE material_transactions MODIFY COLUMN type ENUM('in', 'out', 'adjustment') NOT NULL");
    }
};
