@extends('layouts.vertical', ['page_title' => 'Stock Integrity & Verification Hub', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('css')
<style>
    .metric-card {
        border-radius: 10px;
        transition: transform 0.2s ease-in-out;
    }
    .metric-card:hover {
        transform: translateY(-2px);
    }
    .layer-box {
        background-color: #f8f9fa;
        border-radius: 6px;
        padding: 4px 8px;
        font-size: 11px;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                <div>
                    <h4 class="page-title m-0 d-flex align-items-center gap-2">
                        <i class="ri-shield-check-fill text-success fs-3"></i>
                        Stock Integrity & Verification Hub
                    </h4>
                    <p class="text-muted fs-13 mb-0 mt-1">
                        Real-time cross-verification comparing <strong>Inventory Ledger</strong>, <strong>Batches</strong>, <strong>Warehouse Stocks</strong>, and <strong>Variant Totals</strong>
                    </p>
                </div>
                <div class="d-flex align-items-center gap-2 mt-2 mt-md-0">
                    <a href="{{ route('stock-verification.print') }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                        <i class="ri-printer-line me-1"></i> Print Audit
                    </a>
                    <a href="{{ route('stock-reconciliation.index') }}" class="btn btn-sm btn-outline-primary">
                        <i class="ri-scales-3-line me-1"></i> Reconciliation View
                    </a>
                    <form action="{{ route('stock-reconciliation.reconcile') }}" method="POST" onsubmit="return confirm('Recalculate and synchronize all stock tallies directly from the transaction ledger?');">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="ri-refresh-line me-1"></i> Run Ledger Sync
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <i class="ri-check-line me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Metric KPI Cards -->
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-3 mb-3">
        <!-- System Integrity Score -->
        <div class="col">
            <div class="card metric-card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted fs-12 fw-semibold text-uppercase">Integrity Score</span>
                            <h3 class="my-1 fw-bold {{ $systemIntegrityScore == 100 ? 'text-success' : 'text-danger' }}">
                                {{ $systemIntegrityScore }}%
                            </h3>
                            <small class="text-muted fs-11">
                                <i class="ri-checkbox-circle-fill text-success me-1"></i> {{ $perfectVariantsCount }}/{{ $totalVariantsCount }} Variants Matched
                            </small>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title rounded-3 fs-3 {{ $systemIntegrityScore == 100 ? 'bg-soft-success text-success' : 'bg-soft-danger text-danger' }}">
                                <i class="ri-shield-check-line"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Finished Units -->
        <div class="col">
            <div class="card metric-card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted fs-12 fw-semibold text-uppercase">Total Finished Stock</span>
                            <h3 class="my-1 fw-bold text-primary">{{ number_format($totalFinishedUnits, 0) }}</h3>
                            <small class="text-muted fs-11">Commercial Packaged Units</small>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-soft-primary text-primary rounded-3 fs-3">
                                <i class="ri-archive-line"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Raw Materials -->
        <div class="col">
            <div class="card metric-card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted fs-12 fw-semibold text-uppercase">Raw Materials Stock</span>
                            <h3 class="my-1 fw-bold text-info">{{ number_format($totalRawStock, 1) }}</h3>
                            <small class="text-muted fs-11">
                                <i class="ri-checkbox-circle-fill text-success me-1"></i> {{ $perfectRawsCount }}/{{ count($rawRows) }} Verified
                            </small>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-soft-info text-info rounded-3 fs-3">
                                <i class="ri-inbox-archive-line"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Discrepancies -->
        <div class="col">
            <div class="card metric-card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted fs-12 fw-semibold text-uppercase">Active Discrepancies</span>
                            <h3 class="my-1 fw-bold {{ count($discrepancies) > 0 ? 'text-danger' : 'text-success' }}">
                                {{ count($discrepancies) }}
                            </h3>
                            <small class="{{ count($discrepancies) > 0 ? 'text-danger' : 'text-success' }} fs-11">
                                @if(count($discrepancies) > 0)
                                    <i class="ri-error-warning-line me-1"></i> Requires Sync
                                @else
                                    <i class="ri-check-double-line me-1"></i> Zero Mismatches
                                @endif
                            </small>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title rounded-3 fs-3 {{ count($discrepancies) > 0 ? 'bg-soft-danger text-danger' : 'bg-soft-success text-success' }}">
                                <i class="{{ count($discrepancies) > 0 ? 'ri-alert-line' : 'ri-check-line' }}"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Navigation Tabs -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center justify-content-between">
                <div class="col-md-5">
                    <ul class="nav nav-pills" id="verifyTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active py-1 px-3 fs-13" id="variants-tab" data-bs-toggle="pill" data-bs-target="#variantsTabPane" type="button" role="tab">
                                <i class="ri-stack-line me-1"></i> Commercial Variants ({{ count($variantRows) }})
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link py-1 px-3 fs-13" id="raw-tab" data-bs-toggle="pill" data-bs-target="#rawTabPane" type="button" role="tab">
                                <i class="ri-inbox-archive-line me-1"></i> Raw Materials ({{ count($rawRows) }})
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link py-1 px-3 fs-13" id="guide-tab" data-bs-toggle="pill" data-bs-target="#guideTabPane" type="button" role="tab">
                                <i class="ri-information-line me-1"></i> Verification Guide
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="col-md-7">
                    <form action="{{ route('stock-verification.index') }}" method="GET" class="row g-2 justify-content-md-end">
                        <div class="col-auto">
                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">All Statuses</option>
                                <option value="perfect" {{ request('status') == 'perfect' ? 'selected' : '' }}>100% Perfect Matched</option>
                                <option value="mismatch" {{ request('status') == 'mismatch' ? 'selected' : '' }}>Discrepancies Only</option>
                                <option value="in_stock" {{ request('status') == 'in_stock' ? 'selected' : '' }}>In Stock Only (> 0)</option>
                                <option value="out_of_stock" {{ request('status') == 'out_of_stock' ? 'selected' : '' }}>Out of Stock (= 0)</option>
                            </select>
                        </div>
                        <div class="col-auto">
                            <div class="input-group input-group-sm" style="max-width: 250px;">
                                <input type="text" name="search" class="form-control" placeholder="Search product or SKU..." value="{{ request('search') }}">
                                <button class="btn btn-primary" type="submit"><i class="ri-search-line"></i></button>
                                @if(request('search') || request('status'))
                                    <a href="{{ route('stock-verification.index') }}" class="btn btn-light" title="Clear Filters"><i class="ri-close-line"></i></a>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Contents -->
    <div class="tab-content" id="verifyTabContent">
        <!-- TAB 1: Finished Product Variants -->
        <div class="tab-pane fade show active" id="variantsTabPane" role="tabpanel">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-2 border-bottom">
                    <h5 class="header-title fs-14 m-0">
                        <i class="ri-checkbox-circle-fill text-success me-1"></i> 4-Layer Stock Verification Matrix (Commercial Variants)
                    </h5>
                    <span class="badge bg-light text-dark border">Comparing: Ledger Net vs Batches vs Warehouse Cache vs Global Total</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-centered table-nowrap mb-0 fs-13">
                            <thead class="table-light">
                                <tr>
                                    <th>Product Variant</th>
                                    <th>SKU</th>
                                    @foreach($warehouses as $wh)
                                        <th class="text-center" style="min-width: 170px;">
                                            <i class="ri-building-2-line me-1"></i> {{ $wh->name }} Warehouse
                                            <div class="fs-10 text-muted fw-normal">Ledger &bull; Batch &bull; Cache</div>
                                        </th>
                                    @endforeach
                                    <th class="text-center" style="min-width: 120px;">
                                        Total WH Sum
                                        <div class="fs-10 text-muted fw-normal">&Sigma; All Warehouses</div>
                                    </th>
                                    <th class="text-center" style="min-width: 120px;">
                                        Global Counter
                                        <div class="fs-10 text-muted fw-normal">current_stock</div>
                                    </th>
                                    <th class="text-center" style="width: 140px;">Integrity Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($variantRows as $row)
                                    @php
                                        $sumWs = 0;
                                        foreach($row['warehouses'] as $whData) {
                                            $sumWs += $whData['ws'];
                                        }
                                    @endphp
                                    <tr>
                                        <td>
                                            <strong class="text-dark d-block">{{ $row['product_name'] }}</strong>
                                            <span class="badge bg-light text-dark border fs-11">{{ $row['variant_name'] }}</span>
                                        </td>
                                        <td>
                                            <span class="font-monospace fs-11 text-muted">{{ $row['sku'] }}</span>
                                        </td>
                                        @foreach($warehouses as $wh)
                                            @php
                                                $whData = $row['warehouses'][$wh->id] ?? null;
                                            @endphp
                                            <td class="text-center">
                                                @if($whData)
                                                    <div class="layer-box d-inline-flex align-items-center gap-2 border {{ $whData['match'] ? 'border-success-subtle bg-success-subtle' : 'border-danger-subtle bg-danger-subtle' }}">
                                                        <span title="Ledger Net">L: <strong>{{ (float)$whData['ledger'] }}</strong></span>
                                                        <span class="text-muted">&bull;</span>
                                                        <span title="Active Batches">B: <strong>{{ (float)$whData['batch'] }}</strong></span>
                                                        <span class="text-muted">&bull;</span>
                                                        <span title="Warehouse Stock Cache">C: <strong>{{ (float)$whData['ws'] }}</strong></span>
                                                        @if($whData['match'])
                                                            <i class="ri-check-line text-success fw-bold" title="Warehouse layers match"></i>
                                                        @else
                                                            <i class="ri-alert-line text-danger fw-bold" title="Mismatch in warehouse layers"></i>
                                                        @endif
                                                    </div>
                                                @else
                                                    <span class="text-muted fs-11">0</span>
                                                @endif
                                            </td>
                                        @endforeach
                                        <td class="text-center fw-bold text-primary fs-13">
                                            {{ (float)$sumWs }} {{ $row['unit'] }}
                                        </td>
                                        <td class="text-center fw-bold text-dark fs-13">
                                            {{ (float)$row['current_stock'] }} {{ $row['unit'] }}
                                        </td>
                                        <td class="text-center">
                                            @if($row['is_perfect'])
                                                <span class="badge bg-soft-success text-success px-2 py-1 fs-11">
                                                    <i class="ri-checkbox-circle-fill me-1"></i> 100% Perfect
                                                </span>
                                            @else
                                                <span class="badge bg-soft-danger text-danger px-2 py-1 fs-11" title="{{ implode(' | ', $row['discrepancies']) }}">
                                                    <i class="ri-alert-fill me-1"></i> Discrepancy
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ 5 + count($warehouses) }}" class="text-center py-4 text-muted">
                                            <i class="ri-inbox-line font-24 d-block mb-1"></i>
                                            No variants found matching your search.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: Bulk Raw Materials -->
        <div class="tab-pane fade" id="rawTabPane" role="tabpanel">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-2 border-bottom">
                    <h5 class="header-title fs-14 m-0">
                        <i class="ri-inbox-archive-line text-info me-1"></i> Raw Materials Ledger & Warehouse Verification
                    </h5>
                    <span class="badge bg-light text-dark border">Bulk Production Stock</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-centered table-nowrap mb-0 fs-13">
                            <thead class="table-light">
                                <tr>
                                    <th>Raw Material Name</th>
                                    <th>SKU</th>
                                    <th>Base Unit</th>
                                    @foreach($warehouses as $wh)
                                        <th class="text-center" style="min-width: 170px;">
                                            {{ $wh->name }} Warehouse
                                            <div class="fs-10 text-muted fw-normal">Ledger &bull; Batch &bull; Cache</div>
                                        </th>
                                    @endforeach
                                    <th class="text-center">Total Stock</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rawRows as $raw)
                                    <tr>
                                        <td><strong>{{ $raw['name'] }}</strong></td>
                                        <td><span class="font-monospace fs-11 text-muted">{{ $raw['sku'] }}</span></td>
                                        <td><span class="badge bg-light text-muted">{{ $raw['unit'] }}</span></td>
                                        @foreach($warehouses as $wh)
                                            @php
                                                $whData = $raw['warehouses'][$wh->id] ?? null;
                                            @endphp
                                            <td class="text-center">
                                                @if($whData)
                                                    <div class="layer-box d-inline-flex align-items-center gap-2 border {{ $whData['match'] ? 'border-success-subtle bg-success-subtle' : 'border-danger-subtle bg-danger-subtle' }}">
                                                        <span title="Ledger Net">L: <strong>{{ (float)$whData['ledger'] }}</strong></span>
                                                        <span class="text-muted">&bull;</span>
                                                        <span title="Active Batches">B: <strong>{{ (float)$whData['batch'] }}</strong></span>
                                                        <span class="text-muted">&bull;</span>
                                                        <span title="Warehouse Stock Cache">C: <strong>{{ (float)$whData['ws'] }}</strong></span>
                                                        @if($whData['match'])
                                                            <i class="ri-check-line text-success fw-bold"></i>
                                                        @else
                                                            <i class="ri-alert-line text-danger fw-bold"></i>
                                                        @endif
                                                    </div>
                                                @else
                                                    <span class="text-muted fs-11">0</span>
                                                @endif
                                            </td>
                                        @endforeach
                                        <td class="text-center fw-bold text-info fs-13">
                                            {{ (float)$raw['total_stock'] }} {{ $raw['unit'] }}
                                        </td>
                                        <td class="text-center">
                                            @if($raw['is_perfect'])
                                                <span class="badge bg-soft-success text-success px-2 py-1 fs-11">
                                                    <i class="ri-checkbox-circle-fill me-1"></i> 100% Perfect
                                                </span>
                                            @else
                                                <span class="badge bg-soft-danger text-danger px-2 py-1 fs-11" title="{{ implode(' | ', $raw['discrepancies']) }}">
                                                    <i class="ri-alert-fill me-1"></i> Mismatch
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ 5 + count($warehouses) }}" class="text-center py-4 text-muted">
                                            No raw materials defined.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 3: Verification Guide & Transparency Documentation -->
        <div class="tab-pane fade" id="guideTabPane" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h5 class="header-title mb-3"><i class="ri-book-open-line text-primary me-1"></i> How This System Ensures 100% Stock Accuracy</h5>
                            <p class="fs-13 text-muted">
                                This ERP uses an **accounting-grade multi-layer architecture** to guarantee that stock quantities displayed anywhere in the application are mathematically accurate and never drift:
                            </p>
                            
                            <div class="timeline-steps mt-3">
                                <div class="d-flex mb-3">
                                    <div class="avatar-xs flex-shrink-0 me-3">
                                        <span class="avatar-title bg-primary rounded-circle fs-12">1</span>
                                    </div>
                                    <div>
                                        <h6 class="mb-1 text-dark">Layer 1: Immutable Transaction Ledger (<code>inventory_transactions</code>)</h6>
                                        <p class="fs-12 text-muted mb-0">Every purchase, sale fulfillment, packaging order, transfer, or adjustment creates an immutable audit record. Net stock is always <code>SUM(qty_in) - SUM(qty_out)</code>.</p>
                                    </div>
                                </div>

                                <div class="d-flex mb-3">
                                    <div class="avatar-xs flex-shrink-0 me-3">
                                        <span class="avatar-title bg-info rounded-circle fs-12">2</span>
                                    </div>
                                    <div>
                                        <h6 class="mb-1 text-dark">Layer 2: Physical Lot & Batch Tracking (<code>batches</code>)</h6>
                                        <p class="fs-12 text-muted mb-0">Every product batch holds its cost per unit and remaining quantity. Outgoing sales consume batches using FIFO (First-In, First-Out).</p>
                                    </div>
                                </div>

                                <div class="d-flex mb-3">
                                    <div class="avatar-xs flex-shrink-0 me-3">
                                        <span class="avatar-title bg-warning text-dark rounded-circle fs-12">3</span>
                                    </div>
                                    <div>
                                        <h6 class="mb-1 text-dark">Layer 3: Warehouse Stock Cache (<code>warehouse_stocks</code>)</h6>
                                        <p class="fs-12 text-muted mb-0">Maintains the exact instantaneous balance per warehouse for instantaneous POS and terminal querying without lag.</p>
                                    </div>
                                </div>

                                <div class="d-flex">
                                    <div class="avatar-xs flex-shrink-0 me-3">
                                        <span class="avatar-title bg-success rounded-circle fs-12">4</span>
                                    </div>
                                    <div>
                                        <h6 class="mb-1 text-dark">Layer 4: Global Company Total (<code>product_variants.current_stock</code>)</h6>
                                        <p class="fs-12 text-muted mb-0">Always equals the sum of all warehouse stocks combined. Used on the customer portal and company-wide variant catalog.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm bg-soft-primary border-primary">
                        <div class="card-body">
                            <h5 class="header-title text-primary mb-2"><i class="ri-shield-check-line me-1"></i> Quick Consistency Check</h5>
                            <p class="fs-12 text-muted mb-3">
                                If you ever want to reassure yourself that all stock tallies are synchronized:
                            </p>
                            <ul class="ps-3 fs-12 text-muted mb-3">
                                <li>Check the <strong>Integrity Score</strong> on this hub (it should be 100%).</li>
                                <li>Verify that <strong>Active Discrepancies</strong> is 0.</li>
                                <li>Click <strong>"Run Ledger Sync"</strong> at any time to re-validate the entire database against ledger history.</li>
                            </ul>
                            <a href="{{ route('stock-reconciliation.index') }}" class="btn btn-sm btn-primary w-100">
                                <i class="ri-scales-3-line me-1"></i> Open Reconciliation Tools
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

