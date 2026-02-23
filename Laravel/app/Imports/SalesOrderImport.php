<?php

namespace App\Imports;

use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Product;
use App\Models\Partner; // PENTING: Tambahkan Model Partner
use App\Models\ProductTransaction;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SalesOrderImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    /**
     * Baris header ada di baris 1 sesuai file Excel Anda
     */
    public function headingRow(): int
    {
        return 1;
    }

    public function collection(Collection $rows)
    {
        // 1. GROUPING DATA (Berdasarkan Tanggal & Distributor)
        // Agar baris dengan tanggal & distributor sama masuk ke satu Nota (SO)
        $grouped = $rows->groupBy(function ($row) {
            $rawDate = $this->getValue($row, ['tanggal', 'date', 'tgl']);
            $distVal = $this->getValue($row, ['distributor', 'customer', 'nama_distributor']) ?? 'General';
            
            // Format tanggal untuk key grouping
            $dateVal = $this->transformDate($rawDate)->format('Y-m-d');
            
            // Key unik: 2025-01-01_nama_distributor
            return $dateVal . '_' . strtolower(trim($distVal));
        });

        // 2. PROSES PER GROUP (PER SALES ORDER)
        foreach ($grouped as $key => $items) {
            DB::transaction(function () use ($items) {
                // Ambil baris pertama untuk data Header
                $firstRow = $items->first();

                // A. PERSIAPAN DATA HEADER
                $rawDate     = $this->getValue($firstRow, ['tanggal', 'date']);
                $distName    = $this->getValue($firstRow, ['distributor', 'customer']) ?? 'General Customer';
                
                $trxDate     = $this->transformDate($rawDate);
                
                // Tentukan status default untuk import history (biasanya langsung shipped/completed)
                $status = 'shipped'; 

                // B. LOGIKA PARTNER (DISTRIBUTOR)
                // Cari Partner di DB, jika tidak ada buat baru (agar partner_id tidak error)
                $partner = Partner::firstOrCreate(
                    ['company_name' => $distName],
                    [
                        'type' => 'distributor',
                        'email' => strtolower(str_replace(' ', '', $distName)) . '@placeholder.com', // Dummy email
                        'phone' => '-',
                        'address' => '-'
                    ]
                );

                // C. BUAT / CARI HEADER SALES ORDER
                // Cek apakah SO ini sudah pernah diimport (cek tgl & partner)
                $salesOrder = SalesOrder::whereDate('transaction_date', $trxDate)
                    ->where('partner_id', $partner->id)
                    ->first();

                if (!$salesOrder) {
                    $salesOrder = SalesOrder::create([
                        'so_code'               => $this->generateSOCode($trxDate),
                        'transaction_date'      => $trxDate,
                        'status'                => $status,
                        
                        // Foreign Key Partner
                        'partner_id'            => $partner->id,
                        
                        // Snapshot Partner (Sesuai Skema)
                        'company_name'          => $partner->company_name,
                        'person_name'           => $partner->person_name ?? $partner->company_name,
                        'phone'                 => $partner->phone,
                        'email'                 => $partner->email,
                        'address'               => $partner->address,

                        // Keuangan Default
                        'grand_total'           => 0,
                        'paid_amount'           => 0,
                        'remaining_balance'     => 0,
                        'payment_status'        => 'unpaid', // Nanti bisa disesuaikan manual
                        'shipping_payment_type' => 'bill_to_customer',
                        'shipping_date'         => $trxDate, // Asumsi kirim hari itu juga
                        'due_date'              => $trxDate->copy()->addDays(30), // Default tempo 30 hari
                    ]);
                }

                $grandTotal = $salesOrder->grand_total; // Mulai dari 0 atau existing

                // D. PROSES ITEMS (LOOPING BARANG)
                foreach ($items as $row) {
                    // Mapping kolom Excel
                    $kodeBarang = $this->getValue($row, ['kode', 'sku', 'code']);
                    $namaBarang = $this->getValue($row, ['nama_barang', 'product', 'nama']);
                    
                    // 1. Cari Produk (Prioritas Kode, lalu Nama)
                    $product = null;
                    if ($kodeBarang) {
                        $product = Product::where('code', $kodeBarang)->first();
                    } 
                    
                    if (!$product && $namaBarang) {
                        $product = Product::where('name', 'like', '%' . $namaBarang . '%')->first();
                    }

                    // Jika produk tidak ditemukan di DB Master, Skip baris ini
                    if (!$product) continue; 

                    // 2. Ambil Qty & Harga
                    $qty = (float) $this->getValue($row, ['pcs', 'qty', 'quantity', 'jumlah_barang']);
                    
                    // Harga: Jika di excel 0/kosong, ambil dari master product
                    $excelPrice = (float) $this->getValue($row, ['harga', 'price', 'unit_price']);
                    $price = ($excelPrice > 0) ? $excelPrice : $product->price;

                    if ($qty <= 0) continue;

                    $subtotal = $qty * $price;

                    // 3. Simpan ke SalesOrderItem
                    SalesOrderItem::create([
                        'sales_order_id'            => $salesOrder->id,
                        'product_id'                => $product->id,
                        
                        // SNAPSHOT (PENTING AGAR SESUAI SKEMA)
                        'product_name_snapshot'     => $product->name,
                        'product_packaging_snapshot'=> $product->packaging,
                        'cogs_snapshot'             => $product->cost_price, // Ambil HPP saat ini
                        
                        'quantity'                  => $qty,
                        'unit_price'                => $price,
                        'subtotal'                  => $subtotal,
                    ]);

                    $grandTotal += $subtotal;

                    // 4. Logika Stok (Kurangi Stok Fisik & Buat Kartu Stok)
                    // Hanya jika status shipped dan bukan data masa depan
                    $this->processStockLogic($salesOrder, $product, $qty);
                }

                // E. UPDATE TOTAL HEADER
                // Asumsi import data lama = dianggap LUNAS (Paid) agar rapi, 
                // atau UNPAID jika ingin ditagihkan. Di sini saya set remaining = grand_total (Unpaid)
                $salesOrder->update([
                    'grand_total'       => $grandTotal,
                    'remaining_balance' => $grandTotal, // Default hutang penuh
                    // 'paid_amount'    => $grandTotal, // Uncomment jika ingin dianggap lunas
                    // 'payment_status' => 'paid',      // Uncomment jika ingin dianggap lunas
                ]);
            });
        }
    }

    /**
     * Helper untuk mengambil nilai dari array Excel dengan beberapa kemungkinan key
     */
    private function getValue($row, array $keys)
    {
        foreach ($keys as $k) {
            // Cek slug (contoh: Nama Barang -> nama_barang)
            $slugKey = Str::slug($k, '_');
            if (isset($row[$slugKey]) && !is_null($row[$slugKey])) {
                return $row[$slugKey];
            }
            // Cek raw key
            if (isset($row[$k]) && !is_null($row[$k])) {
                return $row[$k];
            }
        }
        return null;
    }

    private function transformDate($value)
    {
        if (empty($value)) return now();
        try {
            // Excel date numeric format
            if (is_numeric($value)) {
                return \Carbon\Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value));
            }
            // String format
            return \Carbon\Carbon::parse($value);
        } catch (\Throwable $e) {
            return now();
        }
    }

    private function generateSOCode($date)
    {
        // Format: SO-YYYYMMDD-XXXXX
        return 'SO-' . $date->format('Ymd') . '-' . strtoupper(Str::random(5));
    }

    private function processStockLogic($salesOrder, $product, $qty)
    {
        // 1. Cek apakah tanggal transaksi adalah MASA LALU (Kurang dari Hari Ini 00:00)
        // Jika iya, kita anggap stok master saat ini sudah sesuai (sudah stok opname),
        // jadi transaksi lama tidak boleh memotong stok lagi.
        $isHistorical = $salesOrder->transaction_date->lt(now()->startOfDay());

        if ($isHistorical) {
            return; // STOP DISINI, jangan eksekusi logic stok
        }

        // 2. Jika transaksi HARI INI atau MASA DEPAN, baru potong stok
        if ($salesOrder->status === 'shipped') {
            
            $product->decrement('current_stock', $qty);

            ProductTransaction::create([
                'product_id'            => $product->id,
                'transaction_date'      => $salesOrder->transaction_date,
                'type'                  => 'sales_out',
                'qty'                   => -$qty, 
                'cost_price'            => $product->cost_price,
                'current_stock_balance' => $product->current_stock,
                'product_name_snapshot'   => $product->name,
                'product_packaging_snapshot' => $product->packaging,
                'sales_order_id'        => $salesOrder->id,
                'description'           => 'Import Sales: ' . $salesOrder->so_code,
            ]);
        }
    }
}