@extends('layouts.report', ['title' => 'Stock Summary'])

@section('report_content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex justify-content-between align-items-center">
            <h4 class="page-title">Stock Summary</h4>
            <a href="{{ route('reports.inventory.summary.print') }}" target="_blank" class="btn btn-primary">
                <i class="ri-printer-line me-1"></i> Print Report
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="header-title">Raw Materials Stock</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm datatable">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Warehouse</th>
                                <th>Qty</th>
                                <th>Cost (৳)</th>
                                <th>Value (৳)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rawBatches->groupBy(fn($b) => $b->product?->name ?? 'Unknown Product') as $productName => $batches)
                                <tr class="table-light">
                                    <td colspan="5" class="fw-bold text-primary">{{ $productName }}</td>
                                </tr>
                                @foreach($batches as $batch)
                                <tr>
                                    <td class="ps-4"><span class="text-muted fs-10">Batch: {{ $batch->batch_no }}</span></td>
                                    <td>{{ $batch->warehouse?->name ?? 'N/A' }}</td>
                                    <td>{{ number_format($batch->remaining_qty, 2) }} {{ $batch->product?->base_unit ?? '' }}</td>
                                    <td>{{ number_format($batch->cost_per_unit, 0) }}</td>
                                    <td>{{ number_format($batch->remaining_qty * $batch->cost_per_unit, 0) }}</td>
                                </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="2" class="text-end">Total</th>
                                <th>{{ number_format($rawBatches->sum('remaining_qty'), 2) }}</th>
                                <th></th>
                                <th>{{ number_format($rawBatches->sum(function($batch) { return $batch->remaining_qty * $batch->cost_per_unit; }), 0) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="header-title">Standalone Finished Products Stock</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm datatable">
                        <thead>
                            <tr>    
                                <th>Product</th>
                                <th>Warehouse</th>
                                <th>Qty</th>
                                <th>Cost (৳)</th>
                                <th>Value (৳)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($standaloneBatches->groupBy(fn($b) => $b->product?->name ?? 'Unknown Product') as $productName => $batches)
                                <tr class="table-light">
                                    <td colspan="5" class="fw-bold text-primary">{{ $productName }}</td>
                                </tr>
                                @foreach($batches as $batch)
                                <tr>
                                    <td class="ps-4"><span class="text-muted fs-10">Batch: {{ $batch->batch_no }}</span></td>
                                    <td>{{ $batch->warehouse?->name ?? 'N/A' }}</td>
                                    <td>{{ number_format($batch->remaining_qty, 3) }} {{ $batch->product?->base_unit ?? '' }}</td>
                                    <td>{{ number_format($batch->cost_per_unit, 0) }}</td>
                                    <td>{{ number_format($batch->remaining_qty * $batch->cost_per_unit, 0) }}</td>
                                </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="2" class="text-end">Total</th>
                                <th>{{ number_format($standaloneBatches->sum('remaining_qty'), 3) }}</th>
                                <th></th>
                                <th>{{ number_format($standaloneBatches->sum(function($batch) { return $batch->remaining_qty * $batch->cost_per_unit; }), 0) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="header-title">Packaged Variants Stock</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm datatable">
                        <thead>
                            <tr>
                                <th>Product - Variant</th>
                                <th>Warehouse</th>
                                <th>Qty</th>
                                <th>Weight</th>
                                <th>Cost (৳)</th>
                                <th>Value (৳)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($packagedBatches->groupBy(fn($b) => ($b->productVariant?->product?->name ?? 'Product') . ' — ' . ($b->productVariant?->name ?? 'Variant')) as $variantTitle => $vBatches)
                                <tr class="table-secondary">
                                    <td colspan="2" class="fw-bold text-dark"><i class="ri-stack-line me-1"></i> {{ $variantTitle }}</td>
                                    <td class="fw-bold text-primary">{{ number_format($vBatches->sum('remaining_qty'), 2) }} {{ $vBatches->first()->productVariant?->unit_type ?? '' }}</td>
                                    <td class="fw-bold">{{ number_format($vBatches->sum(fn($b) => $b->remaining_qty * ($b->productVariant?->unit_qty ?? 1)), 2) }} {{ $vBatches->first()->productVariant?->product?->base_unit ?? '' }}</td>
                                    <td colspan="2"></td>
                                </tr>
                                @foreach($vBatches as $batch)
                                <tr>
                                    <td class="ps-4 text-muted"><span class="fs-11 font-monospace">Batch: {{ $batch->batch_no }}</span></td>
                                    <td>{{ $batch->warehouse?->name ?? 'N/A' }}</td>
                                    <td>{{ number_format($batch->remaining_qty, 2) }} {{ $batch->productVariant?->unit_type ?? '' }}</td>
                                    <td>{{ number_format($batch->remaining_qty * ($batch->productVariant?->unit_qty ?? 1), 2) }} {{ $batch->productVariant?->product?->base_unit ?? '' }}</td>
                                    <td>{{ number_format($batch->cost_per_unit, 0) }}</td>
                                    <td>{{ number_format($batch->remaining_qty * $batch->cost_per_unit, 0) }}</td>
                                </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="2" class="text-end">Total</th>
                                <th>{{ number_format($packagedBatches->sum('remaining_qty'), 2) }}</th>
                                <th>{{ number_format($packagedBatches->sum(function($batch) { return $batch->remaining_qty * ($batch->productVariant?->unit_qty ?? 1); }), 2) }}</th>
                                <th></th>
                                <th> {{ number_format($packagedBatches->sum(function($batch) { return $batch->remaining_qty * $batch->cost_per_unit; }), 0) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.datatable').DataTable({
        ordering: false,
        pageLength: 50
    });
});
</script>
@endpush

