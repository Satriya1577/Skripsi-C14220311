<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Material;
use App\Models\PartnerPricelist;

class PartnerController extends Controller
{
    // tampilkan list distributor dan supplier
    // distributor: customer/pembeli produk berkaitan dengan data SO (sales order)
    // supplier: pemasok bahan baku dan berkaitan dengan data PO ( purchase order)
    public function index()
    {
        $partners = Partner::orderBy('id', 'desc')->paginate(10);
        return view('partners.index', compact('partners'));
    }

    // digunakan untuk menyimpan data supplier/distributor baru
    // digunakan juga untuk update data supplier/distributor yang sudah ada
    // karena form create dan edit menggunakan form yang sama
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'sales', 'purchase'])) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: Anda tidak memiliki akses untuk insert data distributor atau supplier.')->withInput();
        }
        // 1. Validasi Input
        $rules = [
            // Cek Unik: Nama Perusahaan harus unik, TAPI kecualikan ID yang sedang diedit (jika ada)
            'company_name' => 'required|string|max:255|unique:partners,company_name,' . $request->partner_id,
            'type'         => 'required|in:distributor,supplier,both',
            'person_name'  => 'nullable|string|max:255',
            'phone'        => 'nullable|string|max:20',
            'email'        => 'nullable|email|max:255',
            'address'      => 'nullable|string',
        ];

        $request->validate($rules);

        // 2. Eksekusi Simpan / Update
        // updateOrCreate akan mencari data berdasarkan ID.
        // Jika ID ada -> Update. Jika ID kosong -> Create Baru.
        Partner::updateOrCreate(
            ['id' => $request->partner_id], // Kunci pencarian (Search Key)
            [
                'company_name' => $request->company_name,
                'person_name'  => $request->person_name,
                'type'         => $request->type,
                'phone'        => $request->phone,
                'email'        => $request->email,
                'address'      => $request->address,
            ] // Data yang disimpan/diupdate
        );

        // 3. Pesan Feedback
        $message = $request->partner_id ? 'Partner updated successfully!' : 'New Partner created successfully!';
        
        return redirect()->route('partners.index')->with('success', $message);
    }

    // hapus data distributor atau supplier 
    public function destroy(Partner $partners)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'sales', 'purchase'])) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: Anda tidak memiliki akses untuk menghapus data distributor atau supplier.')->withInput();
        }

        // Cari data berdasarkan ID, jika tidak ketemu akan error 404
        $partner = Partner::findOrFail($partners->id);
        
        // Hapus data
        $partner->delete();

        return redirect()->route('partners.index')->with('success', 'Partner deleted successfully!');
    }

    public function showPricelist(Partner $partner)
    {
        if ($partner->type !== 'supplier') {
            return redirect()->route('partners.index')->with('error', 'Hanya Partner dengan tipe Supplier yang memiliki Pricelist.');
        }

        // Ambil data pricelist yang sudah ada beserta relasi materialnya
        $pricelists = $partner->pricelists()->with('material')->get();

        // Ambil daftar material aktif untuk dropdown
        $materials = Material::where('is_active', true)->orderBy('code')->get();

        return view('partners.pricelist', compact('partner', 'pricelists', 'materials'));
    }

    public function storePricelist(Request $request, Partner $partner)
    {
        $request->validate([
            'material_id' => 'required|exists:materials,id',
            'price' => 'required|numeric|min:0',
        ]);

        // Gunakan updateOrCreate: 
        // Jika material sudah ada di pricelist supplier ini, maka harganya diupdate.
        // Jika belum ada, maka buat data baru.
        PartnerPricelist::updateOrCreate(
            [
                'partner_id'  => $partner->id,
                'material_id' => $request->material_id,
            ],
            [
                'price' => $request->price,
            ]
        );

        return redirect()->back()->with('success', 'Harga Material berhasil ditambahkan/diperbarui.');
    }

    public function destroyPricelist(Partner $partner, $id)
    {
        // Langsung gunakan $id yang dikirim dari route
        \App\Models\PartnerPricelist::where('id', $id)
            ->where('partner_id', $partner->id)
            ->delete();

        return redirect()->back()->with('success', 'Material berhasil dihapus dari Pricelist.');
    }
}

