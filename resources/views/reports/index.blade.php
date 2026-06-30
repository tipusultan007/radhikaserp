@extends('layouts.vertical', ['title' => 'Reports Dashboard'])

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box">
            <h4 class="page-title">Reports & Analytics</h4>
        </div>
    </div>
</div>

<div class="row">
    <!-- Inventory Reports -->
    <div class="col-md-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-primary text-white border-0 py-3">
                <h4 class="card-title text-white mb-0 d-flex align-items-center">
                    <i class="ri-store-2-line fs-3 me-2"></i> Inventory & Stock
                </h4>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush border-0">
                    @can('view stock reports')
                        <a href="{{ route('reports.inventory.summary') }}" class="list-group-item list-group-item-action border-0 py-3 d-flex align-items-center">
                            <i class="ri-bar-chart-box-line text-primary fs-4 me-3"></i>
                            <div>
                                <h5 class="mb-0 fs-14">Stock Summary</h5>
                                <small class="text-muted">Overview of current inventory levels</small>
                            </div>
                        </a>
                        <a href="{{ route('reports.inventory.warehouse') }}" class="list-group-item list-group-item-action border-0 py-3 d-flex align-items-center border-top">
                            <i class="ri-building-4-line text-primary fs-4 me-3"></i>
                            <div>
                                <h5 class="mb-0 fs-14">Stock by Warehouse</h5>
                                <small class="text-muted">Breakdown of stock across locations</small>
                            </div>
                        </a>
                        <a href="{{ route('reports.inventory.date') }}" class="list-group-item list-group-item-action border-0 py-3 d-flex align-items-center border-top">
                            <i class="ri-calendar-event-line text-primary fs-4 me-3"></i>
                            <div>
                                <h5 class="mb-0 fs-14">Stock by Date</h5>
                                <small class="text-muted">Historical inventory valuation</small>
                            </div>
                        </a>
                        <a href="{{ route('reports.inventory.batch') }}" class="list-group-item list-group-item-action border-0 py-3 d-flex align-items-center border-top">
                            <i class="ri-box-3-line text-primary fs-4 me-3"></i>
                            <div>
                                <h5 class="mb-0 fs-14">Batch Movement</h5>
                                <small class="text-muted">Track specific batch lifecycle & expiry</small>
                            </div>
                        </a>
                    @else
                        <div class="p-4 text-center text-muted">
                            <i class="ri-lock-2-line fs-1 mb-2 d-block text-warning"></i>
                            <p class="mb-0">You do not have permission to view these reports.</p>
                        </div>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <!-- Sales Reports -->
    <div class="col-md-4 mt-3 mt-md-0">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-success text-white border-0 py-3">
                <h4 class="card-title text-white mb-0 d-flex align-items-center">
                    <i class="ri-shopping-cart-2-line fs-3 me-2"></i> Sales & Revenue
                </h4>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush border-0">
                    @can('view sales reports')
                        <a href="{{ route('reports.sales.daily') }}" class="list-group-item list-group-item-action border-0 py-3 d-flex align-items-center">
                            <i class="ri-line-chart-line text-success fs-4 me-3"></i>
                            <div>
                                <h5 class="mb-0 fs-14">Daily Sales</h5>
                                <small class="text-muted">Revenue breakdown by day</small>
                            </div>
                        </a>
                        <a href="{{ route('reports.sales.monthly') }}" class="list-group-item list-group-item-action border-0 py-3 d-flex align-items-center border-top">
                            <i class="ri-calendar-todo-line text-success fs-4 me-3"></i>
                            <div>
                                <h5 class="mb-0 fs-14">Monthly Sales</h5>
                                <small class="text-muted">Long-term revenue trends</small>
                            </div>
                        </a>
                        <a href="{{ route('reports.sales.products') }}" class="list-group-item list-group-item-action border-0 py-3 d-flex align-items-center border-top">
                            <i class="ri-shopping-bag-3-line text-success fs-4 me-3"></i>
                            <div>
                                <h5 class="mb-0 fs-14">Product Velocity</h5>
                                <small class="text-muted">Top performing products and variants</small>
                            </div>
                        </a>
                        <a href="{{ route('reports.sales.profit') }}" class="list-group-item list-group-item-action border-0 py-3 d-flex align-items-center border-top">
                            <i class="ri-money-dollar-circle-line text-success fs-4 me-3"></i>
                            <div>
                                <h5 class="mb-0 fs-14">Profit & Margins</h5>
                                <small class="text-muted">Real gross profit based on COGS</small>
                            </div>
                        </a>
                    @else
                        <div class="p-4 text-center text-muted">
                            <i class="ri-lock-2-line fs-1 mb-2 d-block text-warning"></i>
                            <p class="mb-0">You do not have permission to view these reports.</p>
                        </div>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <!-- Production Reports -->
    <div class="col-md-4 mt-3 mt-md-0">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-warning text-white border-0 py-3">
                <h4 class="card-title text-white mb-0 d-flex align-items-center">
                    <i class="ri-settings-4-line fs-3 me-2"></i> Production
                </h4>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush border-0">
                    @can('view production reports')
                        <a href="{{ route('reports.production.yield') }}" class="list-group-item list-group-item-action border-0 py-3 d-flex align-items-center">
                            <i class="ri-scales-3-line text-warning fs-4 me-3"></i>
                            <div>
                                <h5 class="mb-0 fs-14">Repackaging Yield</h5>
                                <small class="text-muted">Detailed conversion ratios</small>
                            </div>
                        </a>
                        <a href="{{ route('reports.production.loss_gain') }}" class="list-group-item list-group-item-action border-0 py-3 d-flex align-items-center border-top">
                            <i class="ri-arrow-up-down-line text-warning fs-4 me-3"></i>
                            <div>
                                <h5 class="mb-0 fs-14">Loss & Gain Report</h5>
                                <small class="text-muted">Wastage and overage tracking</small>
                            </div>
                        </a>
                        <a href="{{ route('reports.production.batch_cost') }}" class="list-group-item list-group-item-action border-0 py-3 d-flex align-items-center border-top">
                            <i class="ri-funds-line text-warning fs-4 me-3"></i>
                            <div>
                                <h5 class="mb-0 fs-14">Cost per Batch</h5>
                                <small class="text-muted">Internal production cost trends</small>
                            </div>
                        </a>
                    @else
                        <div class="p-4 text-center text-muted">
                            <i class="ri-lock-2-line fs-1 mb-2 d-block text-warning"></i>
                            <p class="mb-0">You do not have permission to view these reports.</p>
                        </div>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <!-- Financial Reports -->
    <div class="col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-info text-white border-0 py-3">
                <h4 class="card-title text-white mb-0 d-flex align-items-center">
                    <i class="ri-bank-line fs-3 me-2"></i> Accounting
                </h4>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush border-0">
                    @can('view financial reports')
                        <a href="{{ route('reports.cashbook') }}" class="list-group-item list-group-item-action border-0 py-3 d-flex align-items-center">
                            <i class="ri-book-read-line text-info fs-4 me-3"></i>
                            <div>
                                <h5 class="mb-0 fs-14">Cashbook / Ledgers</h5>
                                <small class="text-muted">Transaction records by account</small>
                            </div>
                        </a>
                        <a href="{{ route('reports.pl') }}" class="list-group-item list-group-item-action border-0 py-3 d-flex align-items-center border-top">
                            <i class="ri-pie-chart-2-line text-info fs-4 me-3"></i>
                            <div>
                                <h5 class="mb-0 fs-14">Profit & Loss</h5>
                                <small class="text-muted">Comprehensive income statement</small>
                            </div>
                        </a>
                        <a href="{{ route('reports.bs') }}" class="list-group-item list-group-item-action border-0 py-3 d-flex align-items-center border-top">
                            <i class="ri-scales-fill text-info fs-4 me-3"></i>
                            <div>
                                <h5 class="mb-0 fs-14">Balance Sheet</h5>
                                <small class="text-muted">Assets, Liabilities, and Equity</small>
                            </div>
                        </a>
                    @else
                        <div class="p-4 text-center text-muted">
                            <i class="ri-lock-2-line fs-1 mb-2 d-block text-warning"></i>
                            <p class="mb-0">You do not have permission to view these reports.</p>
                        </div>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
