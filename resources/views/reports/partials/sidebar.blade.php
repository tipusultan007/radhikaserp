<div class="card shadow-sm border-0 mb-3">
    <div class="card-body p-0">
        <!-- Inventory -->
        <div class="bg-primary text-white p-2 fw-bold d-flex align-items-center">
            <i class="ri-store-2-line me-2"></i> Inventory
        </div>
        <div class="list-group list-group-flush border-0">
            @can('view stock reports')
            <a href="{{ route('reports.inventory.summary') }}" class="list-group-item list-group-item-action {{ request()->routeIs('reports.inventory.summary') ? 'active' : '' }}"><i class="ri-bar-chart-box-line me-2"></i>Stock Summary</a>
            <a href="{{ route('reports.inventory.warehouse') }}" class="list-group-item list-group-item-action {{ request()->routeIs('reports.inventory.warehouse') ? 'active' : '' }}"><i class="ri-building-4-line me-2"></i>Stock by Warehouse</a>
            <a href="{{ route('reports.inventory.date') }}" class="list-group-item list-group-item-action {{ request()->routeIs('reports.inventory.date') ? 'active' : '' }}"><i class="ri-calendar-event-line me-2"></i>Stock by Date</a>
            <a href="{{ route('reports.inventory.batch') }}" class="list-group-item list-group-item-action {{ request()->routeIs('reports.inventory.batch') ? 'active' : '' }}"><i class="ri-box-3-line me-2"></i>Batch Movement</a>
            @endcan
        </div>

        <!-- Sales -->
        <div class="bg-success text-white p-2 fw-bold d-flex align-items-center mt-2">
            <i class="ri-shopping-cart-2-line me-2"></i> Sales
        </div>
        <div class="list-group list-group-flush border-0">
            @can('view sales reports')
            <a href="{{ route('reports.sales.daily') }}" class="list-group-item list-group-item-action {{ request()->routeIs('reports.sales.daily') ? 'active' : '' }}"><i class="ri-line-chart-line me-2"></i>Daily Sales</a>
            <a href="{{ route('reports.sales.monthly') }}" class="list-group-item list-group-item-action {{ request()->routeIs('reports.sales.monthly') ? 'active' : '' }}"><i class="ri-calendar-todo-line me-2"></i>Monthly Sales</a>
            <a href="{{ route('reports.sales.products') }}" class="list-group-item list-group-item-action {{ request()->routeIs('reports.sales.products') ? 'active' : '' }}"><i class="ri-shopping-bag-3-line me-2"></i>Product Velocity</a>
            <a href="{{ route('reports.sales.profit') }}" class="list-group-item list-group-item-action {{ request()->routeIs('reports.sales.profit') ? 'active' : '' }}"><i class="ri-money-dollar-circle-line me-2"></i>Profit & Margins</a>
            @endcan
        </div>

        <!-- Production -->
        <div class="bg-warning text-white p-2 fw-bold d-flex align-items-center mt-2">
            <i class="ri-settings-4-line me-2"></i> Production
        </div>
        <div class="list-group list-group-flush border-0">
            @can('view production reports')
            <a href="{{ route('reports.production.yield') }}" class="list-group-item list-group-item-action {{ request()->routeIs('reports.production.yield') ? 'active' : '' }}"><i class="ri-scales-3-line me-2"></i>Repackaging Yield</a>
            <a href="{{ route('reports.production.loss_gain') }}" class="list-group-item list-group-item-action {{ request()->routeIs('reports.production.loss_gain') ? 'active' : '' }}"><i class="ri-arrow-up-down-line me-2"></i>Loss & Gain Report</a>
            <a href="{{ route('reports.production.batch_cost') }}" class="list-group-item list-group-item-action {{ request()->routeIs('reports.production.batch_cost') ? 'active' : '' }}"><i class="ri-funds-line me-2"></i>Cost per Batch</a>
            @endcan
        </div>

        <!-- Accounting -->
        <div class="bg-info text-white p-2 fw-bold d-flex align-items-center mt-2">
            <i class="ri-bank-line me-2"></i> Accounting
        </div>
        <div class="list-group list-group-flush border-0">
            @can('view financial reports')
            <a href="{{ route('reports.cashbook') }}" class="list-group-item list-group-item-action {{ request()->routeIs('reports.cashbook') ? 'active' : '' }}"><i class="ri-book-read-line me-2"></i>Cashbook / Ledgers</a>
            <a href="{{ route('reports.pl') }}" class="list-group-item list-group-item-action {{ request()->routeIs('reports.pl') ? 'active' : '' }}"><i class="ri-pie-chart-2-line me-2"></i>Profit & Loss</a>
            <a href="{{ route('reports.bs') }}" class="list-group-item list-group-item-action {{ request()->routeIs('reports.bs') ? 'active' : '' }}"><i class="ri-scales-fill me-2"></i>Balance Sheet</a>
            @endcan
        </div>
    </div>
</div>
<style>
    .list-group-item.active { background-color: #f1f5f9; color: #000; border-color: #e2e8f0; border-left: 3px solid #3b82f6; font-weight: 600; }
    .list-group-item { padding: 0.5rem 1rem; font-size: 14px; }
</style>
