<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminApiController;
use App\Http\Controllers\Api\AccountingApiController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\CustomerApiController;

// ─── Public Routes ────────────────────────────────────────────────────────────

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Webhook (no auth)
Route::post('/webhooks/steadfast', [WebhookController::class, 'steadfast']);

// Admin Auth
Route::post('/admin/login', [AuthController::class, 'adminLogin']);
Route::middleware('auth:sanctum')->post('/admin/logout', [AuthController::class, 'adminLogout']);

// Customer Auth
Route::post('/customer/login', [AuthController::class, 'customerLogin']);
Route::middleware('auth:sanctum')->post('/customer/logout', [AuthController::class, 'customerLogout']);

// ─── Admin API Routes ─────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {

    // Dashboard
    Route::get('/dashboard', [AdminApiController::class, 'dashboard'])
        ->middleware('permission:view dashboard');
    Route::get('/notifications', [AdminApiController::class, 'notifications']);
    Route::post('/notifications/mark-read', [AdminApiController::class, 'markNotificationsRead']);

    // Payment Methods (read-only lookup, view products is sufficient)
    Route::get('/payment-methods', [AdminApiController::class, 'paymentMethods'])
        ->middleware('permission:view chart of accounts');

    // Activity Logs
    Route::get('/activity-logs', [AdminApiController::class, 'activityLogs'])
        ->middleware('permission:view activity logs');

    // ── Products ─────────────────────────────────────────────────────────────
    Route::get('/products', [AdminApiController::class, 'products'])
        ->middleware('permission:view products');
    Route::post('/products', [AdminApiController::class, 'storeProduct'])
        ->middleware('permission:create products');
    Route::put('/products/{id}', [AdminApiController::class, 'updateProduct'])
        ->middleware('permission:edit products');
    Route::delete('/products/{id}', [AdminApiController::class, 'destroyProduct'])
        ->middleware('permission:delete products');

    // ── Product Variants ──────────────────────────────────────────────────────
    Route::get('/product-variants/generate-sku', [AdminApiController::class, 'generateProductVariantSku'])
        ->middleware('permission:create product variants|edit product variants');
    Route::post('/product-variants', [AdminApiController::class, 'storeProductVariant'])
        ->middleware('permission:create product variants');
    Route::put('/product-variants/{id}', [AdminApiController::class, 'updateProductVariant'])
        ->middleware('permission:edit product variants');
    Route::delete('/product-variants/{id}', [AdminApiController::class, 'destroyProductVariant'])
        ->middleware('permission:delete product variants');

    // ── Customers ─────────────────────────────────────────────────────────────
    Route::get('/customers', [AdminApiController::class, 'customers'])
        ->middleware('permission:view customers');
    Route::post('/customers', [AdminApiController::class, 'storeCustomer'])
        ->middleware('permission:create customers');
    Route::get('/customers/{id}', [AdminApiController::class, 'showCustomer'])
        ->middleware('permission:view customers');
    Route::put('/customers/{id}', [AdminApiController::class, 'updateCustomer'])
        ->middleware('permission:edit customers');
    Route::delete('/customers/{id}', [AdminApiController::class, 'destroyCustomer'])
        ->middleware('permission:delete customers');
    Route::get('/customers/{id}/ledger', [AdminApiController::class, 'customerLedger'])
        ->middleware('permission:view customers');
    Route::get('/customers/{id}/sales', [AdminApiController::class, 'customerSales'])
        ->middleware('permission:view customers');
    Route::get('/customers/{id}/payments', [AdminApiController::class, 'customerPayments'])
        ->middleware('permission:view customers');

    // ── Suppliers ─────────────────────────────────────────────────────────────
    Route::get('/suppliers', [AdminApiController::class, 'suppliers'])
        ->middleware('permission:view suppliers');
    Route::post('/suppliers', [AdminApiController::class, 'storeSupplier'])
        ->middleware('permission:create suppliers');
    Route::get('/suppliers/{id}', [AdminApiController::class, 'showSupplier'])
        ->middleware('permission:view suppliers');
    Route::get('/suppliers/{id}/purchases', [AdminApiController::class, 'supplierPurchases'])
        ->middleware('permission:view suppliers');
    Route::get('/suppliers/{id}/payments', [AdminApiController::class, 'supplierPayments'])
        ->middleware('permission:view suppliers');
    Route::get('/suppliers/{id}/ledger', [AdminApiController::class, 'supplierLedger'])
        ->middleware('permission:view suppliers');
    Route::put('/suppliers/{id}', [AdminApiController::class, 'updateSupplier'])
        ->middleware('permission:edit suppliers');
    Route::delete('/suppliers/{id}', [AdminApiController::class, 'destroySupplier'])
        ->middleware('permission:delete suppliers');

    // ── Warehouses ────────────────────────────────────────────────────────────
    Route::get('/warehouses', [AdminApiController::class, 'warehouses'])
        ->middleware('permission:view warehouses');
    Route::post('/warehouses', [AdminApiController::class, 'storeWarehouse'])
        ->middleware('permission:create warehouses');
    Route::put('/warehouses/{id}', [AdminApiController::class, 'updateWarehouse'])
        ->middleware('permission:edit warehouses');
    Route::delete('/warehouses/{id}', [AdminApiController::class, 'destroyWarehouse'])
        ->middleware('permission:delete warehouses');

    // ── Imports ───────────────────────────────────────────────────────────────
    Route::get('/import-form-data', [AdminApiController::class, 'importFormData'])
        ->middleware('permission:view imports');
    Route::get('/imports', [AdminApiController::class, 'imports'])
        ->middleware('permission:view imports');
    Route::get('/imports/{id}', [AdminApiController::class, 'showImport'])
        ->middleware('permission:view imports');
    Route::post('/imports', [AdminApiController::class, 'storeImport'])
        ->middleware('permission:create imports');
    Route::put('/imports/{id}', [AdminApiController::class, 'updateImport'])
        ->middleware('permission:edit imports');
    Route::delete('/imports/{id}', [AdminApiController::class, 'destroyImport'])
        ->middleware('permission:delete imports');

    // ── Sales ─────────────────────────────────────────────────────────────────
    Route::get('/sales', [AdminApiController::class, 'sales'])
        ->middleware('permission:view sales');
    Route::post('/sales', [AdminApiController::class, 'storeSale'])
        ->middleware('permission:create sales');
    Route::put('/sales/{id}', [AdminApiController::class, 'updateSale'])
        ->middleware('permission:edit sales');
    Route::delete('/sales/{id}', [AdminApiController::class, 'destroySale'])
        ->middleware('permission:delete sales');
    Route::get('/sales/{id}/pdf', [AdminApiController::class, 'downloadInvoice'])
        ->middleware('permission:view sales');

    // ── Expense Categories ────────────────────────────────────────────────────
    Route::get('/expense-categories/form-data', [AdminApiController::class, 'expenseCategoryFormData'])
        ->middleware('permission:view expenses');
    Route::get('/expense-categories', [AdminApiController::class, 'expenseCategories'])
        ->middleware('permission:view expenses');
    Route::post('/expense-categories', [AdminApiController::class, 'storeExpenseCategory'])
        ->middleware('permission:create expenses');
    Route::put('/expense-categories/{id}', [AdminApiController::class, 'updateExpenseCategory'])
        ->middleware('permission:edit expenses');
    Route::delete('/expense-categories/{id}', [AdminApiController::class, 'destroyExpenseCategory'])
        ->middleware('permission:delete expenses');

    // ── Expenses ──────────────────────────────────────────────────────────────
    Route::get('/expense-form-data', [AdminApiController::class, 'expenseFormData'])
        ->middleware('permission:view expenses');
    Route::get('/expenses', [AdminApiController::class, 'expenses'])
        ->middleware('permission:view expenses');
    Route::post('/expenses', [AdminApiController::class, 'storeExpense'])
        ->middleware('permission:create expenses');
    Route::put('/expenses/{id}', [AdminApiController::class, 'updateExpense'])
        ->middleware('permission:edit expenses');
    Route::delete('/expenses/{id}', [AdminApiController::class, 'destroyExpense'])
        ->middleware('permission:delete expenses');

    // ── Settlements ───────────────────────────────────────────────────────────
    Route::get('/settlements', [AdminApiController::class, 'settlements'])
        ->middleware('permission:view settlements');
    Route::post('/settlements/pay-customer', [AdminApiController::class, 'payCustomer'])
        ->middleware('permission:settle customer dues');
    Route::post('/settlements/pay-supplier', [AdminApiController::class, 'paySupplier'])
        ->middleware('permission:settle supplier payables');

    // ── Stock Transfers ───────────────────────────────────────────────────────
    Route::get('/transfer-form-data', [AdminApiController::class, 'transferFormData'])
        ->middleware('permission:view stock transfers');
    Route::get('/stock-transfers', [AdminApiController::class, 'stockTransfers'])
        ->middleware('permission:view stock transfers');
    Route::get('/stock-transfers/{id}', [AdminApiController::class, 'showStockTransfer'])
        ->middleware('permission:view stock transfers');
    Route::post('/stock-transfers', [AdminApiController::class, 'storeStockTransfer'])
        ->middleware('permission:create stock transfers');
    Route::put('/stock-transfers/{id}', [AdminApiController::class, 'updateStockTransfer'])
        ->middleware('permission:edit stock transfers');
    Route::post('/stock-transfers/{id}/status', [AdminApiController::class, 'updateTransferStatus'])
        ->middleware('permission:update transfer status');
    Route::delete('/stock-transfers/{id}', [AdminApiController::class, 'destroyStockTransfer'])
        ->middleware('permission:delete stock transfers');

    // ── Stock Adjustments ─────────────────────────────────────────────────────
    Route::get('/adjustment-form-data', [AdminApiController::class, 'adjustmentFormData'])
        ->middleware('permission:view stock adjustments');
    Route::get('/stock-adjustments', [AdminApiController::class, 'stockAdjustments'])
        ->middleware('permission:view stock adjustments');
    Route::post('/stock-adjustments', [AdminApiController::class, 'storeStockAdjustment'])
        ->middleware('permission:create stock adjustments');
    Route::post('/stock-adjustments/{id}/status', [AdminApiController::class, 'updateAdjustmentStatus'])
        ->middleware('permission:approve stock adjustments');
    Route::put('/stock-adjustments/{id}', [AdminApiController::class, 'updateStockAdjustment'])
        ->middleware('permission:edit stock adjustments');
    Route::delete('/stock-adjustments/{id}', [AdminApiController::class, 'destroyStockAdjustment'])
        ->middleware('permission:delete stock adjustments');

    // ── Repackaging ───────────────────────────────────────────────────────────
    Route::get('/repackaging-form-data', [AdminApiController::class, 'repackagingFormData'])
        ->middleware('permission:view repackaging');
    Route::get('/repackaging', [AdminApiController::class, 'repackaging'])
        ->middleware('permission:view repackaging');
    Route::get('/repackaging/{id}', [AdminApiController::class, 'showRepackaging'])
        ->middleware('permission:view repackaging');
    Route::post('/repackaging', [AdminApiController::class, 'storeRepackaging'])
        ->middleware('permission:create repackaging');
    Route::put('/repackaging/{id}', [AdminApiController::class, 'updateRepackaging'])
        ->middleware('permission:edit repackaging');
    Route::delete('/repackaging/{id}', [AdminApiController::class, 'destroyRepackaging'])
        ->middleware('permission:delete repackaging');

    // ── Investments ───────────────────────────────────────────────────────────
    Route::get('/investment-form-data', [AdminApiController::class, 'investmentFormData']);
    Route::get('/investments', [AdminApiController::class, 'investments']);
    Route::post('/investments', [AdminApiController::class, 'storeInvestment']);
    Route::put('/investments/{id}', [AdminApiController::class, 'updateInvestment']);
    Route::delete('/investments/{id}', [AdminApiController::class, 'destroyInvestment']);

    // ── Journals ──────────────────────────────────────────────────────────────
    Route::get('/journals', [AdminApiController::class, 'journals'])
        ->middleware('permission:view journals');
    Route::post('/journals', [AdminApiController::class, 'storeJournal'])
        ->middleware('permission:create journals');
    Route::put('/journals/{id}', [AdminApiController::class, 'updateJournal'])
        ->middleware('permission:edit journals');
    Route::delete('/journals/{id}', [AdminApiController::class, 'destroyJournal'])
        ->middleware('permission:delete journals');

    // ── Reports ───────────────────────────────────────────────────────────────
    Route::get('/reports', [AdminApiController::class, 'reports'])
        ->middleware('permission:view stock reports|view sales reports|view financial reports');

    // ── Accounting ────────────────────────────────────────────────────────────
    
    // 💸 Expenses & Categories 💸
    Route::get('/expense-categories/form-data', [AdminApiController::class, 'expenseCategoryFormData'])
        ->middleware('permission:view expenses');
    Route::get('/expense-categories', [AdminApiController::class, 'expenseCategories'])
        ->middleware('permission:view expenses');
    Route::post('/expense-categories', [AdminApiController::class, 'storeExpenseCategory'])
        ->middleware('permission:create expenses');
    Route::put('/expense-categories/{id}', [AdminApiController::class, 'updateExpenseCategory'])
        ->middleware('permission:edit expenses');
    Route::delete('/expense-categories/{id}', [AdminApiController::class, 'destroyExpenseCategory'])
        ->middleware('permission:delete expenses');

    Route::get('/expense-form-data', [AdminApiController::class, 'expenseFormData'])
        ->middleware('permission:view expenses');
    Route::get('/expenses', [AdminApiController::class, 'expenses'])
        ->middleware('permission:view expenses');
    Route::post('/expenses', [AdminApiController::class, 'storeExpense'])
        ->middleware('permission:create expenses');
    Route::put('/expenses/{id}', [AdminApiController::class, 'updateExpense'])
        ->middleware('permission:edit expenses');
    Route::delete('/expenses/{id}', [AdminApiController::class, 'destroyExpense'])
        ->middleware('permission:delete expenses');

    // 📊 Accounting 📊
    Route::get('/accounting/chart-of-accounts', [AccountingApiController::class, 'chartOfAccounts'])
        ->middleware('permission:view chart of accounts');
    Route::get('/accounting/cashbook', [AccountingApiController::class, 'cashbook'])
        ->middleware('permission:view cashbook');
    Route::get('/accounting/balance-sheet', [AccountingApiController::class, 'balanceSheet'])
        ->middleware('permission:view balance sheet');
});

// ─── Customer App Routes ──────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->prefix('customer')->group(function () {
    Route::get('/products', [CustomerApiController::class, 'products']);
    Route::post('/orders', [CustomerApiController::class, 'storeOrder']);
    Route::get('/orders', [CustomerApiController::class, 'orders']);
    Route::get('/orders/{id}', [CustomerApiController::class, 'orderDetails']);
    Route::get('/payments', [CustomerApiController::class, 'payments']);
    Route::get('/dues', [CustomerApiController::class, 'dues']);
    Route::get('/ledger', [CustomerApiController::class, 'ledger']);
    Route::post('/change-password', [CustomerApiController::class, 'changePassword']);
    Route::get('/notifications', [CustomerApiController::class, 'notifications']);
    Route::post('/notifications/mark-read', [CustomerApiController::class, 'markNotificationsRead']);
});