<?php

namespace App\Imports;

use App\Models\Partner;
use App\Models\Partners;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Str;

class PartnersImport implements ToModel, WithHeadingRow, SkipsEmptyRows
{
    /**
     * Tentukan baris header (biasanya baris 1)
     */
    public function headingRow(): int
    {
        return 1;
    }

    public function model(array $row)
    {
        // 1. Ambil Nama Perusahaan (Kunci Unik)
        // Helper getValue akan mencari dari berbagai kemungkinan nama kolom
        $companyName = $this->getValue($row, ['company_name', 'company', 'nama_perusahaan', 'distributor', 'supplier']);

        // Jika tidak ada nama perusahaan, skip baris ini
        if (!$companyName) {
            return null;
        }

        // 2. Ambil Data Lainnya
        $personName = $this->getValue($row, ['person_name', 'person', 'pic', 'nama_kontak', 'kontak']);
        $phone      = $this->getValue($row, ['phone', 'no_telepon', 'no_hp', 'telp', 'contact_number']);
        $email      = $this->getValue($row, ['email', 'e_mail', 'mail_address']);
        $address    = $this->getValue($row, ['address', 'alamat', 'lokasi', 'alamat_lengkap']);
        
        // 3. Tentukan Tipe Partner (Distributor / Supplier)
        // Cek apakah ada kolom 'type' di Excel. Jika tidak ada, default ke 'distributor'
        $rawType    = $this->getValue($row, ['type', 'tipe', 'jenis_partner']);
        $type       = 'distributor'; // Default

        if ($rawType) {
            $normalizedType = strtolower(trim($rawType));
            if (in_array($normalizedType, ['distributor', 'supplier', 'both'])) {
                $type = $normalizedType;
            }
        }

        // 4. Simpan ke Database (Update jika ada, Create jika baru)
        return Partner::updateOrCreate(
            ['company_name' => $companyName], // Kunci pencarian agar tidak duplikat
            [
                'person_name' => $personName,
                'phone'       => $phone,
                'email'       => $email,
                'address'     => $address,
                'type'        => $type,
            ]
        );
    }

    /**
     * Helper Sakti: Mencari nilai dari beberapa kemungkinan nama kolom
     * Berguna mengatasi typo atau perbedaan nama header di Excel
     */
    private function getValue($row, array $keys)
    {
        foreach ($keys as $k) {
            // Cek key versi snake_case (standar Laravel Excel)
            // Contoh: "No. Telepon" di Excel akan dibaca "no_telepon"
            $slugKey = Str::slug($k, '_'); 
            
            if (isset($row[$slugKey]) && !empty($row[$slugKey])) {
                return trim($row[$slugKey]);
            }
            
            // Cek key raw (jaga-jaga)
            if (isset($row[$k]) && !empty($row[$k])) {
                return trim($row[$k]);
            }
        }
        return null;
    }
}