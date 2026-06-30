H# 🧠 SYSTEM TYPE

This is not a simple inventory system.

It is a:

> **Multi-Warehouse Import + Repackaging + Batch Tracking + POS + Lightweight Manufacturing ERP**

---

# 🏗️ 1. CORE STRUCTURE

## 1.1 Entities Overview

* Products (Raw + Sellable)
* Product Variants (Package sizes)
* Warehouses (Multiple locations)
* Batches (Imported stock tracking)
* Repackaging Jobs (Production process)
* Inventory Ledger (All stock movements)
* Sales (POS system)
* Transfers (Warehouse movement)
* Adjustments (Manual correction system)

---

# 📦 2. PRODUCT SYSTEM

## 2.1 Master Product

Example:

* Soya Bean
* Rice
* Lentil

Fields:

* Name
* SKU
* Category
* Base Unit (kg)

---

## 2.2 Product Variants (Sellable Items)

Each product can have multiple package sizes:

| Product | Variant |
| ------- | ------- |
| Soya    | 1kg     |
| Soya    | 500g    |
| Soya    | 2kg     |

Each variant has:

* SKU
* Barcode
* Default Price (dynamic)
* Status

---

# 🏢 3. WAREHOUSE SYSTEM

## 3.1 Warehouses

Examples:

* Main Warehouse
* Dhaka Warehouse
* Factory Warehouse

Fields:

* Name
* Code
* Location
* Manager

---

## 3.2 Stock is Warehouse-Based

Example:

| Product  | Warehouse | Stock   |
| -------- | --------- | ------- |
| Soya Raw | Main      | 5000 kg |
| Soya 1kg | Dhaka     | 200 pcs |

---

# 📥 4. IMPORT SYSTEM (SHIPMENTS)

## 4.1 Import Shipment

Example:

Shipment #IM-1001

| Product  | Qty    | Cost/kg |
| -------- | ------ | ------- |
| Soya Raw | 5000kg | 100 TK  |

---

## 4.2 Batch Creation

Each import creates a batch:

```
Batch: SOYA-001
Qty: 5000kg
Cost: 100 TK/kg
Warehouse: Main
```

---

# 🔁 5. REPACKAGING / PRODUCTION SYSTEM

## 5.1 Core Concept

Convert raw stock → packaged products

---

## 5.2 Example (Normal Case)

Input:

* 10 kg Soya Raw

Output:

* 10 × 1kg packs

---

## 5.3 Example (Gain Case)

Input:

* 10 kg raw

Output:

* 11 × 1kg packs

Gain:

* +1kg (humidity / process variation)

---

## 5.4 Example (Loss Case)

Input:

* 10 kg raw

Output:

* 9.5 kg packaged

Loss:

* 0.5 kg

---

## 5.5 Repackaging Record

### Header

* Date
* Warehouse
* Operator

### Inputs

| Batch    | Qty Used |
| -------- | -------- |
| SOYA-001 | 5kg      |
| SOYA-002 | 5kg      |

### Outputs

| Variant | Qty    |
| ------- | ------ |
| 1kg     | 11 pcs |

---


---

# 💰 6. PRICE MANAGEMENT

## 6.1 Dynamic Price System

Same product can have different price per batch/time.

Example:

| Date | Product  | Variant | Price |
| ---- | -------- | ------- | ----- |
| Jan  | Soya 1kg | 120 TK  |       |
| Feb  | Soya 1kg | 140 TK  |       |

---

## 6.2 Rule

* Never overwrite old price
* Always keep price history

---

## 6.3 POS Behavior

When clicking product:

* Load latest price
* Allow manual override

---

# 🏬 7. MULTI-WAREHOUSE TRANSFER

## 7.1 Transfer Example

Transfer:

* 50 pcs Soya 1kg

From:

* Main Warehouse

To:

* Dhaka Warehouse

---

## 7.2 Transfer Status Flow

1. Draft
2. Sent
3. In Transit
4. Received

---

## 7.3 Stock Effect

On receive:

Main:
-50 pcs

Dhaka:
+50 pcs

---

# 📊 8. STOCK ADJUSTMENT SYSTEM

## 8.1 Use Cases

* Damage
* Theft
* Physical mismatch
* Expired goods

---

## 8.2 Example (Positive)

System: 95 pcs
Physical: 100 pcs
Adjustment: +5 pcs

---

## 8.3 Example (Negative)

System: 100 pcs
Physical: 97 pcs
Adjustment: -3 pcs

---

## 8.4 Approval System

* Created by Storekeeper
* Approved by Manager

---

# 📦 9. INVENTORY LEDGER (CORE ENGINE)

EVERYTHING goes here.

---

## 9.1 Transaction Types

* Import
* Repackaging Input
* Repackaging Output
* Transfer In
* Transfer Out
* Sale
* Return
* Adjustment
* Damage

---

## 9.2 Example Ledger

| Date  | Warehouse | Product  | In   | Out |
| ----- | --------- | -------- | ---- | --- |
| Jan 1 | Main      | Soya Raw | 5000 | 0   |
| Jan 2 | Main      | Soya Raw | 0    | 10  |
| Jan 2 | Main      | Soya 1kg | 11   | 0   |
| Jan 3 | Dhaka     | Soya 1kg | 50   | 0   |

---

## 9.3 Benefit

* Stock on any date
* Full audit trail
* No data corruption

---

# 🧾 10. SALES / POS SYSTEM

## 10.1 POS Flow

1. Select product
2. System loads latest price
3. Admin adjusts quantity
4. Optional discount
5. Confirm sale

---

## 10.2 Example

Product:

* Soya 1kg

System price:

* 140 TK

Admin changes:

* 135 TK

Qty:

* 3 pcs

Total:

* 405 TK

---

## 10.3 Stock Deduction

FIFO rule:

Oldest batch sells first.

---

# 👤 11. CUSTOMER SYSTEM

* Name
* Phone
* Address
* Credit limit
* Due tracking

---

# 🚚 12. SUPPLIER SYSTEM

* Supplier name
* Country
* Contact
* Import history

---

# 📦 13. WAREHOUSE STOCK VIEW

### Raw Stock

| Product | Qty |

### Packaged Stock

| Product | Variant | Qty |

---

# 📉 14. REPORT SYSTEM

## Inventory Reports

* Stock Summary
* Stock by Warehouse
* Stock by Date
* Batch Movement

---

## Sales Reports

* Daily Sales
* Monthly Sales
* Product Sales
* Profit Report

---

## Production Reports

* Repackaging Yield
* Loss/Gain Report
* Cost per Batch

---

# 💹 15. PROFIT ENGINE

## Example

Cost:

* 1000 TK

Sales:

* 1400 TK

Profit:

* 400 TK

---

Breakdown:

* Raw cost
* Packaging cost
* Labor cost
* Transport cost

---

# 📱 16. FLUTTER MOBILE APP

## Features

### Dashboard

* Sales today
* Stock alerts

### Inventory

* Scan barcode
* View stock

### POS

* Sell items
* Offline support

### Repackaging

* Create production entry

### Transfer

* Approve/receive stock

---

# 📡 17. BARCODE / QR SYSTEM

Used for:

* Batches
* Product variants
* Transfers

---

# 🔐 18. ROLE & PERMISSION SYSTEM

Roles:

* Admin
* Manager
* Warehouse Staff
* Salesman

Permissions:

* Repackaging
* Transfer approval
* Price editing
* Stock adjustment

---

# 🧾 19. AUDIT SYSTEM

Track:

* Who changed price
* Who adjusted stock
* Who approved transfer
* Who sold what

---

# ⚙️ 20. SYSTEM DESIGN PRINCIPLE (VERY IMPORTANT)

### NEVER UPDATE STOCK DIRECTLY

Always:

> Insert a ledger entry → calculate stock dynamically

---

# 🧾 21. ACCOUNTING SYSTEM (ERP CORE MODULE)

Your accounting system will follow **double-entry bookkeeping** integrated with every business action:

* Import
* Repackaging
* Sales
* Payments
* Expenses
* Adjustments

---

# 📚 21.1 CHART OF ACCOUNTS (COA)

## Account Types

* Assets
* Liabilities
* Equity
* Income
* Expense

---

## Example Accounts

### Assets

* Cash in Hand
* Bank Account
* Inventory (Raw)
* Inventory (Finished Goods)
* Accounts Receivable (Customer Due)

### Liabilities

* Accounts Payable (Supplier Due)

### Income

* Sales Revenue

### Expense

* Import Cost
* Packaging Cost
* Transport
* Salary
* Loss / Damage

---

# 🔁 21.2 DOUBLE ENTRY PRINCIPLE

Every transaction must affect at least **two accounts**.

Example:

### Sale Example

Customer buys 1kg Soya = 140 TK

```
Cash / Accounts Receivable  Dr 140
    Sales Revenue               Cr 140
```

Stock reduction is handled separately in inventory ledger.

---

# 📥 21.3 IMPORT ACCOUNTING ENTRY

Example:

Imported goods worth 10,000 TK

```
Inventory (Raw)       Dr 10,000
    Accounts Payable        Cr 10,000
```

When paid:

```
Accounts Payable      Dr 10,000
    Cash                    Cr 10,000
```

---

# 🔁 21.4 REPACKAGING ACCOUNTING

Example:

Raw → Packaged goods

Cost movement only:

```
Finished Goods Inventory   Dr XXX
    Raw Inventory               Cr XXX
```

Optional:

* Packaging cost
* Labor cost

```
Finished Goods Inventory   Dr 800
Expense (Labor)            Dr 200
    Raw Inventory               Cr 1000
```

---

# 💰 22. DUE MANAGEMENT SYSTEM

This is critical for your business.

---

## 22.1 CUSTOMER DUE

When sale is NOT fully paid:

Example:

Sale = 1000 TK
Paid = 600 TK
Due = 400 TK

### Journal Entry:

```
Accounts Receivable   Dr 1000
    Sales Revenue          Cr 1000

Cash                  Dr 600
    Accounts Receivable    Cr 600
```

Remaining:

* Customer Due = 400 TK

---

## 22.2 SUPPLIER DUE

Example:

Imported goods = 5000 TK
Paid = 2000 TK
Due = 3000 TK

```
Inventory              Dr 5000
    Accounts Payable       Cr 5000

Accounts Payable      Dr 2000
    Cash                   Cr 2000
```

---

## 22.3 DUE TRACKING FEATURES

### Customer Side

* Total Due
* Paid Amount
* Remaining Due
* Payment history
* Credit limit warning

### Supplier Side

* Payable balance
* Partial payments
* Aging report

---

## 22.4 DUE AGING REPORT

| Customer | 0–30 Days | 30–60 | 60+ | Total |
| -------- | --------- | ----- | --- | ----- |
| ABC      | 500       | 200   | 100 | 800   |

---

# 📒 23. CASHBOOK (T-FORMAT)

## 23.1 Cashbook Structure

### Example:

| Date  | Description | Debit (In) | Credit (Out) | Balance |
| ----- | ----------- | ---------- | ------------ | ------- |
| Jan 1 | Opening     | 10000      | 0            | 10000   |
| Jan 2 | Sale        | 5000       | 0            | 15000   |
| Jan 3 | Purchase    | 0          | 3000         | 12000   |

---

## 23.2 CASHBOOK TYPES

* Cash in Hand
* Bank Cashbook
* Mobile Money (optional: bKash, etc.)

---

# 📊 24. JOURNAL SYSTEM (GENERAL LEDGER)

## 24.1 Journal Entry Structure

Each transaction creates journal entries.

### Example:

Sale 1000 TK:

```
Journal No: JRN-001

Dr Accounts Receivable  1000
    Cr Sales Revenue        1000
```

---

## 24.2 Journal Fields

* Date
* Reference Type (Sale, Purchase, Adjustment)
* Reference ID
* Debit Account
* Credit Account
* Amount
* Notes

---

## 24.3 AUTOMATED JOURNALS

System auto-generates journals for:

* Sales
* Purchases
* Repackaging
* Transfers
* Stock adjustments
* Expenses

---

# 📘 25. GENERAL LEDGER

Ledger per account:

### Example: Cash Account

| Date    | Description | Dr   | Cr  | Balance |
| ------- | ----------- | ---- | --- | ------- |
| Sale    | Cash In     | 1000 | 0   | 1000    |
| Expense | Rent        | 0    | 200 | 800     |

---

# 📊 26. BALANCE SHEET

Generated from accounting data.

---

## 26.1 Structure

### Assets

* Cash
* Bank
* Inventory
* Receivables

### Liabilities

* Payables
* Loans

### Equity

* Capital
* Retained Earnings

---

## 26.2 Example

### Assets

* Cash: 10,000
* Inventory: 50,000
* Receivables: 5,000

### Liabilities

* Payables: 20,000

### Equity

* 45,000

---

# 📈 27. PROFIT & LOSS (P&L)

## 27.1 Income

* Sales Revenue

## 27.2 Expenses

* Import Cost
* Packaging
* Transport
* Salary
* Loss

---

## Example:

Revenue:

* 100,000

Expenses:

* 70,000

Net Profit:

* 30,000

---

# 🔗 28. INTEGRATION WITH INVENTORY

This is VERY IMPORTANT.

Every inventory movement triggers accounting:

---

## Example Flow:

### Sale happens:

1. Inventory decreases
2. Cash/Receivable increases
3. Revenue recorded
4. Journal entry created

---

### Import happens:

1. Stock increases
2. Liability increases (Supplier due)

---

### Repackaging:

1. Raw inventory decreases
2. Finished goods increases
3. Cost transferred

---

### Adjustment:

* Loss → Expense account
* Gain → Other income (or inventory correction)

---

# 🧠 29. SYSTEM DESIGN PRINCIPLE

## Golden Rule:

> Inventory = Physical Stock
> Accounting = Financial Value
> Ledger = Source of truth

Never mix them.

---

# 📱 30. FLUTTER ACCOUNTING FEATURES

* View cashbook
* View profit/loss
* Customer due list
* Supplier due list
* Invoice details
* Payment collection
* Real-time dashboard

---

# 🧩 FINAL SYSTEM (FULL ERP)

Now your system becomes:

## CORE MODULES

### Inventory Side

* Multi warehouse stock
* Import batches
* Repackaging (gain/loss supported)
* Transfers
* Adjustments
* Barcode tracking

### Sales Side

* POS system
* Dynamic pricing
* Customer management
* Credit sales

### Accounting Side

* Double entry system
* Chart of accounts
* Journals
* Cashbook (T-format)
* Ledger system
* Balance sheet
* Profit & loss

### Finance Side

* Customer due
* Supplier due
* Aging reports
* Payment tracking

---