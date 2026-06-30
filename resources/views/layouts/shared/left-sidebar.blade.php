<!-- ========== Left Sidebar Start ========== -->
<div class="leftside-menu">

    <!-- Brand Logo Light -->
    <a href="{{ route('any', 'index') }}" class="logo logo-light">
        <span class="logo-lg">
            <img src="/images/logo.png" alt="logo">
        </span>
        <span class="logo-sm">
            <img src="/images/logo-sm.png" alt="small logo">
        </span>
    </a>

    <!-- Brand Logo Dark -->
    <a href="{{ route('any', 'index') }}" class="logo logo-dark">
        <span class="logo-lg">
            <img src="/images/logo-dark.png" alt="logo">
        </span>
        <span class="logo-sm">
            <img src="/images/logo-sm.png" alt="small logo">
        </span>
    </a>

    <!-- Sidebar Hover Menu Toggle Button -->
    <div class="button-sm-hover" data-bs-toggle="tooltip" data-bs-placement="right" title="Show Full Sidebar">
        <i class="ri-checkbox-blank-circle-line align-middle"></i>
    </div>

    <!-- Full Sidebar Menu Close Button -->
    <div class="button-close-fullsidebar">
        <i class="ri-close-fill align-middle"></i>
    </div>

    <!-- Sidebar -left -->
    <div class="h-100" id="leftside-menu-container" data-simplebar>
        <!-- Leftbar User -->
        <div class="leftbar-user p-3 text-white">
            <a href="{{ route('second', ['pages', 'profile']) }}" class="d-flex align-items-center text-reset">
                <div class="flex-shrink-0">
                    <img src="/images/users/avatar-1.jpg" alt="user-image" height="42" class="rounded-circle shadow">
                </div>
                <div class="flex-grow-1 ms-2">
                    <span class="fw-semibold fs-15 d-block">{{ auth()->user()->name }}</span>
                    <span class="fs-13">{{ auth()->user()->roles->first()->name ?? 'User' }}</span>
                </div>
                <div class="ms-auto">
                    <i class="ri-arrow-right-s-fill fs-20"></i>
                </div>
            </a>
        </div>

        <!--- Sidemenu -->
        <ul class="side-nav">

            <li class="side-nav-title mt-1">Main</li>

            @can('view dashboard')
            <li class="side-nav-item">
                <a href="{{ route('home') }}" class="side-nav-link">
                    <i class="ri-dashboard-2-fill"></i>
                    <span> Dashboard </span>
                </a>
            </li>
            @endcan

            {{-- ── Inventory ──────────────────────────────────────────── --}}
            @canany(['view warehouses', 'view products', 'view imports', 'view journals'])
            <li class="side-nav-title mt-2">Inventory</li>
            @endcanany

            @can('view warehouses')
            <li class="side-nav-item">
                <a href="{{ route('warehouses.index') }}" class="side-nav-link">
                    <i class="ri-hotel-line"></i>
                    <span> Warehouses </span>
                </a>
            </li>
            @endcan

            @canany(['view products', 'create product variants', 'edit product variants'])
            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarProducts" aria-expanded="false" aria-controls="sidebarProducts" class="side-nav-link">
                    <i class="ri-archive-fill"></i>
                    <span> Products </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarProducts">
                    <ul class="side-nav-second-level">
                        @can('view products')
                        <li>
                            <a href="{{ route('products.index') }}">Master Products</a>
                        </li>
                        @endcan
                        @canany(['create product variants', 'edit product variants', 'view products'])
                        <li>
                            <a href="{{ route('product-variants.index') }}">Product Variants</a>
                        </li>
                        @endcanany
                    </ul>
                </div>
            </li>
            @endcanany

            @can('view imports')
            <li class="side-nav-item">
                <a href="{{ route('imports.index') }}" class="side-nav-link">
                    <i class="ri-download-2-fill"></i>
                    <span> Imports (Shipments) </span>
                </a>
            </li>
            @endcan

            @can('view journals')
            <li class="side-nav-item">
                <a href="{{ route('batches.index') }}" class="side-nav-link">
                    <i class="ri-barcode-box-fill"></i>
                    <span> Batch Tracking </span>
                </a>
            </li>
            @endcan

            {{-- ── Production ─────────────────────────────────────────── --}}
            @can('view repackaging')
            <li class="side-nav-title mt-2">Production</li>
            <li class="side-nav-item">
                <a href="{{ route('repackaging.index') }}" class="side-nav-link">
                    <i class="ri-loop-left-fill"></i>
                    <span> Repackaging Orders </span>
                </a>
            </li>
            @endcan

            {{-- ── Logistics & Control ────────────────────────────────── --}}
            @canany(['view stock transfers', 'view stock adjustments', 'view journals'])
            <li class="side-nav-title mt-2">Logistics &amp; Control</li>
            @endcanany

            @can('view stock transfers')
            <li class="side-nav-item">
                <a href="{{ route('stock-transfers.index') }}" class="side-nav-link">
                    <i class="ri-truck-fill"></i>
                    <span> Stock Transfers </span>
                </a>
            </li>
            @endcan

            @can('view stock adjustments')
            <li class="side-nav-item">
                <a href="{{ route('stock-adjustments.index') }}" class="side-nav-link">
                    <i class="ri-equalizer-fill"></i>
                    <span> Stock Adjustments </span>
                </a>
            </li>
            @endcan

            @can('view journals')
            <li class="side-nav-item">
                <a href="{{ route('inventory-transactions.index') }}" class="side-nav-link">
                    <i class="ri-history-fill"></i>
                    <span> Inventory Transactions </span>
                </a>
            </li>
            @endcan

            {{-- ── POS & Sales ────────────────────────────────────────── --}}
            @canany(['create sales', 'view sales'])
            <li class="side-nav-title mt-2">POS &amp; Sales</li>
            @endcanany

            @can('create sales')
            <li class="side-nav-item">
                <a href="{{ route('pos.index') }}" class="side-nav-link">
                    <i class="ri-shopping-basket-fill"></i>
                    <span> POS Terminal </span>
                </a>
            </li>
            @endcan

            @can('view sales')
            <li class="side-nav-item">
                <a href="{{ route('sales.index') }}" class="side-nav-link">
                    <i class="ri-file-list-3-fill"></i>
                    <span> Sales Invoices </span>
                </a>
            </li>
            @endcan

            {{-- ── Partners ───────────────────────────────────────────── --}}
            @canany(['view customers', 'view suppliers'])
            <li class="side-nav-title mt-2">Partners</li>
            @endcanany

            @can('view customers')
            <li class="side-nav-item">
                <a href="{{ route('customers.index') }}" class="side-nav-link">
                    <i class="ri-contacts-fill"></i>
                    <span> Customers </span>
                </a>
            </li>
            @endcan

            @can('view suppliers')
            <li class="side-nav-item">
                <a href="{{ route('suppliers.index') }}" class="side-nav-link">
                    <i class="ri-user-received-fill"></i>
                    <span> Suppliers </span>
                </a>
            </li>
            @endcan

            {{-- ── Financials & Expenses ──────────────────────────────── --}}
            @canany(['view chart of accounts', 'view journals', 'view cashbook', 'view settlements', 'settle customer dues', 'settle supplier payables', 'view expenses'])
            <li class="side-nav-title mt-2">Financials &amp; Expenses</li>
            @endcanany

            @can('view chart of accounts')
            <li class="side-nav-item">
                <a href="{{ route('coa.index') }}" class="side-nav-link">
                    <i class="ri-bank-card-fill"></i>
                    <span> Chart of Accounts </span>
                </a>
            </li>
            @endcan

            <li class="side-nav-item">
                <a href="{{ route('investments.index') }}" class="side-nav-link">
                    <i class="ri-funds-box-fill"></i>
                    <span> Investments </span>
                </a>
            </li>

            @can('view journals')
            <li class="side-nav-item">
                <a href="{{ route('journals.index') }}" class="side-nav-link">
                    <i class="ri-book-read-fill"></i>
                    <span> General Journal </span>
                </a>
            </li>
            @endcan

            @can('view cashbook')
            <li class="side-nav-item">
                <a href="{{ route('reports.cashbook') }}" class="side-nav-link">
                    <i class="ri-wallet-3-fill"></i>
                    <span> Cashbook (T-Account) </span>
                </a>
            </li>
            @endcan

            @canany(['view settlements', 'settle customer dues', 'settle supplier payables'])
            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarDues" aria-expanded="false" aria-controls="sidebarDues" class="side-nav-link">
                    <i class="ri-money-dollar-box-fill"></i>
                    <span> Due Management </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarDues">
                    <ul class="side-nav-second-level">
                        @canany(['view settlements', 'settle customer dues'])
                        <li>
                            <a href="{{ route('customer-dues.index') }}">Customer Dues</a>
                        </li>
                        @endcanany
                        @canany(['view settlements', 'settle supplier payables'])
                        <li>
                            <a href="{{ route('supplier-payables.index') }}">Supplier Payables</a>
                        </li>
                        @endcanany
                    </ul>
                </div>
            </li>
            @endcanany

            @can('view expenses')
            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarExpenses" aria-expanded="false" aria-controls="sidebarExpenses" class="side-nav-link">
                    <i class="ri-money-cny-box-fill"></i>
                    <span> Expenses </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarExpenses">
                    <ul class="side-nav-second-level">
                        @can('manage chart of accounts')
                        <li>
                            <a href="{{ route('expense-categories.index') }}">Categories</a>
                        </li>
                        @endcan
                        <li>
                            <a href="{{ route('expenses.index') }}">All Expenses</a>
                        </li>
                    </ul>
                </div>
            </li>
            @endcan

            {{-- ── Reports & Analytics ────────────────────────────────── --}}
            @canany(['view stock reports', 'view sales reports', 'view production reports', 'view financial reports'])
            <li class="side-nav-title mt-2">Reports &amp; Analytics</li>
            <li class="side-nav-item">
                <a href="{{ route('reports.index') }}" class="side-nav-link">
                    <i class="ri-dashboard-2-line"></i>
                    <span> Reports Dashboard </span>
                </a>
            </li>
            @endcanany

            {{-- ── System Control ─────────────────────────────────────── --}}
            @canany(['view activity logs', 'manage users', 'manage roles', 'view salaries'])
            <li class="side-nav-title">System Control</li>
            @endcanany

            @can('view salaries')
            <li class="side-nav-item">
                <a href="{{ route('salary.index') }}" class="side-nav-link">
                    <i class="ri-money-dollar-circle-fill"></i>
                    <span> Employee Salaries </span>
                </a>
            </li>
            @endcan

            @can('view activity logs')
            <li class="side-nav-item">
                <a href="{{ route('activity-logs.index') }}" class="side-nav-link">
                    <i class="ri-shield-user-fill"></i>
                    <span> Audit Logs </span>
                </a>
            </li>
            @endcan

            @can('manage users')
            <li class="side-nav-item">
                <a href="{{ route('users.index') }}" class="side-nav-link">
                    <i class="ri-group-fill"></i>
                    <span> Users </span>
                </a>
            </li>
            @endcan

            @can('manage roles')
            <li class="side-nav-item">
                <a href="{{ route('roles.index') }}" class="side-nav-link">
                    <i class="ri-shield-keyhole-fill"></i>
                    <span> Roles &amp; Permissions </span>
                </a>
            </li>
            @endcan

        </ul>
        <!--- End Sidemenu -->

        <div class="clearfix"></div>
    </div>
</div>
<!-- ========== Left Sidebar End ========== -->
