<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ForecastController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\ProductionPlanController;
use App\Http\Controllers\ProductMaterialController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchasePaymentController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\SalesPaymentController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;



Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->name('login.post')->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware(['auth'])->group(function () {

    Route::get('/home', function () {
        return view('home.index');
    })->name('home.index');


    // PRODUCT SECTION 
    // Product Routes
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::get('/products/edit/{product}', [ProductController::class, 'edit'])->name('products.edit'); 
    Route::patch('/products/update/{product}', [ProductController::class, 'update'])->name('products.update'); // admin, inventory
    Route::post('/products/store', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/show/{product}', [ProductController::class, 'show'])->name('products.show');  
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    Route::post('/products/adjustment', [ProductController::class, 'storeAdjustment'])->name('products.adjustment.store'); // admin, inventory
    // Product Material Routes
    Route::post('/product-materials/store', [ProductMaterialController::class, 'store'])->name('product_materials.store'); // admin, production
    Route::delete('/product-materials/{product_material}', [ProductMaterialController::class, 'destroy'])->name('product_materials.destroy'); // admin, production


    // MATERIAL SECTION
    // Material Routes
    Route::get('/materials', [MaterialController::class, 'index'])->name('materials.index');
    Route::get('/materials/create', [MaterialController::class, 'create'])->name('materials.create');
    Route::get('/materials/edit/{material}', [MaterialController::class, 'edit'])->name('materials.edit'); 
    Route::patch('/materials/update/{material}', [MaterialController::class, 'update'])->name('materials.update'); // admin, inventory
    Route::post('/materials/store', [MaterialController::class, 'store'])->name('materials.store');
    Route::get('/materials/show/{material}', [MaterialController::class, 'show'])->name('materials.show');
    Route::delete('/materials/{material}', [MaterialController::class, 'destroy'])->name('materials.destroy');
    Route::post('/materials/adjustment', [MaterialController::class, 'storeAdjustment'])->name('materials.adjustment.store'); // admin, inventory


    // PARTNER SECTION
    // Partner Routes
    Route::get('/partners', [PartnerController::class, 'index'])->name('partners.index');
    Route::post('/partners/store', [PartnerController::class, 'store'])->name('partners.store');
    Route::get('/partners/show/{partner}', [PartnerController::class, 'show'])->name('partners.show');
    Route::delete('/partners/{partner}', [PartnerController::class, 'destroy'])->name('partners.destroy');

    // SALES SECTION
    // Sales Routes
    Route::get('/sales', [SalesOrderController::class, 'index'])->name('sales.index');
    Route::post('/sales/store', [SalesOrderController::class, 'store'])->name('sales.store'); // admin, sales
    Route::get('/sales/show/{salesOrder}', [SalesOrderController::class, 'show'])->name('sales.show');
    Route::get('/sales/show-payments/{salesOrder}', [SalesOrderController::class, 'showPayments'])->name('sales.showPayments');
    Route::patch('/sales/update/{salesOrder}', [SalesOrderController::class, 'updateStatus'])->name('sales.updateStatus'); // admin, sales, inventory
    Route::get('/sales/print/{salesOrder}', [SalesOrderController::class, 'print'])->name('sales.print'); // admin, sales, inventory
    // Sales Payment Routes
    Route::post('/sales/payments/store', [SalesPaymentController::class, 'store'])->name('sales_payments.store'); // admin, accounting
    Route::delete('/sales/payments/{salesPayment}', [SalesPaymentController::class, 'destroy'])->name('sales_payments.destroy'); // admin, accounting
    

    // PURCHASE SECTION
    // Purchase Routes
    Route::get('/purchases', [PurchaseOrderController::class, 'index'])->name('purchases.index');
    Route::post('/purchases/store', [PurchaseOrderController::class, 'store'])->name('purchases.store'); // admin, purchase
    Route::get('/purchases/show/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('purchases.show');
    Route::get('/purchases/show-payments/{purchaseOrder}', [PurchaseOrderController::class, 'showPayments'])->name('purchases.showPayments');
    Route::patch('/purchases/update/{purchaseOrder}', [PurchaseOrderController::class, 'updateStatus'])->name('purchases.updateStatus'); // admin,purchase, inventory
    Route::get('/purchases/print/{purchaseOrder}', [PurchaseOrderController::class, 'print'])->name('purchases.print'); // admin, purchase, inventory
    // Purchase Payment Routes
    Route::post('/purchases/payments/store', [PurchasePaymentController::class, 'store'])->name('purchase_payments.store'); // admin, accounting
    Route::delete('/purchases/payments/{purchasePayment}', [PurchasePaymentController::class, 'destroy'])->name('purchase_payments.destroy'); // admin, accounting

    // PRODUCTION SECTION
    // Production Routes
    Route::get('/production', [ProductionController::class, 'index'])->name('production.index');
    Route::get('production/show-plan/{product}', [ProductionController::class, 'showPlan'])->name('production.showPlan');
    Route::get('/production/show-plan-details/{productionPlan}', [ProductionController::class, 'showPlanDetails'])->name('production.showPlanDetails');
    Route::post('/production/store-batch', [ProductionController::class, 'storeBatch'])->name('production.storeBatch');
    Route::get('/production/show-realization/{productionBatch}', [ProductionController::class, 'showRealization'])->name('production.showRealization');
    Route::post('/production/store-realization', [ProductionController::class, 'storeRealization'])->name('production.storeRealization');
    


    // REPORTS SECTION
    // Reports Routes
    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
    Route::get('/reports/product-stock-cards', [ReportsController::class, 'showProductReports'])->name('reports.product');
    Route::get('/reports/material-stock-cards', [ReportsController::class, 'showMaterialReports'])->name('reports.material');


    // FORECASTING SECTION
    // Forecasting Routes
    Route::get('/forecast', [ForecastController::class, 'index'])->name('forecast.index');
    Route::get('/forecast/show/{product}', [ForecastController::class, 'show'])->name('forecast.show');
    Route::post('/forecast/generate/{product}', [ForecastController::class, 'generate'])->name('forecast.generate');
    Route::get('/forecast/check-status/{product}', [ForecastController::class, 'checkStatus'])->name('forecast.checkStatus');
    Route::get('/forecast/chart/{productionPlan}', [ForecastController::class, 'showChart'])->name('forecast.chart');
    Route::patch('/forecast/approve/{productionPlan}', [ForecastController::class, 'approvePlan'])->name('forecast.approvePlan'); // admin, production
    
    // Production Plan Routes
    // Route::get('/production-plans/{production_plan}', [ProductionPlanController::class, 'show'])->name('production_plans.show');

    // SETTINGS SECTION
    // Forecasting Routes
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::get('/settings/forecasting', [SettingsController::class, 'forecasting'])->name('settings.forecast');
    Route::put('/settings/forecasting/update-sarima', [SettingsController::class, 'updateSarimaParameters'])->name('settings.updateSarima');
    Route::post('/settings/forecasting/grid-search-all', [SettingsController::class, 'runGridSearchAll'])->name('settings.gridSearchAll');
    // Import Routes
    Route::get('/settings/imports', [SettingsController::class, 'import'])->name('settings.import');
    Route::post('/settings/import/products', [SettingsController::class, 'importProducts'])->name('products.import.excel');
    Route::post('/settings/import/materials', [SettingsController::class, 'importMaterials'])->name('materials.import.excel');
    Route::post('/settings/import/partners', [SettingsController::class, 'importPartners'])->name('partners.import.excel');
    Route::post('/settings/import/recipes', [SettingsController::class, 'importProductMaterials'])->name('product_materials.import.excel');
    // Route::post('/settings/import/sales', [SettingsController::class, 'importSales'])->name('sales.import.excel');
    Route::post('sales-orders/import', [SettingsController::class, 'importSalesOrder'])->name('sales_orders.import.excel');
    Route::post('sales-order-items/import', [SettingsController::class, 'importSalesOrderItems'])->name('sales_order_items.import.excel');

    // User Management Routes
    Route::get('/settings/user-management', [SettingsController::class, 'userManagement'])->name('settings.userManagement');
    Route::post('/settings/user-management/store', [SettingsController::class, 'storeUser'])->name('settings.storeUser');
    Route::delete('/settings/user-management/{user}', [SettingsController::class, 'destroyUser'])->name('settings.destroyUser');
});