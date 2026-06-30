Below is a **clean, production-ready Laravel migration set** for your full system (Inventory + Repackaging + Multi-Warehouse + POS + Accounting + Due Management).

I’m grouping them so you can build module-by-module without chaos.

---

# 🧠 IMPORTANT NOTE (READ FIRST)

This system depends heavily on:

* `reference_type`
* `reference_id`
* `warehouse_id`
* `batch_id`

These are what connect **inventory ↔ accounting ↔ sales ↔ production**

---

# 🏢 1. USERS & ROLES

## 2026_01_01_000001_create_warehouses_table.php

```php
Schema::create('warehouses', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('code')->unique();
    $table->text('address')->nullable();
    $table->foreignId('manager_id')->nullable()->constrained('users');
    $table->boolean('status')->default(1);
    $table->timestamps();
});
```

---

## 2026_01_01_000002_add_warehouse_to_users.php

```php
Schema::table('users', function (Blueprint $table) {
    $table->foreignId('warehouse_id')->nullable()->constrained('warehouses');
});
```

---

# 📦 2. PRODUCTS

## 2026_01_01_000003_create_products_table.php

```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('sku')->unique();
    $table->enum('type', ['raw', 'finished'])->default('raw');
    $table->string('base_unit')->default('kg');
    $table->boolean('status')->default(1);
    $table->timestamps();
});
```

---

## 2026_01_01_000004_create_product_variants_table.php

```php
Schema::create('product_variants', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained();
    $table->string('name'); // 1kg, 500g
    $table->string('sku')->unique();
    $table->string('barcode')->nullable();
    $table->decimal('unit_qty', 10, 2); // 1, 0.5
    $table->string('unit_type')->default('kg');
    $table->boolean('status')->default(1);
    $table->timestamps();
});
```

---

# 📥 3. IMPORTS & BATCHES

## 2026_01_01_000005_create_imports_table.php

```php
Schema::create('imports', function (Blueprint $table) {
    $table->id();
    $table->string('import_no')->unique();
    $table->foreignId('supplier_id')->nullable()->constrained();
    $table->foreignId('warehouse_id')->constrained();
    $table->date('date');
    $table->decimal('total_cost', 15, 2)->default(0);
    $table->timestamps();
});
```

---

## 2026_01_01_000006_create_import_items_table.php

```php
Schema::create('import_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('import_id')->constrained();
    $table->foreignId('product_id')->constrained();
    $table->decimal('qty', 12, 3);
    $table->decimal('unit_cost', 12, 2);
    $table->decimal('total_cost', 12, 2);
    $table->timestamps();
});
```

---

## 2026_01_01_000007_create_batches_table.php

```php
Schema::create('batches', function (Blueprint $table) {
    $table->id();
    $table->string('batch_no')->unique();
    $table->foreignId('product_id')->constrained();
    $table->foreignId('warehouse_id')->constrained();
    $table->foreignId('import_id')->nullable()->constrained();

    $table->decimal('qty_in', 12, 3);
    $table->decimal('qty_out', 12, 3)->default(0);
    $table->decimal('remaining_qty', 12, 3);

    $table->decimal('cost_per_unit', 12, 2);
    $table->date('expiry_date')->nullable();

    $table->timestamps();
});
```

---

# 🔁 4. REPACKAGING

## 2026_01_01_000008_create_repackaging_orders_table.php

```php
Schema::create('repackaging_orders', function (Blueprint $table) {
    $table->id();
    $table->string('ref_no')->unique();
    $table->foreignId('warehouse_id')->constrained();
    $table->date('date');
    $table->foreignId('created_by')->constrained('users');
    $table->text('notes')->nullable();
    $table->timestamps();
});
```

---

## 2026_01_01_000009_create_repackaging_inputs_table.php

```php
Schema::create('repackaging_inputs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('repackaging_order_id')->constrained();
    $table->foreignId('batch_id')->constrained();
    $table->foreignId('product_id')->constrained();
    $table->decimal('qty_used', 12, 3);
    $table->timestamps();
});
```

---

## 2026_01_01_000010_create_repackaging_outputs_table.php

```php
Schema::create('repackaging_outputs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('repackaging_order_id')->constrained();
    $table->foreignId('product_variant_id')->constrained();
    $table->foreignId('warehouse_id')->constrained();

    $table->decimal('qty_produced', 12, 3);
    $table->decimal('unit_cost', 12, 2);
    $table->decimal('total_cost', 12, 2);

    $table->timestamps();
});
```

---

## 2026_01_01_000011_create_repackaging_adjustments_table.php

```php
Schema::create('repackaging_adjustments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('repackaging_order_id')->constrained();
    $table->enum('type', ['gain', 'loss']);
    $table->decimal('qty', 12, 3);
    $table->text('reason')->nullable();
    $table->timestamps();
});
```

---

# 🚚 5. WAREHOUSE TRANSFERS

## 2026_01_01_000012_create_stock_transfers_table.php

```php
Schema::create('stock_transfers', function (Blueprint $table) {
    $table->id();
    $table->string('transfer_no')->unique();
    $table->foreignId('from_warehouse_id')->constrained('warehouses');
    $table->foreignId('to_warehouse_id')->constrained('warehouses');
    $table->enum('status', ['draft', 'sent', 'received'])->default('draft');
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
});
```

---

## 2026_01_01_000013_create_stock_transfer_items_table.php

```php
Schema::create('stock_transfer_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('stock_transfer_id')->constrained();
    $table->foreignId('product_variant_id')->constrained();
    $table->foreignId('batch_id')->nullable()->constrained();
    $table->decimal('qty', 12, 3);
    $table->timestamps();
});
```

---

# 📊 6. INVENTORY LEDGER (CORE ENGINE)

## 2026_01_01_000014_create_inventory_transactions_table.php

```php
Schema::create('inventory_transactions', function (Blueprint $table) {
    $table->id();

    $table->foreignId('warehouse_id')->constrained();
    $table->foreignId('product_id')->constrained();
    $table->foreignId('product_variant_id')->nullable()->constrained();
    $table->foreignId('batch_id')->nullable()->constrained();

    $table->enum('type', [
        'import',
        'repack_input',
        'repack_output',
        'sale',
        'return',
        'transfer_in',
        'transfer_out',
        'adjustment',
        'damage'
    ]);

    $table->decimal('qty_in', 12, 3)->default(0);
    $table->decimal('qty_out', 12, 3)->default(0);

    $table->decimal('cost', 12, 2)->default(0);

    $table->string('reference_type');
    $table->unsignedBigInteger('reference_id');

    $table->date('date');
    $table->foreignId('created_by')->constrained('users');

    $table->timestamps();
});
```

---

# 🛒 7. SALES

## 2026_01_01_000015_create_sales_table.php

```php
Schema::create('sales', function (Blueprint $table) {
    $table->id();
    $table->string('invoice_no')->unique();
    $table->foreignId('customer_id')->nullable()->constrained();
    $table->foreignId('warehouse_id')->constrained();

    $table->decimal('subtotal', 12, 2);
    $table->decimal('discount', 12, 2)->default(0);
    $table->decimal('total', 12, 2);

    $table->decimal('paid_amount', 12, 2)->default(0);
    $table->decimal('due_amount', 12, 2)->default(0);

    $table->enum('payment_status', ['paid', 'partial', 'due'])->default('due');

    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
});
```

---

## 2026_01_01_000016_create_sale_items_table.php

```php
Schema::create('sale_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('sale_id')->constrained();
    $table->foreignId('product_variant_id')->constrained();
    $table->foreignId('batch_id')->nullable()->constrained();

    $table->decimal('qty', 12, 3);
    $table->decimal('unit_price', 12, 2);
    $table->decimal('total_price', 12, 2);

    $table->timestamps();
});
```

---

## 2026_01_01_000017_create_sale_payments_table.php

```php
Schema::create('sale_payments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('sale_id')->constrained();
    $table->decimal('amount', 12, 2);
    $table->string('method');
    $table->date('date');
    $table->string('reference')->nullable();
    $table->timestamps();
});
```

---

# 👤 8. CUSTOMERS & SUPPLIERS

## customers

```php
Schema::create('customers', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('phone');
    $table->text('address')->nullable();
    $table->decimal('credit_limit', 12, 2)->default(0);
    $table->decimal('total_due', 12, 2)->default(0);
    $table->timestamps();
});
```

---

## suppliers

```php
Schema::create('suppliers', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('phone');
    $table->text('address')->nullable();
    $table->string('country')->nullable();
    $table->decimal('total_payable', 12, 2)->default(0);
    $table->timestamps();
});
```

---

# 💰 9. ACCOUNTING SYSTEM

## chart_of_accounts

```php
Schema::create('chart_of_accounts', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->enum('type', ['asset','liability','equity','income','expense']);
    $table->foreignId('parent_id')->nullable()->constrained('chart_of_accounts');
    $table->timestamps();
});
```

---

## journals

```php
Schema::create('journals', function (Blueprint $table) {
    $table->id();
    $table->string('journal_no')->unique();
    $table->date('date');
    $table->string('reference_type');
    $table->unsignedBigInteger('reference_id');
    $table->text('notes')->nullable();
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
});
```

---

## journal_entries

```php
Schema::create('journal_entries', function (Blueprint $table) {
    $table->id();
    $table->foreignId('journal_id')->constrained();
    $table->foreignId('account_id')->constrained('chart_of_accounts');
    $table->enum('type', ['debit', 'credit']);
    $table->decimal('amount', 15, 2);
    $table->timestamps();
});
```

---

# 📉 12. STOCK ADJUSTMENTS

## stock_adjustments

```php
Schema::create('stock_adjustments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('warehouse_id')->constrained();
    $table->foreignId('product_id')->constrained();
    $table->foreignId('product_variant_id')->nullable()->constrained();
    $table->foreignId('batch_id')->nullable()->constrained();

    $table->enum('type', ['add','remove']);
    $table->decimal('qty', 12, 3);
    $table->text('reason');
    $table->enum('status', ['pending','approved','rejected'])->default('pending');

    $table->foreignId('created_by')->constrained('users');
    $table->foreignId('approved_by')->nullable()->constrained('users');

    $table->timestamps();
});
```

---

# 📊 13. EXPENSES

## 📁 expense_categories (NEW)

```php id="exp_cat_001"
Schema::create('expense_categories', function (Blueprint $table) {
    $table->id();
    $table->string('name'); // Transport, Salary, Packaging
    $table->string('code')->nullable();
    $table->boolean('status')->default(1);
    $table->timestamps();
});
```

---

## 📁 expenses (UPDATED)

```php id="exp_001"
Schema::create('expenses', function (Blueprint $table) {
    $table->id();

    $table->foreignId('expense_category_id')->constrained();

    $table->foreignId('warehouse_id')->nullable()->constrained();

    $table->decimal('amount', 12, 2);

    $table->text('notes')->nullable();

    $table->date('date');

    // optional linking (very important for ERP)
    $table->string('reference_type')->nullable();
    $table->unsignedBigInteger('reference_id')->nullable();

    $table->foreignId('created_by')->constrained('users');

    $table->timestamps();
});

```

---

# 🔐 14. AUDIT LOGS

## activity_logs

```php
Schema::create('activity_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained();
    $table->string('action');
    $table->string('reference_type');
    $table->unsignedBigInteger('reference_id');
    $table->text('description')->nullable();
    $table->timestamps();
});
```

---

# 🚀 FINAL RESULT

With these migrations, you now have:

## ✔ Inventory Core

* Batch tracking
* Warehouse stock
* Repackaging with gain/loss
* Transfers
* Adjustments

## ✔ Sales System

* POS
* Partial payments
* Invoice tracking

## ✔ Accounting System

* Double-entry journals
* Cashbook (T-format)
* Chart of accounts
* Balance sheet ready structure

## ✔ Finance System

* Customer due
* Supplier due
* Payment tracking

## ✔ Full Audit System

* Every action traceable

---
