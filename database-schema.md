Below is a **complete unified database schema** for your system (Inventory + Repackaging + Multi-Warehouse + POS + Accounting + Due Management).

This is designed for **Laravel + MySQL**, fully normalized and scalable for ERP-level usage.

---

# 🧠 CORE DESIGN PRINCIPLE

You will use **3 core engines**:

1. **Inventory Ledger (physical stock)**
2. **Accounting Ledger (financial stock)**
3. **Reference Documents (sales, imports, transfers, repackaging)**

Everything links through `reference_type + reference_id`.

---

# 🏢 1. USERS & ROLES

## users

* id
* name
* email
* phone
* password
* status
* warehouse_id (nullable)
* created_at

---

## Roles
* We will use spatie roles permissions

---

# 🏬 2. WAREHOUSE MODULE

## warehouses

* id
* name
* code
* address
* manager_id
* status

---

# 📦 3. PRODUCT MODULE

## products

* id
* name
* sku
* type (raw / finished)
* base_unit (kg)
* status

---

## product_variants (sellable items)

* id
* product_id
* name (1kg, 500g)
* sku
* barcode
* unit_qty (e.g. 1, 0.5)
* unit_type (kg/g)
* status

---

# 📥 4. IMPORT MODULE

## imports

* id
* import_no
* supplier_id
* warehouse_id
* date
* total_cost
* status

---

## import_items

* id
* import_id
* product_id
* qty
* unit_cost
* total_cost

---

## batches (VERY IMPORTANT)

Tracks raw stock batches

* id
* batch_no
* product_id
* warehouse_id
* import_id
* qty_in
* qty_out
* cost_per_unit
* remaining_qty
* expiry_date (nullable)

---

# 🔁 5. REPACKAGING MODULE

## repackaging_orders

* id
* ref_no
* warehouse_id
* date
* created_by
* notes

---

## repackaging_inputs

* id
* repackaging_id
* batch_id
* product_id
* qty_used

---

## repackaging_outputs

* id
* repackaging_id
* product_variant_id
* warehouse_id
* qty_produced
* unit_cost
* total_cost

---

## repackaging_adjustments (GAIN/LOSS SUPPORT)

* id
* repackaging_id
* type (gain/loss)
* qty
* reason

---

# 🚚 6. WAREHOUSE TRANSFER MODULE

## stock_transfers

* id
* transfer_no
* from_warehouse_id
* to_warehouse_id
* date
* status (draft/sent/received)
* created_by

---

## stock_transfer_items

* id
* transfer_id
* product_variant_id
* batch_id
* qty

---

# 📊 7. INVENTORY LEDGER (CORE STOCK ENGINE)

## inventory_transactions

* id

* warehouse_id

* product_id

* product_variant_id (nullable)

* batch_id (nullable)

* type:

  * import
  * repack_input
  * repack_output
  * sale
  * return
  * transfer_in
  * transfer_out
  * adjustment
  * damage

* qty_in

* qty_out

* cost

* reference_type (sale/import/repack/transfer/adjustment)

* reference_id

* date

* created_by

---

# 🛒 8. SALES MODULE (POS)

## sales

* id

* invoice_no

* customer_id

* warehouse_id

* date

* subtotal

* discount

* total

* paid_amount

* due_amount

* payment_status (paid/partial/due)

* created_by

---

## sale_items

* id
* sale_id
* product_variant_id
* batch_id
* qty
* unit_price
* total_price

---

## sale_payments

* id
* sale_id
* amount
* method (cash/bank/mobile)
* date
* reference

---

# 👤 9. CUSTOMER MODULE

## customers

* id
* name
* phone
* address
* credit_limit
* total_due

---

# 🚚 10. SUPPLIER MODULE

## suppliers

* id
* name
* phone
* address
* country
* total_payable

---

# 💰 11. ACCOUNTING CORE

## chart_of_accounts

* id
* name
* type (asset/liability/equity/income/expense)
* parent_id (nullable)

---

## journals

* id
* journal_no
* date
* reference_type
* reference_id
* notes
* created_by

---

## journal_entries

* id
* journal_id
* account_id
* type (debit/credit)
* amount

---

## ledger_accounts (optional cache table)

* id
* account_id
* date
* debit
* credit
* balance

---

# 💵 12. CASH & BANK (T-ACCOUNT SYSTEM)

## cashbooks

* id
* date
* account_id (cash/bank)
* description
* debit
* credit
* balance
* reference_type
* reference_id

---

# 📉 13. DUE MANAGEMENT

## customer_dues

* id
* customer_id
* sale_id
* total_amount
* paid_amount
* due_amount
* status

---

## supplier_dues

* id
* supplier_id
* import_id
* total_amount
* paid_amount
* due_amount

---

## payments

* id
* type (customer/supplier)
* reference_id
* amount
* method
* date

---

# 📦 14. STOCK ADJUSTMENTS

## stock_adjustments

* id
* warehouse_id
* product_id
* product_variant_id
* batch_id
* type (add/remove)
* qty
* reason
* status (pending/approved/rejected)
* created_by
* approved_by

---

# 📉 15. EXPENSE MODULE

## expenses

* id
* warehouse_id
* category (transport/labor/packaging)
* amount
* date
* notes
* reference_type
* reference_id

---

# 📊 16. REPORTING SUPPORT TABLES (OPTIONAL CACHE)

## stock_snapshots

* id
* warehouse_id
* product_id
* product_variant_id
* date
* qty

---

## financial_snapshots

* id
* account_id
* date
* balance

---

# 🔐 17. AUDIT LOGS

## activity_logs

* id
* user_id
* action
* reference_type
* reference_id
* description
* created_at

---

# 🔗 18. RELATIONSHIP FLOW (IMPORTANT)

### Example: SALE FLOW

1. sale created
2. sale_items created
3. inventory_transactions (out)
4. journal_entries created
5. cashbook updated
6. customer_due updated

---

### Example: REPACKAGING FLOW

1. repackaging_orders
2. repackaging_inputs (raw stock out)
3. repackaging_outputs (finished stock in)
4. inventory_transactions (both sides)
5. accounting journal entry created

---

### Example: IMPORT FLOW

1. imports
2. import_items
3. batches created
4. inventory_transactions (stock in)
5. journal entry (inventory + payable)

---

# 🧠 FINAL ARCHITECTURE SUMMARY

You now have:

## INVENTORY ENGINE

* Warehouse-based stock
* Batch tracking
* Repackaging (gain/loss)
* Transfers
* Adjustments

## SALES ENGINE

* POS system
* Invoice tracking
* Partial payments

## ACCOUNTING ENGINE

* Double entry system
* Journals
* Ledger
* Cashbook
* Balance sheet
* Profit & loss

## FINANCE ENGINE

* Customer due
* Supplier due
* Payment tracking

## CONTROL LAYER

* Audit logs
* Approval system
* Role permissions

---

# 🚀 RESULT

This schema is strong enough to build:

* Odoo-like ERP (light version)
* FMCG inventory system
* Import/export business ERP
* Warehouse distribution system

---

