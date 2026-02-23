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
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->string('so_code')->unique(); // Identitas Nota
          
            $table->date('transaction_date'); // Dipindah dari sales
           
            // draft     : Belum memotong stok / belum reserve
            // confirmed : Menambah committed_stock (Reserve)
            // shipped   : Mengurangi committed_stock DAN mengurangi current_stock (Fisik)
            // cancelled : Mengembalikan committed_stock jika sebelumnya confirmed
            $table->enum('status', ['draft', 'confirmed', 'shipped', 'cancelled'])->default('draft');

            // --- KEUANGAN (HEADER) ---
            // Total belanja (Tagihan Awal)
            $table->decimal('grand_total', 15, 2)->default(0); 
            
            // Akumulasi Pembayaran (Total dari tabel sales_payments)
            $table->decimal('paid_amount', 15, 2)->default(0); 
            
            // Sisa Hutang (grand_total - paid_amount)
            $table->decimal('remaining_balance', 15, 2)->default(0); 
            
            // Status Pembayaran
            // 'unpaid' : Belum bayar sama sekali
            // 'partial': Sudah bayar sebagian (Nyicil)
            // 'paid'   : Lunas (remaining_balance = 0)
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid');

            $table->foreignId('partner_id')->constrained('partners')->onDelete('cascade');
            // Customer Snapshot
            $table->string('company_name');
            $table->string('person_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();

            // Shipping / Delivery Info
            // 1. Biaya yang muncul di Invoice Customer (Menambah Total Tagihan)
            $table->decimal('shipping_cost', 15, 2)->default(0); 
            // 2. Keterangan siapa yang nanggung (Pengganti FOB yang lebih deskriptif untuk sales)
            // 'bill_to_customer': Customer bayar full (Total Tagihan + Ongkir)
            // 'borne_by_company': Gratis Ongkir (Total Tagihan tidak tambah, tapi nanti jadi expense di laporan laba rugi)
            $table->enum('shipping_payment_type', ['bill_to_customer', 'borne_by_company'])->default('bill_to_customer');

            $table->date('shipping_date')->nullable(); 

            
            $table->date('due_date')->nullable(); // Jatuh Tempo

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
