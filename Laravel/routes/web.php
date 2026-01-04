<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ForecastController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\MaterialTransactionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductionPlanController;
use App\Http\Controllers\ProductMaterialController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\SarimaConfigController;
use App\Http\Controllers\SettingsController;
use App\Models\MaterialTransaction;
use Illuminate\Support\Facades\Route;



Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->name('login.post')->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware(['auth'])->group(function () {

    Route::get('/home', function () {
        return view('home.index');
    })->name('home.index');

    // Product Routes
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::post('/products/store', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/show/{product}', [ProductController::class, 'show'])->name('products.show');  
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

    // Product Material Routes
    Route::post('/product-materials/store', [ProductMaterialController::class, 'store'])->name('product_materials.store');
    Route::delete('/product-materials/{product_material}', [ProductMaterialController::class, 'destroy'])->name('product_materials.destroy');

    // Material Routes
    Route::get('/materials', [MaterialController::class, 'index'])->name('materials.index');
    Route::post('/materials/store', [MaterialController::class, 'store'])->name('materials.store');
    Route::delete('/materials/{material}', [MaterialController::class, 'destroy'])->name('materials.destroy');
    Route::get('/materials/show/{material}', [MaterialController::class, 'show'])->name('materials.show');

    // Material Transaction Routes
    Route::post('/materials/adjustment', [MaterialTransactionController::class, 'storeAdjustment'])->name('materials.adjustment.store');
    Route::post('/materials/in', [MaterialTransactionController::class, 'storeIn'])->name('materials.in.store');

    // Sales Routes
    Route::get('/sales', [SalesController::class, 'index'])->name('sales.index');
    Route::post('/sales/store', [SalesController::class, 'store'])->name('sales.store');
    Route::delete('/sales/{sales}', [SalesController::class, 'destroy'])->name('sales.destroy');

    // Forecasting Routes
    Route::get('/forecast', [ForecastController::class, 'index'])->name('forecast.index');
    Route::get('/forecast/show/{product}', [ForecastController::class, 'show'])->name('forecast.show');
    Route::post('/forecast/generate/{product}', [ForecastController::class, 'generate'])->name('forecast.generate');
    Route::get('/forecast/check-status/{product}', [ForecastController::class, 'checkStatus'])->name('forecast.checkStatus');

    // Production Plan Routes
    Route::get('/production-plans/{production_plan}', [ProductionPlanController::class, 'show'])->name('production_plans.show');

    // Settings Routes
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::get('settings/forecasting', [SettingsController::class, 'forecasting'])->name('settings.forecast');
    Route::put('settings/forecasting/update-sarima', [SettingsController::class, 'updateSarimaParameters'])->name('settings.updateSarima');
    Route::get('settings/imports', [SettingsController::class, 'import'])->name('settings.import');
    Route::post('settings/import/products', [SettingsController::class, 'importProducts'])->name('products.import.excel');
    Route::post('settings/import/materials', [SettingsController::class, 'importMaterials'])->name('materials.import.excel');
    Route::post('settings/import/recipes', [SettingsController::class, 'importProductMaterials'])->name('product_materials.import.excel');
    Route::post('settings/import/sales', [SettingsController::class, 'importSales'])->name('sales.import.excel');

});