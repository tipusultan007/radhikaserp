@extends('layouts.vertical', ['title' => 'Stock Summary'])

@section('content')
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
                                <th>Batch No</th>
                                <th>Warehouse</th>
                                <th>Remaining Qty</th>
                                <th>Unit Cost</th>
                                <th>Total Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rawBatches as $batch)
                            <tr>
                                <td>{{ $batch->product->name }}</td>
                                <td>{{ $batch->batch_no }}</td>
                                <td>{{ $batch->warehouse->name ?? 'N/A' }}</td>
                                <td>{{ number_format($batch->remaining_qty, 3) }} {{ $batch->product->base_unit }}</td>
                                <td>${{ number_format($batch->cost_per_unit, 0) }}</td>
                                <td>${{ number_format($batch->remaining_qty * $batch->cost_per_unit, 0) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
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
                                <th>Batch No</th>
                                <th>Warehouse</th>
                                <th>Remaining Qty</th>
                                <th>Unit Cost</th>
                                <th>Total Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($standaloneBatches as $batch)
                            <tr>
                                <td>{{ $batch->product->name }}</td>
                                <td>{{ $batch->batch_no }}</td>
                                <td>{{ $batch->warehouse->name ?? 'N/A' }}</td>
                                <td>{{ number_format($batch->remaining_qty, 3) }} {{ $batch->product->base_unit }}</td>
                                <td>${{ number_format($batch->cost_per_unit, 0) }}</td>
                                <td>${{ number_format($batch->remaining_qty * $batch->cost_per_unit, 0) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
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
                                <th>Batch No</th>
                                <th>Warehouse</th>
                                <th>Unit Count</th>
                                <th>Total Weight</th>
                                <th>Unit Cost</th>
                                <th>Total Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($packagedBatches as $batch)
                            <tr>
                                <td>{{ $batch->productVariant->product->name }} - {{ $batch->productVariant->name }}</td>
                                <td>{{ $batch->batch_no }}</td>
                                <td>{{ $batch->warehouse->name ?? 'N/A' }}</td>
                                <td>{{ number_format($batch->remaining_qty, 2) }} {{ $batch->productVariant->unit_type }}</td>
                                <td>{{ number_format($batch->remaining_qty * $batch->productVariant->unit_qty, 2) }} {{ $batch->productVariant->product->base_unit }}</td>
                                <td>${{ number_format($batch->cost_per_unit, 0) }}</td>
                                <td>${{ number_format($batch->remaining_qty * $batch->cost_per_unit, 0) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
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
    $('.datatable').DataTable();
});
</script>
@endpush

