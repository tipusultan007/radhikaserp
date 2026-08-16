@extends('layouts.vertical', ['page_title' => 'Dashboard', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('css')
    @vite(['node_modules/daterangepicker/daterangepicker.css'])
@endsection

@section('content')
    <!-- Start Content-->
    <div class="container-fluid">

        <!-- Page Header & Quick Actions -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box justify-content-between d-flex align-items-lg-center flex-lg-row flex-column py-3">     
                    <div class="mb-lg-0 mb-2">
                        <h4 class="page-title mb-0">Dashboard</h4>
                        <p class="text-muted mb-0 fs-13">Welcome to your Radhikas ERP overview</p>
                    </div>

                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        @can('create sales')
                        <a href="{{ route('pos.index') }}" class="btn btn-success btn-sm rounded-pill shadow-sm">
                            <i class="ri-add-line me-1"></i> New Sale (POS)
                        </a>
                        @endcan

                        @can('create purchases')
                        <a href="{{ route('purchases.create') }}" class="btn btn-info btn-sm rounded-pill shadow-sm">
                            <i class="ri-shopping-bag-3-line me-1"></i> New Purchase
                        </a>
                        @endcan

                        @can('create expenses')
                        <a href="{{ route('expenses.create') }}" class="btn btn-outline-danger btn-sm rounded-pill shadow-sm">
                            <i class="ri-money-dollar-circle-line me-1"></i> Add Expense
                        </a>
                        @endcan

                        @can('create products')
                        <a href="{{ route('products.create') }}" class="btn btn-outline-primary btn-sm rounded-pill shadow-sm">
                            <i class="ri-box-3-line me-1"></i> Add Product
                        </a>
                        @endcan

                        <form class="d-flex ms-lg-2">
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control shadow border-0" id="dash-daterange">
                                <span class="input-group-text bg-primary border-primary text-white">
                                    <i class="ri-calendar-todo-fill fs-13"></i>
                                </span>
                            </div>
                            <a href="javascript: void(0);" class="btn btn-sm btn-primary ms-1">
                                <i class="ri-refresh-line"></i>
                            </a>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Row 1: Core Financial KPI Cards -->
        <div class="row row-cols-1 row-cols-xxl-4 row-cols-lg-2 row-cols-md-2">
            <!-- Total Sales Card -->
            <div class="col mb-3">
                <div class="card widget-icon-box h-100 mb-0 shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="flex-grow-1 overflow-hidden">
                                <h5 class="text-muted text-uppercase fs-12 fw-semibold mt-0" title="Total Sales">Total Sales</h5>
                                <h3 class="my-2 fw-bold text-dark">{{ number_format($totalSales ?? 0, 0) }}</h3>
                                <p class="mb-0 text-muted text-truncate fs-12">
                                    <span class="badge bg-success me-1"><i class="ri-arrow-up-line"></i></span>
                                    <span>All-time revenue</span>  
                                </p>
                            </div>
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title text-bg-success rounded-3 fs-3 widget-icon-box-avatar shadow">
                                    <i class="ri-shopping-cart-2-line"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Today's Sales Card -->
            <div class="col mb-3">
                <div class="card widget-icon-box h-100 mb-0 shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="flex-grow-1 overflow-hidden">
                                <h5 class="text-muted text-uppercase fs-12 fw-semibold mt-0" title="Today's Sales">Today's Sales</h5>
                                <h3 class="my-2 fw-bold text-dark">{{ number_format($todaySales ?? 0, 0) }}</h3>
                                <p class="mb-0 text-muted text-truncate fs-12">
                                    <span class="badge bg-info me-1"><i class="ri-time-line"></i></span>
                                    <span>Generated today</span>
                                </p>
                            </div>
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title text-bg-info rounded-3 fs-3 widget-icon-box-avatar shadow">
                                    <i class="ri-funds-line"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Expenses Card -->
            <div class="col mb-3">
                <div class="card widget-icon-box h-100 mb-0 shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="flex-grow-1 overflow-hidden">
                                <h5 class="text-muted text-uppercase fs-12 fw-semibold mt-0" title="Total Expenses">Total Expenses</h5>
                                <h3 class="my-2 fw-bold text-dark">{{ number_format($totalExpenses ?? 0, 0) }}</h3>
                                <p class="mb-0 text-muted text-truncate fs-12">
                                    <span class="badge bg-danger me-1"><i class="ri-arrow-down-line"></i></span>
                                    <span>All-time expenses</span>
                                </p>
                            </div>
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title text-bg-danger rounded-3 fs-3 widget-icon-box-avatar shadow">
                                    <i class="ri-money-dollar-circle-line"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cash Balance Card -->
            <div class="col mb-3">
                <div class="card widget-icon-box h-100 mb-0 shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="flex-grow-1 overflow-hidden">
                                <h5 class="text-muted text-uppercase fs-12 fw-semibold mt-0" title="Cash Balance">Cash Balance</h5>
                                <h3 class="my-2 fw-bold text-dark">{{ number_format($cashBalance ?? 0, 0) }}</h3>
                                <p class="mb-0 text-muted text-truncate fs-12">
                                    <span class="badge bg-primary me-1"><i class="ri-wallet-3-line"></i></span>
                                    <span>Current Cash Flow</span>
                                </p>
                            </div>
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title text-bg-primary rounded-3 fs-3 widget-icon-box-avatar shadow">
                                    <i class="ri-wallet-3-line"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Row 2: Operational & Inventory KPI Cards -->
        <div class="row row-cols-1 row-cols-xxl-4 row-cols-lg-2 row-cols-md-2">
            <!-- Total Purchases Card -->
            <div class="col mb-3">
                <div class="card widget-icon-box h-100 mb-0 shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="flex-grow-1 overflow-hidden">
                                <h5 class="text-muted text-uppercase fs-12 fw-semibold mt-0" title="Total Purchases">Total Purchases</h5>
                                <h3 class="my-2 fw-bold text-dark">{{ number_format($totalPurchases ?? 0, 0) }}</h3>
                                <p class="mb-0 text-muted text-truncate fs-12">
                                    <span class="badge bg-secondary me-1"><i class="ri-truck-line"></i></span>
                                    <span>Inventory cost</span>
                                </p>
                            </div>
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title text-bg-secondary rounded-3 fs-3 widget-icon-box-avatar shadow">
                                    <i class="ri-shopping-bag-3-line"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stock Valuation Card -->
            <div class="col mb-3">
                <div class="card widget-icon-box h-100 mb-0 shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="flex-grow-1 overflow-hidden">
                                <h5 class="text-muted text-uppercase fs-12 fw-semibold mt-0" title="Stock Valuation">Stock Valuation</h5>
                                <h3 class="my-2 fw-bold text-dark">{{ number_format($inventoryValue ?? 0, 0) }}</h3>
                                <p class="mb-0 text-muted text-truncate fs-12">
                                    <span class="badge bg-purple me-1" style="background-color: #6f42c1;"><i class="ri-archive-line"></i></span>
                                    <span>Batch inventory value</span>
                                </p>
                            </div>
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title rounded-3 fs-3 widget-icon-box-avatar shadow text-white" style="background-color: #6f42c1;">
                                    <i class="ri-stack-line"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Accounts Receivable (Due) Card -->
            <div class="col mb-3">
                <div class="card widget-icon-box h-100 mb-0 shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="flex-grow-1 overflow-hidden">
                                <h5 class="text-muted text-uppercase fs-12 fw-semibold mt-0" title="Customer Due">Receivables (Due)</h5>
                                <h3 class="my-2 fw-bold text-dark">{{ number_format($totalDue ?? 0, 0) }}</h3>
                                <p class="mb-0 text-muted text-truncate fs-12">
                                    <span class="badge bg-warning text-dark me-1"><i class="ri-hand-coin-line"></i></span>
                                    <span>Unpaid customer sales</span>
                                </p>
                            </div>
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title text-bg-warning rounded-3 fs-3 widget-icon-box-avatar shadow">
                                    <i class="ri-hand-coin-line"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Low Stock Alerts Card -->
            <div class="col mb-3">
                <div class="card widget-icon-box h-100 mb-0 shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="flex-grow-1 overflow-hidden">
                                <h5 class="text-muted text-uppercase fs-12 fw-semibold mt-0" title="Low Stock Alerts">Low Stock Alerts</h5>
                                <h3 class="my-2 fw-bold text-dark">{{ $lowStockAlerts ?? 0 }}</h3>
                                <p class="mb-0 text-muted text-truncate fs-12">
                                    <span class="badge bg-danger me-1"><i class="ri-alert-line"></i></span>
                                    <span>Batches &le; 10 remaining</span>
                                </p>
                            </div>
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title text-bg-danger rounded-3 fs-3 widget-icon-box-avatar shadow">
                                    <i class="ri-error-warning-line"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Row 3: Main Chart & Analytics Sidebar -->
        <div class="row">
            <!-- Revenue vs Expenses Chart (Last 7 Days) -->
            <div class="col-xl-8 col-lg-7 mb-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="d-flex card-header justify-content-between align-items-center bg-transparent border-bottom">
                        <h4 class="header-title mb-0 fs-15"><i class="ri-line-chart-line me-1 text-primary"></i> Revenue vs Expenses (Last 7 Days)</h4>
                        <span class="badge bg-light text-dark border fs-12">Daily Trend</span>
                    </div>
                    <div class="card-body">
                        <div dir="ltr">
                            <canvas id="revenueExpensesChart" height="280"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Analytics Sidebar Widgets -->
            <div class="col-xl-4 col-lg-5 mb-3">
                <!-- Monthly Growth Performance Card -->
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center">
                        <h4 class="header-title mb-0 fs-15"><i class="ri-bar-chart-box-line me-1 text-success"></i> Monthly Sales Performance</h4>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <p class="text-muted mb-1 fs-12">This Month Revenue</p>
                                <h4 class="fw-bold text-success mb-0">{{ number_format($thisMonthSales ?? 0, 0) }}</h4>
                            </div>
                            <div class="text-end">
                                <p class="text-muted mb-1 fs-12">Last Month Revenue</p>
                                <h5 class="fw-semibold text-dark mb-0">{{ number_format($lastMonthSales ?? 0, 0) }}</h5>
                            </div>
                        </div>

                        <div class="p-2 rounded bg-light border d-flex align-items-center justify-content-between">
                            <span class="fs-12 text-muted fw-medium">Monthly Growth Rate</span>
                            @if(($monthlyGrowthPercent ?? 0) >= 0)
                                <span class="badge bg-success fs-12 px-2 py-1"><i class="ri-arrow-up-line me-1"></i>+{{ $monthlyGrowthPercent }}%</span>
                            @else
                                <span class="badge bg-danger fs-12 px-2 py-1"><i class="ri-arrow-down-line me-1"></i>{{ $monthlyGrowthPercent }}%</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Expense Categories Breakdown Widget -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center">
                        <h4 class="header-title mb-0 fs-15"><i class="ri-pie-chart-line me-1 text-danger"></i> Expense Breakdown</h4>
                        <a href="{{ route('expenses.index') }}" class="text-primary fs-12">View All</a>
                    </div>
                    <div class="card-body p-3">
                        @php $totalExpCat = $expenseCategories->sum('total_amount'); @endphp
                        @forelse($expenseCategories as $catItem)
                            @php 
                                $percent = $totalExpCat > 0 ? round(($catItem->total_amount / $totalExpCat) * 100, 1) : 0; 
                            @endphp
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fs-13 font-weight-medium text-dark">{{ $catItem->category->name ?? 'Uncategorized' }}</span>
                                    <span class="fs-12 text-muted fw-bold">{{ number_format($catItem->total_amount, 0) }} ({{ $percent }}%)</span>
                                </div>
                                <div class="progress progress-sm">
                                    <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $percent }}%" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted text-center py-3 mb-0 fs-13">No categorized expense records found.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Row 4: Detailed Activity Tables -->
        <div class="row">
            <!-- Recent Sales Table -->
            <div class="col-xl-4 col-lg-6 mb-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="d-flex card-header justify-content-between align-items-center bg-transparent border-bottom">
                        <h4 class="header-title mb-0 fs-15"><i class="ri-shopping-cart-2-line me-1 text-info"></i> Recent Sales</h4>
                        <a href="{{ route('sales.index') }}" class="btn btn-xs btn-outline-info">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-borderless table-hover table-nowrap table-centered m-0">
                                <thead class="border-top border-bottom bg-light-subtle">
                                    <tr class="text-muted fs-12">
                                        <th class="py-2">Invoice</th>
                                        <th class="py-2">Customer</th>
                                        <th class="py-2 text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentSales as $sale)
                                    <tr>
                                        <td><span class="fw-semibold text-dark">{{ $sale->invoice_no }}</span><br><small class="text-muted fs-11">{{ $sale->date ? $sale->date->format('M d') : '' }}</small></td>
                                        <td>{{ Str::limit($sale->customer->name ?? 'Walk-in', 14) }}</td>
                                        <td class="text-end text-success fw-bold">{{ number_format($sale->total, 0) }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-3 text-muted fs-13">No recent sales records.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Purchases Table -->
            <div class="col-xl-4 col-lg-6 mb-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="d-flex card-header justify-content-between align-items-center bg-transparent border-bottom">
                        <h4 class="header-title mb-0 fs-15"><i class="ri-shopping-bag-3-line me-1 text-success"></i> Recent Purchases</h4>
                        <a href="{{ route('purchases.index') }}" class="btn btn-xs btn-outline-success">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-borderless table-hover table-nowrap table-centered m-0">
                                <thead class="border-top border-bottom bg-light-subtle">
                                    <tr class="text-muted fs-12">
                                        <th class="py-2">Ref No</th>
                                        <th class="py-2">Supplier</th>
                                        <th class="py-2 text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentPurchases as $purchase)
                                    <tr>
                                        <td><span class="fw-semibold text-dark">{{ $purchase->purchase_no }}</span><br><small class="text-muted fs-11">{{ $purchase->date ? $purchase->date->format('M d') : '' }}</small></td>
                                        <td>{{ Str::limit($purchase->supplier->name ?? 'N/A', 14) }}</td>
                                        <td class="text-end text-danger fw-bold">{{ number_format($purchase->total_cost, 0) }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-3 text-muted fs-13">No recent purchases.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Selling Items Widget -->
            <div class="col-xl-4 col-lg-12 mb-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="d-flex card-header justify-content-between align-items-center bg-transparent border-bottom">
                        <h4 class="header-title mb-0 fs-15"><i class="ri-trophy-line me-1 text-warning"></i> Top Selling Items</h4>
                        <span class="badge bg-warning-subtle text-warning border border-warning fs-11">By Volume</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-borderless table-hover table-nowrap table-centered m-0">
                                <thead class="border-top border-bottom bg-light-subtle">
                                    <tr class="text-muted fs-12">
                                        <th class="py-2">Item</th>
                                        <th class="py-2 text-center">Qty Sold</th>
                                        <th class="py-2 text-end">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($topSellingItems as $item)
                                    @php
                                        $prodName = $item->productVariant->product->name ?? ($item->batch->product->name ?? 'Unknown Item');
                                        $varName = $item->productVariant->name ?? '';
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="fw-semibold text-dark">{{ Str::limit($prodName, 16) }}</span>
                                            @if($varName)<br><small class="text-muted fs-11">{{ $varName }}</small>@endif
                                        </td>
                                        <td class="text-center"><span class="badge bg-primary-subtle text-primary border border-primary fs-12 px-2">{{ number_format($item->total_qty, 0) }}</span></td>
                                        <td class="text-end fw-bold text-dark">{{ number_format($item->total_revenue, 0) }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-3 text-muted fs-13">No sales items logged yet.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Row 5: Low Stock Alert Table -->
        <div class="row">
            <div class="col-xl-12 mb-3">
                <div class="card shadow-sm border-0">
                    <div class="d-flex card-header justify-content-between align-items-center bg-transparent border-bottom">
                        <h4 class="header-title text-danger mb-0 fs-15"><i class="ri-error-warning-line me-1"></i> Low Stock Alerts</h4>
                        <a href="{{ route('reports.inventory.summary') }}" class="btn btn-xs btn-outline-danger">View Full Inventory Report</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-centered table-hover table-borderless mb-0">
                                <thead class="border-top border-bottom bg-light-subtle">
                                    <tr class="text-muted fs-12">
                                        <th class="ps-3 py-2">Batch No</th>
                                        <th class="py-2">Product</th>
                                        <th class="py-2">Warehouse</th>
                                        <th class="py-2 text-end pe-3">Remaining Qty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($lowStockBatches as $batch)
                                    <tr>
                                        <td class="ps-3 fw-semibold text-dark">{{ $batch->batch_no }}</td>
                                        <td>{{ $batch->product->name ?? 'N/A' }} {{ $batch->productVariant ? ' - ' . $batch->productVariant->name : '' }}</td>
                                        <td>{{ $batch->warehouse->name ?? 'N/A' }}</td>
                                        <td class="text-end pe-3">
                                            <span class="badge bg-danger fs-12 px-2 py-1">{{ number_format($batch->remaining_qty, 0) }}</span>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-3 text-muted fs-13">All stock levels are currently healthy!</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <!-- container -->
@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var ctx = document.getElementById('revenueExpensesChart').getContext('2d');
            var chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: {!! $dates->reverse()->values()->toJson() !!},
                    datasets: [{
                        label: 'Revenue',
                        data: {!! $salesData->reverse()->values()->toJson() !!},
                        borderColor: '#17a497',
                        backgroundColor: 'rgba(23, 164, 151, 0.1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }, {
                        label: 'Expenses',
                        data: {!! $expensesData->reverse()->values()->toJson() !!},
                        borderColor: '#fa5c7c',
                        backgroundColor: 'rgba(250, 92, 124, 0.1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
    </script>
@endsection
