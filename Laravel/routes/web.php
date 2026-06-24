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
    Route::get('/products/show/{product}', [ProductController::class, 'show'])->name('products.show');  
    Route::patch('/products/update/{product}', [ProductController::class, 'update'])->name('products.update'); // admin, sales [DONE]
    Route::post('/products/store', [ProductController::class, 'store'])->name('products.store'); // admin, sales [DONE]
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy'); // admin, sales [DONE]
    Route::post('/products/cost-adjustment', [ProductController::class, 'costAdjustment'])->name('products.costAdjustment'); // admin, produksi [DONE]
    Route::post('/products/stock-adjustment', [ProductController::class, 'stockAdjustment'])->name('products.stockAdjustment'); // admin, inventory [DONE]
    Route::post('/products/update-product-lead-time-safety-stock', [ProductController::class, 'updateProductLeadTimeSafetyStock'])->name('products.updateProductLeadTimeSafetyStock'); // admin, inventory, production [DONE]
    // Product Material Routes, 
    Route::post('/product-materials/store', [ProductMaterialController::class, 'store'])->name('product_materials.store'); // admin, production [DONE]
    Route::delete('/product-materials/{product_material}', [ProductMaterialController::class, 'destroy'])->name('product_materials.destroy'); // admin, production [DONE]

    
    // MATERIAL SECTION
    // Material Routes
    Route::get('/materials', [MaterialController::class, 'index'])->name('materials.index');
    Route::get('/materials/create', [MaterialController::class, 'create'])->name('materials.create');
    Route::get('/materials/edit/{material}', [MaterialController::class, 'edit'])->name('materials.edit'); 
    Route::get('/materials/show/{material}', [MaterialController::class, 'show'])->name('materials.show');
    Route::patch('/materials/update/{material}', [MaterialController::class, 'update'])->name('materials.update'); // admin, purchase [DONE]
    Route::post('/materials/store', [MaterialController::class, 'store'])->name('materials.store'); // admin, purchase [DONE]
    Route::delete('/materials/{material}', [MaterialController::class, 'destroy'])->name('materials.destroy'); // admin, purchase [DONE]
    Route::post('/materials/cost-adjustment', [MaterialController::class, 'costAdjustment'])->name('materials.costAdjustment'); // admin, purchase [DONE]
    Route::post('/materials/stock-adjustment', [MaterialController::class, 'stockAdjustment'])->name('materials.stockAdjustment'); // admin, inventory [DONE]
    Route::post('/materials/update-material-lead-time-safety-stock-rop', [MaterialController::class, 'updateMaterialLeadTimeSafetyStockROP'])->name('materials.updateMaterialLeadTimeSafetyStockROP'); // admin, inventory, production [DONE]


    // PARTNER SECTION
    // Partner Routes
    Route::get('/partners', [PartnerController::class, 'index'])->name('partners.index');
    Route::get('/partners/show/{partner}', [PartnerController::class, 'show'])->name('partners.show');
    Route::post('/partners/store', [PartnerController::class, 'store'])->name('partners.store'); // admin, sales, purchase [DONE]
    Route::delete('/partners/{partner}', [PartnerController::class, 'destroy'])->name('partners.destroy'); // admin, sales, purchase [DONE]
    Route::get('partners/pricelist/{partner}', [PartnerController::class, 'showPricelist'])->name('partners.pricelist');
    Route::post('/partners/store-pricelist/{partner}', [PartnerController::class, 'storePricelist'])->name('partners.storePricelist');
    Route::delete('/partners/{partner}/pricelist/{id}', [PartnerController::class, 'destroyPricelist'])->name('partners.destroyPricelist');
   
   
    // SALES SECTION
    // Sales Routes
    Route::get('/sales', [SalesOrderController::class, 'index'])->name('sales.index');
    Route::get('/sales/show/{salesOrder}', [SalesOrderController::class, 'show'])->name('sales.show');
    Route::get('/sales/show-payments/{salesOrder}', [SalesOrderController::class, 'showPayments'])->name('sales.showPayments');
    Route::post('/sales/store', [SalesOrderController::class, 'store'])->name('sales.store'); // admin, sales [DONE]
    Route::patch('/sales/update/{salesOrder}', [SalesOrderController::class, 'updateStatus'])->name('sales.updateStatus'); // admin, sales, inventory [DONE]
    Route::get('/sales/print/{salesOrder}', [SalesOrderController::class, 'print'])->name('sales.print'); // admin, sales, inventory [DONE]
    Route::patch('/sales/shipping/{salesOrder}', [SalesOrderController::class, 'updateShippingInfo'])->name('sales.updateShippingInfo'); // admin, inventory [DONE]
    Route::patch('/sales/verify-shipping/{salesOrder}', [SalesOrderController::class, 'verifyShippingPayment'])->name('sales.verifyShippingPayment'); // admin, accounting [DONE]
    // Sales Payment Routes
    Route::post('/sales/payments/store', [SalesPaymentController::class, 'store'])->name('sales_payments.store'); // admin, accounting [DONE]
    Route::delete('/sales/payments/{salesPayment}', [SalesPaymentController::class, 'destroy'])->name('sales_payments.destroy'); // admin, accounting [DONE]
   

    // PURCHASE SECTION
    // Purchase Routes
    Route::get('/purchases', [PurchaseOrderController::class, 'index'])->name('purchases.index');
    Route::get('/purchases/show/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('purchases.show');
    Route::get('/purchases/show-payments/{purchaseOrder}', [PurchaseOrderController::class, 'showPayments'])->name('purchases.showPayments');
    Route::post('/purchases/store', [PurchaseOrderController::class, 'store'])->name('purchases.store'); // admin, purchase [DONE]
    Route::patch('/purchases/update/{purchaseOrder}', [PurchaseOrderController::class, 'updateStatus'])->name('purchases.updateStatus'); // admin, purchase, inventory [DONE]
    Route::get('/purchases/print/{purchaseOrder}', [PurchaseOrderController::class, 'print'])->name('purchases.print'); // admin, purchase [DONE]
    Route::patch('/purchases/shipping/{purchaseOrder}', [PurchaseOrderController::class, 'updateShippingInfo'])->name('purchases.updateShippingInfo'); // admin, inventory [DONE]
    Route::patch('/purchases/verify-shipping/{purchaseOrder}', [PurchaseOrderController::class, 'verifyShippingPayment'])->name('purchases.verifyShippingPayment'); // admin, accounting [DONE]
    // Purchase Payment Routes
    Route::post('/purchases/payments/store', [PurchasePaymentController::class, 'store'])->name('purchase_payments.store'); // admin, accounting [DONE]
    Route::delete('/purchases/payments/{purchasePayment}', [PurchasePaymentController::class, 'destroy'])->name('purchase_payments.destroy'); // admin, accounting [DONE]

    // PRODUCTION SECTION
    // Production Routes
    Route::get('/production', [ProductionController::class, 'index'])->name('production.index');
    Route::get('production/show-plan/{product}', [ProductionController::class, 'showPlan'])->name('production.showPlan');
    Route::get('/production/show-plan-details/{productionPlan}', [ProductionController::class, 'showPlanDetails'])->name('production.showPlanDetails');
    Route::get('/production/show-realization/{productionBatch}', [ProductionController::class, 'showRealization'])->name('production.showRealization'); 
    Route::post('/production/store-batch', [ProductionController::class, 'storeBatch'])->name('production.storeBatch'); // admin, production [DONE]
    Route::post('/production/store-realization', [ProductionController::class, 'storeRealization'])->name('production.storeRealization'); // admin, production [DONE]
    Route::post('/production/create-current-month-plan/{product}', [ProductionController::class, 'createCurrentMonthProductionPlan'])->name('production.createCurrentMonthProductionPlan'); // admin, purchase, production [DONE]
    

    // REPORTS SECTION
    // Reports Routes
    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
    Route::get('/reports/product-stock-cards', [ReportsController::class, 'showProductReports'])->name('reports.product');
    Route::get('/reports/material-stock-cards', [ReportsController::class, 'showMaterialReports'])->name('reports.material');
    Route::get('/reports/income-statement-report', [ReportsController::class, 'showIncomeStatement'])->name('reports.incomeStatement');
    Route::get('/reports/product-chart/{product}', [ReportsController::class, 'showProductChart'])->name('reports.productChart');
    Route::get('/reports/material-chart/{material}', [ReportsController::class, 'showMaterialChart'])->name('reports.materialChart');



    // FORECASTING SECTION
    // Forecasting Routes
    Route::get('/forecast', [ForecastController::class, 'index'])->name('forecast.index');
    Route::get('/forecast/show/{product}', [ForecastController::class, 'show'])->name('forecast.show');
    Route::get('/forecast/check-status/{product}', [ForecastController::class, 'checkStatus'])->name('forecast.checkStatus');
    Route::get('/forecast/chart/{productionPlan}', [ForecastController::class, 'showChart'])->name('forecast.chart');
    Route::post('/forecast/generate/{product}', [ForecastController::class, 'generate'])->name('forecast.generate'); // admin, purchase, production [DONE]
    Route::patch('/forecast/approve/{productionPlan}', [ForecastController::class, 'approvePlan'])->name('forecast.approvePlan'); // admin, production [DONE]
    
    // Production Plan Routes
    // Route::get('/production-plans/{production_plan}', [ProductionPlanController::class, 'show'])->name('production_plans.show');

    // SETTINGS SECTION
    // Forecasting Routes
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::get('/settings/forecasting', [SettingsController::class, 'forecasting'])->name('settings.forecast');
    Route::put('/settings/forecasting/update-sarima', [SettingsController::class, 'updateSarimaParameters'])->name('settings.updateSarima'); // admin [DONE]
    Route::post('/settings/forecasting/grid-search-all', [SettingsController::class, 'runGridSearchAll'])->name('settings.gridSearchAll'); // admin, production [DONE]
    Route::delete('/settings/evaluations/clear', [SettingsController::class, 'clearEvaluations'])->name('settings.clearEvaluations');
    Route::delete('/settings/evaluations/{id}', [SettingsController::class, 'deleteEvaluation'])->name('settings.deleteEvaluation');
    Route::post('/settings/grid-search-all/cancel', [SettingsController::class, 'cancelGridSearch'])->name('settings.cancelGridSearch');
    
    // Import Routes
    Route::get('/settings/imports', [SettingsController::class, 'import'])->name('settings.import'); 
    Route::post('/settings/import/products', [SettingsController::class, 'importProducts'])->name('products.import.excel'); // admin
    Route::post('/settings/import/materials', [SettingsController::class, 'importMaterials'])->name('materials.import.excel'); // admin [DONE]
    Route::post('/settings/import/partners', [SettingsController::class, 'importPartners'])->name('partners.import.excel'); // admin [DONE]
    Route::post('/settings/import/recipes', [SettingsController::class, 'importProductMaterials'])->name('product_materials.import.excel'); // admin [DONE]
    Route::post('/settings/import/sales', [SettingsController::class, 'importSalesOrder'])->name('sales_orders.import.excel'); // admin [DONE]

    // User Management Routes
    Route::get('/settings/user-management', [SettingsController::class, 'userManagement'])->name('settings.userManagement'); // admin [DONE]
    Route::post('/settings/user-management/store', [SettingsController::class, 'storeUser'])->name('settings.storeUser'); // admin [DONE]
    Route::delete('/settings/user-management/{user}', [SettingsController::class, 'destroyUser'])->name('settings.destroyUser'); // admin [DONE]
});