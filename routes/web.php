<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoutingController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductVariantController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SalePaymentController;
use App\Http\Controllers\ReportController;

require __DIR__ . '/auth.php';

Route::group(['prefix' => '/', 'middleware' => 'auth'], function () {

    // ─── Dashboard ──────────────────────────────────────────────────────────────
    Route::group(['middleware' => ['permission:view dashboard']], function () {
        Route::get('', [RoutingController::class, 'index'])->name('root');
        Route::get('/home', [RoutingController::class, 'index'])->name('home');
    });

    // ─── Warehouses ─────────────────────────────────────────────────────────────
    Route::group(['middleware' => ['permission:view warehouses']], function () {
        Route::get('warehouses', [WarehouseController::class, 'index'])->name('warehouses.index');
    });
    Route::group(['middleware' => ['permission:create warehouses']], function () {
        Route::get('warehouses/create', [WarehouseController::class, 'create'])->name('warehouses.create');
        Route::post('warehouses', [WarehouseController::class, 'store'])->name('warehouses.store');
    });
    Route::group(['middleware' => ['permission:edit warehouses']], function () {
        Route::get('warehouses/{warehouse}/edit', [WarehouseController::class, 'edit'])->name('warehouses.edit');
        Route::put('warehouses/{warehouse}', [WarehouseController::class, 'update'])->name('warehouses.update');
        Route::patch('warehouses/{warehouse}', [WarehouseController::class, 'update']);
    });
    Route::group(['middleware' => ['permission:delete warehouses']], function () {
        Route::delete('warehouses/{warehouse}', [WarehouseController::class, 'destroy'])->name('warehouses.destroy');
    });
    // ─── Units ──────────────────────────────────────────────────────────────────
    Route::group(['middleware' => ['permission:view products']], function () {
        Route::get('units', [\App\Http\Controllers\UnitController::class, 'index'])->name('units.index');
    });
    Route::group(['middleware' => ['permission:create products']], function () {
        Route::post('units', [\App\Http\Controllers\UnitController::class, 'store'])->name('units.store');
    });
    Route::group(['middleware' => ['permission:edit products']], function () {
        Route::put('units/{unit}', [\App\Http\Controllers\UnitController::class, 'update'])->name('units.update');
    });
    Route::group(['middleware' => ['permission:delete products']], function () {
        Route::delete('units/{unit}', [\App\Http\Controllers\UnitController::class, 'destroy'])->name('units.destroy');
    });


    // ─── Products ───────────────────────────────────────────────────────────────
    Route::group(['middleware' => ['permission:view products']], function () {
        Route::get('products', [ProductController::class, 'index'])->name('products.index');
    });
    Route::group(['middleware' => ['permission:create products']], function () {
        Route::get('products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('products', [ProductController::class, 'store'])->name('products.store');
    });
    Route::group(['middleware' => ['permission:edit products']], function () {
        Route::get('products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::patch('products/{product}', [ProductController::class, 'update']);
    });
    Route::group(['middleware' => ['permission:delete products']], function () {
        Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    });

    // ─── Product Variants ───────────────────────────────────────────────────────
    Route::group(['middleware' => ['permission:view products']], function () {
        Route::get('product-variants', [ProductVariantController::class, 'index'])->name('product-variants.index');
    });
    Route::group(['middleware' => ['permission:create product variants']], function () {
        Route::get('product-variants/generate-sku', [ProductVariantController::class, 'generateSku'])->name('product-variants.generate-sku');
        Route::get('product-variants/create', [ProductVariantController::class, 'create'])->name('product-variants.create');
        Route::post('product-variants', [ProductVariantController::class, 'store'])->name('product-variants.store');
    });
    Route::group(['middleware' => ['permission:edit product variants']], function () {
        Route::get('product-variants/{product_variant}/edit', [ProductVariantController::class, 'edit'])->name('product-variants.edit');
        Route::put('product-variants/{product_variant}', [ProductVariantController::class, 'update'])->name('product-variants.update');
        Route::patch('product-variants/{product_variant}', [ProductVariantController::class, 'update']);
    });
    Route::group(['middleware' => ['permission:view products']], function () {
        Route::get('product-variants/{product_variant}', [ProductVariantController::class, 'show'])->name('product-variants.show');
    });
    Route::group(['middleware' => ['permission:delete product variants']], function () {
        Route::delete('product-variants/{product_variant}', [ProductVariantController::class, 'destroy'])->name('product-variants.destroy');
    });

    // ─── Chart of Accounts & Expense Categories ─────────────────────────────────
    Route::group(['middleware' => ['permission:manage chart of accounts']], function () {
        Route::get('coa/create', [App\Http\Controllers\CoaController::class, 'create'])->name('coa.create');
        Route::post('coa', [App\Http\Controllers\CoaController::class, 'store'])->name('coa.store');
        Route::get('coa/{coa}/edit', [App\Http\Controllers\CoaController::class, 'edit'])->name('coa.edit');
        Route::put('coa/{coa}', [App\Http\Controllers\CoaController::class, 'update'])->name('coa.update');
        Route::patch('coa/{coa}', [App\Http\Controllers\CoaController::class, 'update']);
        Route::delete('coa/{coa}', [App\Http\Controllers\CoaController::class, 'destroy'])->name('coa.destroy');
        Route::resource('expense-categories', App\Http\Controllers\ExpenseCategoryController::class);
    });
    Route::group(['middleware' => ['permission:view chart of accounts']], function () {
        Route::get('coa', [App\Http\Controllers\CoaController::class, 'index'])->name('coa.index');
        Route::get('coa/{coa}', [App\Http\Controllers\CoaController::class, 'show'])->name('coa.show');
    });

    Route::resource('investments', \App\Http\Controllers\InvestmentController::class);

    // ─── Customers ──────────────────────────────────────────────────────────────
    Route::group(['middleware' => ['permission:create customers']], function () {
        Route::get('/customers/ajax/search', [CustomerController::class, 'searchAjax'])->name('customers.ajax.search');
        Route::get('customers/create', [CustomerController::class, 'create'])->name('customers.create');
        Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::post('/customers/ajax-store', [CustomerController::class, 'ajaxStore'])->name('customers.ajaxStore');
    });
    Route::group(['middleware' => ['permission:export customers']], function () {
        Route::get('/customers/export', [CustomerController::class, 'export'])->name('customers.export');
    });
    Route::group(['middleware' => ['permission:view customers']], function () {
        Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    });
    Route::group(['middleware' => ['permission:edit customers']], function () {
        Route::get('customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
        Route::put('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        Route::patch('customers/{customer}', [CustomerController::class, 'update']);
    });
    Route::group(['middleware' => ['permission:delete customers']], function () {
        Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
    });

    // ─── Suppliers ──────────────────────────────────────────────────────────────
    Route::group(['middleware' => ['permission:view suppliers']], function () {
        Route::get('suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
        Route::get('suppliers/{supplier}', [SupplierController::class, 'show'])->name('suppliers.show');
    });
    Route::group(['middleware' => ['permission:create suppliers']], function () {
        Route::get('suppliers/create', [SupplierController::class, 'create'])->name('suppliers.create');
        Route::post('suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
    });
    Route::group(['middleware' => ['permission:edit suppliers']], function () {
        Route::get('suppliers/{supplier}/edit', [SupplierController::class, 'edit'])->name('suppliers.edit');
        Route::put('suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
        Route::patch('suppliers/{supplier}', [SupplierController::class, 'update']);
    });
    Route::group(['middleware' => ['permission:delete suppliers']], function () {
        Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
    });

    // ─── Imports ────────────────────────────────────────────────────────────────
    Route::group(['middleware' => ['permission:create imports']], function () {
        Route::get('imports/create', [ImportController::class, 'create'])->name('imports.create');
        Route::post('imports', [ImportController::class, 'store'])->name('imports.store');
    });
    Route::group(['middleware' => ['permission:view imports']], function () {
        Route::get('imports', [ImportController::class, 'index'])->name('imports.index');
        Route::get('imports/{import}', [ImportController::class, 'show'])->name('imports.show');
    });
    Route::group(['middleware' => ['permission:edit imports']], function () {
        Route::get('imports/{import}/edit', [ImportController::class, 'edit'])->name('imports.edit');
        Route::put('imports/{import}', [ImportController::class, 'update'])->name('imports.update');
        Route::patch('imports/{import}', [ImportController::class, 'update']);
    });
    Route::group(['middleware' => ['permission:delete imports']], function () {
        Route::delete('imports/{import}', [ImportController::class, 'destroy'])->name('imports.destroy');
    });

    // ─── Sales / POS ────────────────────────────────────────────────────────────
    Route::group(['middleware' => ['permission:export sales']], function () {
        Route::get('/sales/export', [SaleController::class, 'export'])->name('sales.export');
    });
    Route::group(['middleware' => ['permission:view sales']], function () {
        Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
        Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
    });
    Route::group(['middleware' => ['permission:create sales']], function () {
        Route::get('/pos', [SaleController::class, 'create'])->name('pos.index');
        Route::get('/pos/variants', [SaleController::class, 'ajaxGetVariants'])->name('pos.variants');
        Route::post('/sales', [SaleController::class, 'store'])->name('sales.store');
    });
    Route::group(['middleware' => ['permission:edit sales']], function () {
        Route::get('/sales/{sale}/edit', [SaleController::class, 'edit'])->name('sales.edit');
        Route::put('/sales/{sale}', [SaleController::class, 'update'])->name('sales.update');
        Route::patch('/sales/{sale}', [SaleController::class, 'update']);
        Route::post('/sales/{sale}/update-details', [SaleController::class, 'updateDetails'])->name('sales.updateDetails');
    });
    Route::group(['middleware' => ['permission:delete sales']], function () {
        Route::delete('/sales/{sale}', [SaleController::class, 'destroy'])->name('sales.destroy');
    });
    Route::group(['middleware' => ['permission:print sales']], function () {
        Route::get('/sales/{sale}/print', [SaleController::class, 'print'])->name('sales.print');
        Route::get('/sales/{sale}/pdf', [SaleController::class, 'pdf'])->name('sales.pdf');
    });

    // ─── Sale Payments ──────────────────────────────────────────────────────────
    Route::group(['middleware' => ['permission:view sale payments']], function () {
        Route::get('sales/{sale}/payments', [SalePaymentController::class, 'index'])->name('sale-payments.index');
    });
    Route::group(['middleware' => ['permission:edit sale payments']], function () {
        Route::put('sale-payments/{id}', [SalePaymentController::class, 'update'])->name('sale-payments.update');
        Route::post('sales/{sale}/add-payment', [SalePaymentController::class, 'store'])->name('sale-payments.store');
    });
    Route::group(['middleware' => ['permission:delete sale payments']], function () {
        Route::delete('sale-payments/{id}', [SalePaymentController::class, 'destroy'])->name('sale-payments.destroy');
    });

    // ─── Repackaging ────────────────────────────────────────────────────────────
    Route::group(['middleware' => ['permission:create repackaging']], function () {
        Route::get('repackaging/create', [App\Http\Controllers\RepackagingController::class, 'create'])->name('repackaging.create');
        Route::post('repackaging', [App\Http\Controllers\RepackagingController::class, 'store'])->name('repackaging.store');
    });
    Route::group(['middleware' => ['permission:view repackaging']], function () {
        Route::get('repackaging', [App\Http\Controllers\RepackagingController::class, 'index'])->name('repackaging.index');
        Route::get('repackaging/{repackaging}', [App\Http\Controllers\RepackagingController::class, 'show'])->name('repackaging.show');
    });
    Route::group(['middleware' => ['permission:edit repackaging']], function () {
        Route::get('repackaging/{repackaging}/edit', [App\Http\Controllers\RepackagingController::class, 'edit'])->name('repackaging.edit');
        Route::put('repackaging/{repackaging}', [App\Http\Controllers\RepackagingController::class, 'update'])->name('repackaging.update');
        Route::patch('repackaging/{repackaging}', [App\Http\Controllers\RepackagingController::class, 'update']);
    });
    Route::group(['middleware' => ['permission:delete repackaging']], function () {
        Route::delete('repackaging/{repackaging}', [App\Http\Controllers\RepackagingController::class, 'destroy'])->name('repackaging.destroy');
    });

    // ─── Stock Transfers ────────────────────────────────────────────────────────
    Route::group(['middleware' => ['permission:create stock transfers']], function () {
        Route::get('stock-transfers/create', [App\Http\Controllers\StockTransferController::class, 'create'])->name('stock-transfers.create');
        Route::post('stock-transfers', [App\Http\Controllers\StockTransferController::class, 'store'])->name('stock-transfers.store');
    });
    Route::group(['middleware' => ['permission:view stock transfers']], function () {
        Route::get('stock-transfers', [App\Http\Controllers\StockTransferController::class, 'index'])->name('stock-transfers.index');
        Route::get('stock-transfers/{stock_transfer}', [App\Http\Controllers\StockTransferController::class, 'show'])->name('stock-transfers.show');
    });
    Route::group(['middleware' => ['permission:edit stock transfers']], function () {
        Route::get('stock-transfers/{stock_transfer}/edit', [App\Http\Controllers\StockTransferController::class, 'edit'])->name('stock-transfers.edit');
        Route::put('stock-transfers/{stock_transfer}', [App\Http\Controllers\StockTransferController::class, 'update'])->name('stock-transfers.update');
        Route::patch('stock-transfers/{stock_transfer}', [App\Http\Controllers\StockTransferController::class, 'update']);
    });
    Route::group(['middleware' => ['permission:delete stock transfers']], function () {
        Route::delete('stock-transfers/{stock_transfer}', [App\Http\Controllers\StockTransferController::class, 'destroy'])->name('stock-transfers.destroy');
    });
    Route::group(['middleware' => ['permission:update transfer status']], function () {
        Route::post('/stock-transfers/{stock_transfer}/update-status', [App\Http\Controllers\StockTransferController::class, 'updateStatus'])->name('stock-transfers.updateStatus');
    });

    // ─── Stock Adjustments ──────────────────────────────────────────────────────
    Route::group(['middleware' => ['permission:view stock adjustments']], function () {
        Route::get('stock-adjustments', [App\Http\Controllers\StockAdjustmentController::class, 'index'])->name('stock-adjustments.index');
    });
    Route::group(['middleware' => ['permission:create stock adjustments']], function () {
        Route::get('stock-adjustments/create', [App\Http\Controllers\StockAdjustmentController::class, 'create'])->name('stock-adjustments.create');
        Route::post('stock-adjustments', [App\Http\Controllers\StockAdjustmentController::class, 'store'])->name('stock-adjustments.store');
    });
    Route::group(['middleware' => ['permission:edit stock adjustments']], function () {
        Route::get('stock-adjustments/{stock_adjustment}/edit', [App\Http\Controllers\StockAdjustmentController::class, 'edit'])->name('stock-adjustments.edit');
        Route::put('stock-adjustments/{stock_adjustment}', [App\Http\Controllers\StockAdjustmentController::class, 'update'])->name('stock-adjustments.update');
        Route::patch('stock-adjustments/{stock_adjustment}', [App\Http\Controllers\StockAdjustmentController::class, 'update']);
    });
    Route::group(['middleware' => ['permission:delete stock adjustments']], function () {
        Route::delete('stock-adjustments/{stock_adjustment}', [App\Http\Controllers\StockAdjustmentController::class, 'destroy'])->name('stock-adjustments.destroy');
    });
    Route::group(['middleware' => ['permission:approve stock adjustments']], function () {
        Route::post('/stock-adjustments/{stock_adjustment}/update-status', [App\Http\Controllers\StockAdjustmentController::class, 'updateStatus'])->name('stock-adjustments.updateStatus');
    });

    // ─── Journals & Accounting ──────────────────────────────────────────────────
    Route::group(['middleware' => ['permission:create journals']], function () {
        Route::get('journals/create', [App\Http\Controllers\JournalController::class, 'create'])->name('journals.create');
        Route::post('journals', [App\Http\Controllers\JournalController::class, 'store'])->name('journals.store');
    });
    Route::group(['middleware' => ['permission:view journals']], function () {
        Route::get('journals', [App\Http\Controllers\JournalController::class, 'index'])->name('journals.index');
        Route::get('journals/{journal}', [App\Http\Controllers\JournalController::class, 'show'])->name('journals.show');
        Route::get('/batches', [App\Http\Controllers\BatchController::class, 'index'])->name('batches.index');
        Route::get('/inventory-transactions', [\App\Http\Controllers\InventoryTransactionController::class, 'index'])->name('inventory-transactions.index');
    });
    Route::group(['middleware' => ['permission:edit journals']], function () {
        Route::get('journals/{journal}/edit', [App\Http\Controllers\JournalController::class, 'edit'])->name('journals.edit');
        Route::put('journals/{journal}', [App\Http\Controllers\JournalController::class, 'update'])->name('journals.update');
        Route::patch('journals/{journal}', [App\Http\Controllers\JournalController::class, 'update']);
    });
    Route::group(['middleware' => ['permission:delete journals']], function () {
        Route::delete('journals/{journal}', [App\Http\Controllers\JournalController::class, 'destroy'])->name('journals.destroy');
    });

    // ─── Settlements ────────────────────────────────────────────────────────────
    Route::group(['middleware' => ['permission:view settlements']], function () {
        Route::get('/customer-dues', [App\Http\Controllers\DueSettlementController::class, 'customers'])->name('customer-dues.index');
        Route::get('/supplier-payables', [App\Http\Controllers\DueSettlementController::class, 'suppliers'])->name('supplier-payables.index');
    });
    Route::group(['middleware' => ['permission:settle customer dues']], function () {
        Route::post('/customer-dues/pay', [App\Http\Controllers\DueSettlementController::class, 'payCustomer'])->name('customer-dues.pay');
        Route::get('/customer-dues/{journal}/edit', [App\Http\Controllers\DueSettlementController::class, 'editCustomerPayment'])->name('customer-dues.edit');
        Route::put('/customer-dues/{journal}', [App\Http\Controllers\DueSettlementController::class, 'updateCustomerPayment'])->name('customer-dues.update');
        Route::delete('/customer-dues/{journal}', [App\Http\Controllers\DueSettlementController::class, 'deleteCustomerPayment'])->name('customer-dues.destroy');
    });
    Route::group(['middleware' => ['permission:settle supplier payables']], function () {
        Route::post('/supplier-payables/pay', [App\Http\Controllers\DueSettlementController::class, 'paySupplier'])->name('supplier-payables.pay');
        Route::get('/supplier-payables/{journal}/edit', [App\Http\Controllers\DueSettlementController::class, 'editSupplierPayment'])->name('supplier-payables.edit');
        Route::put('/supplier-payables/{journal}', [App\Http\Controllers\DueSettlementController::class, 'updateSupplierPayment'])->name('supplier-payables.update');
        Route::delete('/supplier-payables/{journal}', [App\Http\Controllers\DueSettlementController::class, 'deleteSupplierPayment'])->name('supplier-payables.destroy');
    });

    // ─── Expenses ───────────────────────────────────────────────────────────────
    Route::group(['middleware' => ['permission:view expenses']], function () {
        Route::get('expenses', [App\Http\Controllers\ExpenseController::class, 'index'])->name('expenses.index');
    });
    Route::group(['middleware' => ['permission:create expenses']], function () {
        Route::get('expenses/create', [App\Http\Controllers\ExpenseController::class, 'create'])->name('expenses.create');
        Route::post('expenses', [App\Http\Controllers\ExpenseController::class, 'store'])->name('expenses.store');
    });
    Route::group(['middleware' => ['permission:edit expenses']], function () {
        Route::get('expenses/{expense}/edit', [App\Http\Controllers\ExpenseController::class, 'edit'])->name('expenses.edit');
        Route::put('expenses/{expense}', [App\Http\Controllers\ExpenseController::class, 'update'])->name('expenses.update');
        Route::patch('expenses/{expense}', [App\Http\Controllers\ExpenseController::class, 'update']);
    });
    Route::group(['middleware' => ['permission:delete expenses']], function () {
        Route::delete('expenses/{expense}', [App\Http\Controllers\ExpenseController::class, 'destroy'])->name('expenses.destroy');
    });

    // ─── Reports Dashboard ──────────────────────────────────────────────────────
    Route::get('/reports', function () {
        return view('reports.index');
    })->name('reports.index');

    // ─── Inventory Reports ──────────────────────────────────────────────────────
    Route::group(['middleware' => ['permission:view stock reports']], function () {
        Route::get('/reports/inventory/stock-summary', [\App\Http\Controllers\InventoryReportController::class, 'stockSummary'])->name('reports.inventory.summary');
        Route::get('/reports/inventory/stock-summary/print', [\App\Http\Controllers\InventoryReportController::class, 'stockSummaryPrint'])->name('reports.inventory.summary.print');
        Route::get('/reports/inventory/stock-warehouse', [\App\Http\Controllers\InventoryReportController::class, 'stockByWarehouse'])->name('reports.inventory.warehouse');
        Route::get('/reports/inventory/stock-date', [\App\Http\Controllers\InventoryReportController::class, 'stockByDate'])->name('reports.inventory.date');
        Route::get('/reports/inventory/batch-movement', [\App\Http\Controllers\InventoryReportController::class, 'batchMovement'])->name('reports.inventory.batch');
    });

    // ─── Sales Reports ──────────────────────────────────────────────────────────
    Route::group(['middleware' => ['permission:view sales reports']], function () {
        Route::get('/reports/sales/daily', [\App\Http\Controllers\SalesReportController::class, 'dailySales'])->name('reports.sales.daily');
        Route::get('/reports/sales/monthly', [\App\Http\Controllers\SalesReportController::class, 'monthlySales'])->name('reports.sales.monthly');
        Route::get('/reports/sales/products', [\App\Http\Controllers\SalesReportController::class, 'productSales'])->name('reports.sales.products');
        Route::get('/reports/sales/profit', [\App\Http\Controllers\SalesReportController::class, 'profitReport'])->name('reports.sales.profit');
    });

    // ─── Production Reports ─────────────────────────────────────────────────────
    Route::group(['middleware' => ['permission:view production reports']], function () {
        Route::get('/reports/production/yield', [\App\Http\Controllers\ProductionReportController::class, 'repackagingYield'])->name('reports.production.yield');
        Route::get('/reports/production/loss-gain', [\App\Http\Controllers\ProductionReportController::class, 'lossGainReport'])->name('reports.production.loss_gain');
        Route::get('/reports/production/batch-cost', [\App\Http\Controllers\ProductionReportController::class, 'costPerBatch'])->name('reports.production.batch_cost');
    });

    // ─── Financial Reports ──────────────────────────────────────────────────────
    Route::group(['middleware' => ['permission:view financial reports']], function () {
        Route::get('/reports/cashbook', [ReportController::class, 'cashbook'])->name('reports.cashbook');
        Route::get('/reports/cashbook/print', [ReportController::class, 'cashbookPrint'])->name('reports.cashbook.print');
        Route::get('/reports/profit-loss', [ReportController::class, 'profitAndLoss'])->name('reports.pl');
        Route::get('/reports/profit-loss/print', [ReportController::class, 'profitAndLossPrint'])->name('reports.pl.print');
        Route::get('/reports/balance-sheet', [ReportController::class, 'balanceSheet'])->name('reports.bs');
        Route::get('/reports/balance-sheet/print', [ReportController::class, 'balanceSheetPrint'])->name('reports.bs.print');
    });

    // ─── Activity Logs ──────────────────────────────────────────────────────────
    Route::group(['middleware' => ['permission:view activity logs']], function () {
        Route::get('/activity-logs', [App\Http\Controllers\ActivityLogController::class, 'index'])->name('activity-logs.index');
    });

    // ─── Users (manage users) ────────────────────────────────────────────────────
    Route::group(['middleware' => ['permission:manage users']], function () {
        Route::get('/users', [App\Http\Controllers\UserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [App\Http\Controllers\UserController::class, 'show'])->name('users.show');
        Route::post('/users', [App\Http\Controllers\UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [App\Http\Controllers\UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [App\Http\Controllers\UserController::class, 'destroy'])->name('users.destroy');
    });

    // ─── Salary Management ──────────────────────────────────────────────────────
    Route::group(['middleware' => ['permission:view salaries']], function () {
        Route::get('/salary', [App\Http\Controllers\SalaryController::class, 'index'])->name('salary.index');
        Route::get('/salary/{user}', [App\Http\Controllers\SalaryController::class, 'show'])->name('salary.show');
    });
    Route::group(['middleware' => ['permission:manage salaries']], function () {
        Route::post('/salary/{user}/payments', [App\Http\Controllers\SalaryController::class, 'storePayment'])->name('salary.payments.store');
        Route::put('/salary/payments/{payment}', [App\Http\Controllers\SalaryController::class, 'updatePayment'])->name('salary.payments.update');
        Route::delete('/salary/payments/{payment}', [App\Http\Controllers\SalaryController::class, 'destroyPayment'])->name('salary.payments.destroy');
    });

    // ─── Roles & Permissions (manage roles) ─────────────────────────────────────
    Route::group(['middleware' => ['permission:manage roles']], function () {
        Route::get('/roles', [App\Http\Controllers\RoleController::class, 'index'])->name('roles.index');
        Route::post('/roles', [App\Http\Controllers\RoleController::class, 'store'])->name('roles.store');
        Route::put('/roles/{role}', [App\Http\Controllers\RoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{role}', [App\Http\Controllers\RoleController::class, 'destroy'])->name('roles.destroy');
    });

    // ─── Catch-all templates (MUST BE LAST) ─────────────────────────────────────
    Route::group(['middleware' => ['permission:view dashboard']], function () {
        Route::get('{first}/{second}/{third}', [RoutingController::class, 'thirdLevel'])->name('third');
        Route::get('{first}/{second}', [RoutingController::class, 'secondLevel'])->name('second');
        Route::get('{any}', [RoutingController::class, 'root'])->name('any');
    });
});
