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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->unique(); // No PO: PO/2024/001
            $table->date('order_date');
            
            // STATUS PO:
            // 'draft'    : Masih diedit
            // 'ordered'  : Sudah dikirim ke supplier (Barang OTW)
            // 'received' : Barang sudah diterima (Masuk Stok)
            // 'cancelled': Batal
            $table->enum('status', ['draft', 'ordered', 'received', 'cancelled'])->default('draft');

            // --- KEUANGAN (BARU - MENYAMAKAN DENGAN SALES ORDER) ---
            // Total Pembelian (termasuk ongkir jika ada)
            $table->decimal('grand_total', 15, 2)->default(0); 
            
            // Akumulasi Pembayaran ke Supplier (Total dari tabel purchase_payments)
            $table->decimal('paid_amount', 15, 2)->default(0); 
            
            // Sisa Hutang ke Supplier (grand_total - paid_amount)
            $table->decimal('remaining_balance', 15, 2)->default(0); 
            
            // Status Pembayaran Hutang
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid');

            // --- RELASI SUPPLIER ---
            $table->foreignId('partner_id')->constrained('partners')->onDelete('cascade');
            
            // Supplier Snapshot
            $table->string('company_name');
            $table->string('person_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();

            // --- PENGIRIMAN ---
            $table->decimal('shipping_cost', 15, 2)->default(0);
            
            // Syarat Pengiriman (Khusus Pembelian)
            // FOB Shipping Point: Pembeli nanggung ongkir & risiko sejak gudang penjual.
            // FOB Destination: Penjual nanggung ongkir sampai gudang pembeli.
            $table->enum('shipping_terms', ['FOB_shipping_point', 'FOB_destination'])->default('FOB_destination');
            
            // Tanggal Barang Sampai (Opsional)
            // Ini baru bisa diisi kalau status PO sudah 'ordered'  
            $table->date('expected_arrival_date')->nullable(); 

            // --- JATUH TEMPO (BARU) ---
            $table->date('due_date')->nullable(); // Jatuh tempo pembayaran ke supplier

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
