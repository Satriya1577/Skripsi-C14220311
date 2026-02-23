<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $partners = Partner::orderBy('id', 'desc')->paginate(10);
        return view('partners.index', compact('partners'));
    }

    /**
     * Store a newly created resource in storage.
     * KARENA VIEW MENGGUNAKAN SATU FORM UNTUK CREATE & EDIT,
     * KITA GUNAKAN LOGIKA "UPSERT" DI SINI.
     */
    public function store(Request $request)
    {
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Partner $partners)
    {
        // Cari data berdasarkan ID, jika tidak ketemu akan error 404
        $partner = Partner::findOrFail($partners->id);
        
        // Hapus data
        $partner->delete();

        return redirect()->route('partners.index')->with('success', 'Partner deleted successfully!');
    }
}
