@extends('layouts.vertical', ['title' => 'Stock Reconciliation'])

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex justify-content-between align-items-center">
            <div>
                <h4 class="page-title mb-0">Product Stock Reconciliation</h4>
                <p class="text-muted fs-13 mb-0">Reconcile raw materials, variants, repackagings, and batch inventory directly from transaction ledgers</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('stock-verification.index') }}" class="btn btn-success">
                    <i class="ri-shield-check-fill me-1"></i> Stock Verification Hub
                </a>
                <a href="{{ route('stock-reconciliation.physical-audit') }}" class="btn btn-outline-primary">
                    <i class="ri-survey-line me-1"></i> Physical Stock Take
                </a>
                <form action="{{ route('stock-reconciliation.reconcile') }}" method="POST" onsubmit="return confirm('Are you sure you want to recalculate and resync all stock balances from the transaction ledger?');">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-refresh-line me-1"></i> Run Full Ledger Sync
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

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
    <i class="ri-error-warning-line me-1"></i> {{ $errors->first() }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<!-- Ledger Health & Discrepancy Status Card -->
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 d-flex align-items-center">
                    <i class="ri-shield-check-line text-success fs-4 me-2"></i> Ledger Audit & Consistency Check
                </h5>
                @if(count($discrepancies) > 0)
                    <span class="badge bg-warning text-dark fs-12 px-2 py-1"><i class="ri-alert-line me-1"></i> {{ count($discrepancies) }} Mismatch(es) Detected</span>
                @else
                    <span class="badge bg-success fs-12 px-2 py-1"><i class="ri-check-double-line me-1"></i> 100% Synchronized</span>
                @endif
            </div>
            <div class="card-body">
                @if(count($discrepancies) > 0)
                    <div class="alert alert-warning border-0 shadow-xs mb-3">
                        <strong>The system detected differences between cached tallies/batches and transaction ledgers:</strong>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Mismatch Type</th>
                                    <th>Item / Batch Reference</th>
                                    <th>Warehouse</th>
                                    <th>Current System Qty</th>
                                    <th>Ledger Calculated Qty</th>
                                    <th>Variance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($discrepancies as $disc)
                                <tr>
                                    <td><span class="badge bg-soft-warning text-warning fw-semibold">{{ $disc['type'] }}</span></td>
                                    <td class="fw-bold">{{ $disc['reference'] }}</td>
                                    <td>{{ $disc['warehouse'] }}</td>
                                    <td>{{ number_format($disc['system_val'], 3) }}</td>
                                    <td>{{ number_format($disc['ledger_val'], 3) }}</td>
                                    <td class="text-danger fw-bold">{{ number_format($disc['difference'], 3) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="text-muted fs-12 mb-0">Clicking <strong>"Run Full Ledger Sync"</strong> above will automatically recalculate every batch and warehouse balance to match the ledger truth.</p>
                @else
                    <div class="text-center py-3">
                        <i class="ri-checkbox-circle-fill text-success fs-1 mb-2"></i>
                        <h5 class="text-success mb-1">Stock Integrity Verified</h5>
                        <p class="text-muted fs-13 mb-0">All physical batches, raw material stocks, product variant tallies, and warehouse stocks are in complete sync with your transaction log.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Stock Breakdown Tables -->
<div class="row mt-3">
    <!-- Raw Materials Stock -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-primary text-white py-2">
                <h5 class="card-title text-white mb-0"><i class="ri-inbox-archive-line me-2"></i> Raw Materials Stock Balance</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Raw Material</th>
                                <th>Unit</th>
                                @foreach($warehouses as $wh)
                                    <th class="text-end">{{ $wh->name }}</th>
                                @endforeach
                                <th class="text-end fw-bold">Total Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rawProducts as $raw)
                                @php
                                    $rowTotal = 0;
                                @endphp
                                <tr>
                                    <td class="fw-bold text-dark">{{ $raw->name }}</td>
                                    <td><span class="badge bg-light text-muted">{{ $raw->unit?->name ?? $raw->base_unit }}</span></td>
                                    @foreach($warehouses as $wh)
                                        @php
                                            $qty = $whStockMap[$wh->id . '_raw_' . $raw->id] ?? 0;
                                            $rowTotal += $qty;
                                        @endphp
                                        <td class="text-end">{{ number_format($qty, 3) }}</td>
                                    @endforeach
                                    <td class="text-end fw-bold text-primary">{{ number_format($rowTotal, 3) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ 3 + count($warehouses) }}" class="text-center text-muted py-3">No raw materials defined.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Variants Stock -->
    <div class="col-lg-6 mt-3 mt-lg-0">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-info text-white py-2">
                <h5 class="card-title text-white mb-0"><i class="ri-stack-line me-2"></i> Packaged Product Variants Stock</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Variant / Product</th>
                                <th>SKU</th>
                                @foreach($warehouses as $wh)
                                    <th class="text-end">{{ $wh->name }}</th>
                                @endforeach
                                <th class="text-end fw-bold">Global Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($variants as $variant)
                                @php
                                    $rowTotal = 0;
                                @endphp
                                <tr>
                                    <td>
                                        <span class="fw-bold">{{ $variant->name }}</span>
                                        <br><small class="text-muted">{{ $variant->product?->name }}</small>
                                    </td>
                                    <td><span class="badge bg-light text-dark fs-11">{{ $variant->sku }}</span></td>
                                    @foreach($warehouses as $wh)
                                        @php
                                            $qty = $whStockMap[$wh->id . '_variant_' . $variant->id] ?? 0;
                                            $rowTotal += $qty;
                                        @endphp
                                        <td class="text-end">{{ number_format($qty, 2) }}</td>
                                    @endforeach
                                    <td class="text-end fw-bold text-info">{{ number_format($variant->current_stock, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ 3 + count($warehouses) }}" class="text-center text-muted py-3">No product variants found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Active Batches Table -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0"><i class="ri-archive-line me-2"></i> Batch Stock Balances & Status</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Batch No</th>
                                <th>Item Name</th>
                                <th>Warehouse</th>
                                <th class="text-end">Qty In</th>
                                <th class="text-end">Qty Out</th>
                                <th class="text-end fw-bold">Remaining Qty</th>
                                <th class="text-end">Cost / Unit (৳)</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($batches as $batch)
                                @php
                                    $itemName = $batch->productVariant ? ($batch->product?->name . ' - ' . $batch->productVariant->name) : ($batch->product?->name ?? 'N/A');
                                @endphp
                                <tr>
                                    <td><span class="font-monospace text-primary fw-bold">{{ $batch->batch_no }}</span></td>
                                    <td>{{ $itemName }}</td>
                                    <td>{{ $batch->warehouse?->name ?? 'N/A' }}</td>
                                    <td class="text-end">{{ number_format($batch->qty_in, 3) }}</td>
                                    <td class="text-end text-muted">{{ number_format($batch->qty_out, 3) }}</td>
                                    <td class="text-end fw-bold {{ $batch->remaining_qty > 0 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format($batch->remaining_qty, 3) }}
                                    </td>
                                    <td class="text-end">৳ {{ number_format($batch->cost_per_unit, 2) }}</td>
                                    <td>{{ $batch->created_at ? $batch->created_at->format('d M Y, h:i A') : 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-3">No batches recorded.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-end mt-2">
                    {{ $batches->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
