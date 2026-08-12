@extends('layouts.vertical', ['title' => 'Physical Stock Take Audit'])

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex justify-content-between align-items-center">
            <div>
                <h4 class="page-title mb-0">Physical Stock Take Audit</h4>
                <p class="text-muted fs-13 mb-0">Enter actual counted stock. The system will automatically generate reconciling adjustments for any variances.</p>
            </div>
            <a href="{{ route('stock-reconciliation.index') }}" class="btn btn-secondary">
                <i class="ri-arrow-left-line me-1"></i> Back to Reconciliation
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <!-- Warehouse Selector -->
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body">
                <form action="{{ route('stock-reconciliation.physical-audit') }}" method="GET" class="row align-items-center">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Select Warehouse for Stock Take:</label>
                        <select name="warehouse_id" class="form-select" onchange="this.form.submit()">
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}" {{ $selectedWarehouseId == $wh->id ? 'selected' : '' }}>
                                    {{ $wh->name }} ({{ $wh->location ?? 'Warehouse' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-8 text-md-end mt-3 mt-md-0">
                        <span class="badge bg-soft-info text-info p-2 fs-12">
                            <i class="ri-information-line me-1"></i> Count input entries automatically calculate variances against system records.
                        </span>
                    </div>
                </form>
            </div>
        </div>

        <form action="{{ route('stock-reconciliation.store-physical-audit') }}" method="POST" id="auditForm">
            @csrf
            <input type="hidden" name="warehouse_id" value="{{ $selectedWarehouseId }}">

            <!-- Notes Card -->
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body">
                    <div class="form-group mb-0">
                        <label for="notes" class="form-label fw-bold">Stock Take Audit Notes / Reason:</label>
                        <input type="text" name="notes" id="notes" class="form-control" placeholder="e.g. Monthly Physical Count Audit - Q3 2026" required>
                    </div>
                </div>
            </div>

            <!-- Batches & Items Stock Count Table -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="ri-clipboard-line me-2"></i> Item & Batch Count Form</h5>
                    <button type="submit" class="btn btn-success fw-bold">
                        <i class="ri-save-line me-1"></i> Submit & Reconcile Physical Count
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Item Type</th>
                                    <th>Item Name</th>
                                    <th>Batch Ref</th>
                                    <th class="text-end">System Recorded Qty</th>
                                    <th class="text-center" style="width: 200px;">Physical Count Qty</th>
                                    <th class="text-end">Calculated Variance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $itemIndex = 0; @endphp

                                <!-- Raw Materials Section -->
                                <tr class="table-secondary">
                                    <td colspan="6" class="fw-bold text-uppercase fs-12 text-dark"><i class="ri-inbox-archive-line me-1"></i> Raw Materials</td>
                                </tr>
                                @forelse($rawProducts as $raw)
                                    @php
                                        $sysQty = $stockMap['raw_' . $raw->id] ?? 0;
                                    @endphp
                                    <tr>
                                        <td><span class="badge bg-soft-primary text-primary">Raw Material</span></td>
                                        <td class="fw-bold">{{ $raw->name }}</td>
                                        <td><span class="text-muted">General Stock</span></td>
                                        <td class="text-end fw-semibold">{{ number_format($sysQty, 3) }} {{ $raw->unit?->name ?? $raw->base_unit }}</td>
                                        <td class="text-center">
                                            <input type="hidden" name="items[{{ $itemIndex }}][product_id]" value="{{ $raw->id }}">
                                            <input type="number" step="0.001" min="0" 
                                                   name="items[{{ $itemIndex }}][physical_qty]" 
                                                   value="{{ number_format($sysQty, 3, '.', '') }}" 
                                                   class="form-control form-control-sm text-end physical-input" 
                                                   data-system="{{ $sysQty }}" 
                                                   data-variance-id="var-raw-{{ $raw->id }}">
                                        </td>
                                        <td class="text-end fw-bold" id="var-raw-{{ $raw->id }}">0.000</td>
                                    </tr>
                                    @php $itemIndex++; @endphp
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-2 fs-12">No raw products found.</td>
                                    </tr>
                                @endforelse

                                <!-- Product Variants Section -->
                                <tr class="table-secondary">
                                    <td colspan="6" class="fw-bold text-uppercase fs-12 text-dark"><i class="ri-stack-line me-1"></i> Packaged Product Variants</td>
                                </tr>
                                @forelse($variants as $variant)
                                    @php
                                        $sysQty = $stockMap['variant_' . $variant->id] ?? 0;
                                    @endphp
                                    <tr>
                                        <td><span class="badge bg-soft-info text-info">Product Variant</span></td>
                                        <td>
                                            <span class="fw-bold">{{ $variant->name }}</span>
                                            <br><small class="text-muted">{{ $variant->product?->name }}</small>
                                        </td>
                                        <td><span class="text-muted">Variant Aggregate</span></td>
                                        <td class="text-end fw-semibold">{{ number_format($sysQty, 2) }} {{ $variant->unit_type }}</td>
                                        <td class="text-center">
                                            <input type="hidden" name="items[{{ $itemIndex }}][product_id]" value="{{ $variant->product_id }}">
                                            <input type="hidden" name="items[{{ $itemIndex }}][product_variant_id]" value="{{ $variant->id }}">
                                            <input type="number" step="0.01" min="0" 
                                                   name="items[{{ $itemIndex }}][physical_qty]" 
                                                   value="{{ number_format($sysQty, 2, '.', '') }}" 
                                                   class="form-control form-control-sm text-end physical-input" 
                                                   data-system="{{ $sysQty }}" 
                                                   data-variance-id="var-var-{{ $variant->id }}">
                                        </td>
                                        <td class="text-end fw-bold" id="var-var-{{ $variant->id }}">0.00</td>
                                    </tr>
                                    @php $itemIndex++; @endphp
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-2 fs-12">No product variants found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-light text-end">
                    <button type="submit" class="btn btn-success btn-lg fw-bold">
                        <i class="ri-check-line me-1"></i> Submit & Apply Stock Take Adjustments
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.physical-input').on('input', function() {
        var inputVal = parseFloat($(this).val()) || 0;
        var sysVal = parseFloat($(this).data('system')) || 0;
        var diff = inputVal - sysVal;
        var targetId = $(this).data('variance-id');
        
        var formattedDiff = (diff >= 0 ? '+' : '') + diff.toFixed(2);
        var $el = $('#' + targetId);
        
        $el.text(formattedDiff);
        if (diff > 0) {
            $el.removeClass('text-danger text-muted').addClass('text-success');
        } else if (diff < 0) {
            $el.removeClass('text-success text-muted').addClass('text-danger');
        } else {
            $el.removeClass('text-success text-danger').addClass('text-muted');
        }
    });
});
</script>
@endpush
