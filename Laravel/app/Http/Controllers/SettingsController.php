<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Imports\MaterialsImport;
use App\Imports\PartnersImport;
use App\Imports\ProductMaterialsImport;
use App\Imports\ProductsImport;
use App\Imports\SalesImport;
use App\Imports\SalesOrderImport;
use App\Imports\SalesOrderItemImport;
use App\Jobs\RunGridSearchJob;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;


class SettingsController extends Controller
{
    
    public function index()
    {
       return view ('settings.index');
    }


    public function forecasting() {
        $products = Product::all();

        $isGridSearchRunning = DB::table('jobs')
        ->where('payload', 'like', '%RunGridSearchJob%')
        ->exists();

        return view('settings.forecast', compact('products', 'isGridSearchRunning')); 
    }



    public function updateSarimaParameters(Request $request) 
    {
        // 1. Validasi Input
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'order_p'    => 'required|integer|min:0',
            'order_d'    => 'required|integer|min:0',
            'order_q'    => 'required|integer|min:0',
            'seasonal_P' => 'required|integer|min:0',
            'seasonal_D' => 'required|integer|min:0',
            'seasonal_Q' => 'required|integer|min:0',
            'seasonal_s' => 'required|integer|min:1', // Seasonality minimal 1 (atau 2)
        ]);

        // 2. Ambil Product berdasarkan ID
        $product = Product::findOrFail($validated['product_id']);

        // 3. Update Kolom di Tabel Products
        $product->update([
            'order_p'    => $validated['order_p'],
            'order_d'    => $validated['order_d'],
            'order_q'    => $validated['order_q'],
            'seasonal_P' => $validated['seasonal_P'],
            'seasonal_D' => $validated['seasonal_D'],
            'seasonal_Q' => $validated['seasonal_Q'],
            'seasonal_s' => $validated['seasonal_s'],
        ]);

        return redirect()->back()->with('success', 'Parameter SARIMA berhasil diperbarui.');
    }

    public function import() {
        return view('settings.import');
    }

   // --- IMPORT PRODUCTS ---
    public function importProducts(Request $request) {
        return $this->processImport($request, new ProductsImport, 'Data Produk berhasil diimport!');
    }

    // --- IMPORT MATERIALS ---
    public function importMaterials(Request $request) {
        return $this->processImport($request, new MaterialsImport, 'Data Material berhasil diimport!');
    }

    // --- IMPORT PARTNERS ---
    public function importPartners(Request $request) {
        return $this->processImport($request, new PartnersImport, 'Data Partner berhasil diimport!');
    }

    // --- IMPORT RECIPES ---
    public function importProductMaterials(Request $request) {
        return $this->processImport($request, new ProductMaterialsImport, 'Data Resep berhasil diimport!');
    }

    // --- IMPORT SALES ORDER ---
    public function importSalesOrder(Request $request) {
        return $this->processImport($request, new SalesOrderImport, 'Data Sales Order berhasil diimport!');
    }

    /**
     * Helper Function untuk menangani logic try-catch yang berulang
     */
    private function processImport(Request $request, $importClass, $successMessage)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            DB::beginTransaction();
            
            Excel::import($importClass, $request->file('file'));
            
            DB::commit();
            return redirect()->route('settings.index')->with('success', $successMessage);

        } catch (ValidationException $e) {
            DB::rollBack();
            $failures = $e->failures();
            $messages = [];
            foreach ($failures as $failure) {
                $messages[] = "Baris " . $failure->row() . ": " . implode(', ', $failure->errors());
            }
            return redirect()->back()->withErrors($messages);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // SettingsController.php

    public function runGridSearchAll()
    {
        $configs = Product::all();
        $jobs = [];

        foreach ($configs as $config) {
            // Masukkan ke Job Baru
            Log::info("Dispatching Grid Search Job for Product ID: {$config->id}");
            $jobs[] = new RunGridSearchJob($config->id);
        }

        // Dispatch Batch
        $batch = Bus::batch($jobs)
            ->name('Grid Search All Products')
            ->allowFailures()
            ->dispatch();

        return redirect()->back()->with('success', "Proses Grid Search dimulai untuk " . count($jobs) . " produk. Silakan cek nanti.");
    }

    public function userManagement()
    {
        $users = User::orderBy('created_at', 'desc')->paginate(10);        
        return view('settings.user', compact('users'));
    }

    public function storeUser(Request $request)
    {
        // 1. Tentukan apakah ini mode Edit atau Create
        $isEdit = $request->filled('user_id'); // Jika ada user_id, berarti Edit

        // 2. Validasi Input
        $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|in:none,admin,sales,purchase,inventory,accounting,production',
            
            // Validasi Email Unik (Penting!)
            'email' => [
                'required', 
                'email', 
                'max:255',
                // Jika Edit: Ignore email milik user ini sendiri agar tidak error "Email taken"
                // Jika Create: Cek unique biasa
                $isEdit ? Rule::unique('users')->ignore($request->user_id) : 'unique:users'
            ],

            // Validasi Password
            // Jika Create: Wajib (Required)
            // Jika Edit: Boleh kosong (Nullable), hanya diisi jika ingin ganti password
            'password' => $isEdit ? 'nullable|min:6' : 'required|min:6',
        ], [
            // Custom messages (Opsional)
            'role.in' => 'Role yang dipilih tidak valid.',
            'email.unique' => 'Email ini sudah digunakan oleh user lain.',
        ]);

        try {
            if ($isEdit) {
                // --- LOGIKA UPDATE ---
                $user = User::findOrFail($request->user_id);
                
                $dataToUpdate = [
                    'name'  => $request->name,
                    'email' => $request->email,
                    'role'  => $request->role,
                ];

                // Hanya update password jika user mengisi form password
                if ($request->filled('password')) {
                    $dataToUpdate['password'] = Hash::make($request->password);
                }

                $user->update($dataToUpdate);
                $message = "User '{$user->name}' berhasil diperbarui.";

            } else {
                // --- LOGIKA CREATE ---
                User::create([
                    'name'     => $request->name,
                    'email'    => $request->email,
                    'role'     => $request->role,
                    'password' => Hash::make($request->password), // Wajib hash
                ]);
                $message = "User baru berhasil ditambahkan.";
            }

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage())
                ->withInput(); // Kembalikan input agar user tidak ngetik ulang
        }
    }

    public function destroyUser(User $user)
    {
        $currentUser = Auth::user(); // User yang sedang login

        // 1. Cek Keamanan: Jangan biarkan user menghapus dirinya sendiri
        if ($currentUser->id === $user->id) {
            return redirect()->back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri saat sedang login.');
        }

        // 2. Cek Keamanan: Proteksi Admin
        // Jika target user adalah 'admin', TAPI yang mau menghapus BUKAN 'admin'
        if ($user->role === 'admin' && $currentUser->role !== 'admin') {
            return redirect()->back()->with('error', 'Akses Ditolak: Anda tidak memiliki akses untuk menghapus akun Admin.');
        }

        try {
            // Hapus user
            $userName = $user->name;
            $user->delete();

            return redirect()->back()->with('success', "User '{$userName}' berhasil dihapus.");
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus user: ' . $e->getMessage());
        }
    }
}
