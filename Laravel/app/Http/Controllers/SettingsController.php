<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Imports\MaterialsImport;
use App\Imports\ProductMaterialsImport;
use App\Imports\ProductsImport;
use App\Imports\SalesImport;
use App\Jobs\RunGridSearchJob;
use App\Models\SarimaConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class SettingsController extends Controller
{
    
    public function index()
    {
       return view ('settings.index');
    }


    public function forecasting() {
        $sarimaParameters = SarimaConfig::with('product')->get();

        $isGridSearchRunning = DB::table('jobs')
        ->where('payload', 'like', '%RunGridSearchJob%')
        ->exists();

        return view('settings.forecast', compact('sarimaParameters', 'isGridSearchRunning')); 
    }


    public function updateSarimaParameters(Request $request) {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'order_p' => 'required|integer|min:0',
            'order_d' => 'required|integer|min:0',
            'order_q' => 'required|integer|min:0',
            'seasonal_P' => 'required|integer|min:0',
            'seasonal_D' => 'required|integer|min:0',
            'seasonal_Q' => 'required|integer|min:0',
            'seasonal_s' => 'required|integer|min:1',
        ]);

        SarimaConfig::updateOrCreate(
            // KONDISI PENCARIAN (Cari config milik product_id ini)
            ['product_id' => $validated['product_id']], 
            
            // DATA YANG AKAN DISIMPAN/DIUPDATE
            [
                'order_p'    => $validated['order_p'],
                'order_d'    => $validated['order_d'],
                'order_q'    => $validated['order_q'],
                'seasonal_P' => $validated['seasonal_P'],
                'seasonal_D' => $validated['seasonal_D'],
                'seasonal_Q' => $validated['seasonal_Q'],
                'seasonal_s' => $validated['seasonal_s'],
            ]
        );

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

    // --- IMPORT RECIPES ---
    public function importProductMaterials(Request $request) {
        return $this->processImport($request, new ProductMaterialsImport, 'Data Resep berhasil diimport!');
    }

    // --- IMPORT SALES ---
    public function importSales(Request $request) {
        return $this->processImport($request, new SalesImport, 'Data Penjualan berhasil diimport!');
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
        $configs = SarimaConfig::all();
        $jobs = [];

        foreach ($configs as $config) {
            // Masukkan ke Job Baru
            $jobs[] = new RunGridSearchJob($config->product_id);
        }

        // Dispatch Batch
        $batch = Bus::batch($jobs)
            ->name('Grid Search All Products')
            ->allowFailures()
            ->dispatch();

        return redirect()->back()->with('success', "Proses Grid Search dimulai untuk " . count($jobs) . " produk. Silakan cek nanti.");
    }
}
