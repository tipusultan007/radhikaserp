@extends('layouts.vertical', ['page_title' => 'Dashboard', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('css')
    @vite(['node_modules/daterangepicker/daterangepicker.css'])
@endsection

@section('content')
    <!-- Start Content-->
    <div class="container-fluid">

        <div class="row">
            <div class="col-12">
                <div class="page-title-box justify-content-between d-flex align-items-lg-center flex-lg-row flex-column">     
                    <h4 class="page-title">Dashboard</h4>
                    <form class="d-flex mb-xxl-0 mb-2">
                        <div class="input-group">
                            <input type="text" class="form-control shadow border-0" id="dash-daterange">
                            <span class="input-group-text bg-primary border-primary text-white">
                                <i class="ri-calendar-todo-fill fs-13"></i>
                            </span>
                        </div>
                        <a href="javascript: void(0);" class="btn btn-primary ms-2">
                            <i class="ri-refresh-line"></i>
                        </a>
                    </form>
                </div>
            </div>
        </div>

        <div class="row row-cols-1 row-cols-xxl-4 row-cols-lg-2 row-cols-md-2">
            <div class="col">
                <div class="card widget-icon-box">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="flex-grow-1 overflow-hidden">
                                <h5 class="text-muted text-uppercase fs-13 mt-0" title="Total Sales">Total Sales</h5>
                                <h3 class="my-3">${{ number_format($totalSales ?? 0, 0) }}</h3>
                                <p class="mb-0 text-muted text-truncate">
                                    <span class="badge bg-success me-1"><i class="ri-arrow-up-line"></i></span>
                                    <span>All-time revenue</span>  
                                </p>
                            </div>
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title text-bg-success rounded rounded-3 fs-3 widget-icon-box-avatar shadow">
                                    <i class="ri-shopping-cart-2-line"></i>
                                </span>
                            </div>
                        </div>
                    </div> <!-- end card-body-->
                </div> <!-- end card-->
            </div> <!-- end col-->

            <div class="col">
                <div class="card widget-icon-box">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="flex-grow-1 overflow-hidden">
                                <h5 class="text-muted text-uppercase fs-13 mt-0" title="Total Expenses">Total Expenses</h5>
                                <h3 class="my-3">${{ number_format($totalExpenses ?? 0, 0) }}</h3>
                                <p class="mb-0 text-muted text-truncate">
                                    <span class="badge bg-danger me-1"><i class="ri-arrow-down-line"></i></span>
                                    <span>All-time expenses</span>
                                </p>
                            </div>
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title text-bg-danger rounded rounded-3 fs-3 widget-icon-box-avatar shadow">
                                    <i class="ri-money-dollar-circle-line"></i>
                                </span>
                            </div>
                        </div>
                    </div> <!-- end card-body-->
                </div> <!-- end card-->
            </div> <!-- end col-->

            <div class="col">
                <div class="card widget-icon-box">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="flex-grow-1 overflow-hidden">
                                <h5 class="text-muted text-uppercase fs-13 mt-0" title="Cash Balance">Cash Balance</h5>
                                <h3 class="my-3">${{ number_format($cashBalance ?? 0, 0) }}</h3>
                                <p class="mb-0 text-muted text-truncate">
                                    <span class="badge bg-primary me-1"><i class="ri-wallet-3-line"></i></span>
                                    <span>Current Cash Flow</span>
                                </p>
                            </div>
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title text-bg-primary rounded rounded-3 fs-3 widget-icon-box-avatar shadow">
                                    <i class="ri-wallet-3-line"></i>
                                </span>
                            </div>
                        </div>
                    </div> <!-- end card-body-->
                </div> <!-- end card-->
            </div> <!-- end col-->

            <div class="col">
                <div class="card widget-icon-box">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="flex-grow-1 overflow-hidden">
                                <h5 class="text-muted text-uppercase fs-13 mt-0" title="Low Stock Alerts">Low Stock Alerts</h5>
                                <h3 class="my-3">{{ $lowStockAlerts ?? 0 }}</h3>
                                <p class="mb-0 text-muted text-truncate">
                                    <span class="badge bg-warning text-dark me-1"><i class="ri-alert-line"></i></span>
                                    <span>Batches <= 10 remaining</span>
                                </p>
                            </div>
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title text-bg-warning rounded rounded-3 fs-3 widget-icon-box-avatar shadow">
                                    <i class="ri-error-warning-line"></i>
                                </span>
                            </div>
                        </div>
                    </div> <!-- end card-body-->
                </div> <!-- end card-->
            </div> <!-- end col-->
        </div> <!-- end row -->

        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="d-flex card-header justify-content-between align-items-center">
                        <h4 class="header-title">Revenue vs Expenses (Last 7 Days)</h4>
                    </div>
                    <div class="card-body pt-0">
                        <div dir="ltr">
                            <canvas id="revenueExpensesChart" height="80"></canvas>
                        </div>
                    </div> <!-- end card-body-->
                </div> <!-- end card-->
            </div> <!-- end col-->
        </div>
        <!-- end row -->

        <div class="row">
            <div class="col-xl-6">
                <div class="card">
                    <div class="d-flex card-header justify-content-between align-items-center">
                        <h4 class="header-title">Recent Sales</h4>
                        <a href="{{ route('sales.index') }}" class="btn btn-sm btn-info">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-borderless table-hover table-nowrap table-centered m-0">
                                <thead class="border-top border-bottom bg-light-subtle border-light">
                                    <tr>
                                        <th class="py-1">Invoice</th>
                                        <th class="py-1">Date</th>
                                        <th class="py-1">Customer</th>
                                        <th class="py-1">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentSales as $sale)
                                    <tr>
                                        <td>{{ $sale->invoice_no }}</td>
                                        <td>{{ $sale->date->format('M d, Y') }}</td>
                                        <td>{{ $sale->customer->name ?? 'Walk-in' }}</td>
                                        <td class="text-success fw-bold">${{ number_format($sale->total, 0) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div> <!-- end col -->
            
            <div class="col-xl-6">
                <div class="card">
                    <div class="d-flex card-header justify-content-between align-items-center">
                        <h4 class="header-title">Recent Imports</h4>
                        <a href="{{ route('imports.index') }}" class="btn btn-sm btn-success">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-borderless table-hover table-nowrap table-centered m-0">
                                <thead class="border-top border-bottom bg-light-subtle border-light">
                                    <tr>
                                        <th class="py-1">Ref No</th>
                                        <th class="py-1">Date</th>
                                        <th class="py-1">Supplier</th>
                                        <th class="py-1">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentImports as $import)
                                    <tr>
                                        @can('view imports')
                                        <td>{{ $import->ref_no }}</td>
                                        <td>{{ $import->date->format('M d, Y') }}</td>
                                        <td>{{ $import->supplier->name ?? 'N/A' }}</td>
                                        <td class="text-danger fw-bold">${{ number_format($import->grand_total, 0) }}</td>
                                        @endcan
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div> <!-- end col -->
        </div>
        <!-- end row -->

        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="d-flex card-header justify-content-between align-items-center">
                        <h4 class="header-title text-warning"><i class="ri-alert-line"></i> Low Stock Alerts</h4>
                        <a href="{{ route('reports.inventory.summary') }}" class="btn btn-sm btn-warning">View Inventory Report</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-centered table-hover table-borderless mb-0">
                                <thead class="border-top border-bottom bg-light-subtle border-light">
                                    <tr>
                                        <th>Batch No</th>
                                        <th>Product</th>
                                        <th>Warehouse</th>
                                        <th>Remaining Qty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($lowStockBatches as $batch)
                                    <tr>
                                        <td>{{ $batch->batch_no }}</td>
                                        <td>{{ $batch->product->name }} {{ $batch->productVariant ? ' - ' . $batch->productVariant->name : '' }}</td>
                                        <td>{{ $batch->warehouse->name ?? 'N/A' }}</td>
                                        <td>
                                            @can('view repackaging')
                                            <span class="badge bg-danger fs-13">{{ $batch->remaining_qty }}</span>
                                            @endcan
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-3 text-muted">All stock levels are healthy!</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div> <!-- end table-responsive-->
                    </div> <!-- end card-body-->
                </div> <!-- end card-->
            </div> <!-- end col-->
        </div>
        <!-- end row -->

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
                        label: 'Revenue ($)',
                        data: {!! $salesData->reverse()->values()->toJson() !!},
                        borderColor: '#17a497',
                        backgroundColor: 'rgba(23, 164, 151, 0.1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }, {
                        label: 'Expenses ($)',
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

