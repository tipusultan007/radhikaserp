@extends('layouts.report', ['title' => 'Stock by Warehouse'])

@section('report_content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box">
            <h4 class="page-title">Stock by Warehouse</h4>
        </div>
    </div>
</div>

<div class="row">
    @foreach($stockData as $data)
    <div class="col-md-12 mb-4">
        <div class="card border-primary">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0 text-white"><i class="ri-store-2-fill"></i> {{ $data['warehouse']->name }}</h4>
                <span class="badge bg-light text-primary fs-5">Total Value: ৳ {{ number_format($data['total_value'], 0) }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Type</th>
                                <th>Item</th>
                                <th>Qty</th>
                                <th>Weight</th>
                                <th>Cost (৳)</th>
                                <th>Value (৳)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['raw']->groupBy(fn($b) => $b->product->name) as $productName => $batches)
                                <tr class="table-dark">
                                    <td colspan="6" class="fw-bold text-primary">{{ $productName }} (Raw Material)</td>
                                </tr>
                                @foreach($batches as $batch)
                                <tr>
                                    <td><span class="badge bg-secondary">Raw Material</span></td>
                                    <td class="ps-4">
                                        <span class="text-muted fs-10">Batch: {{ $batch->batch_no }}</span>
                                    </td>
                                    <td>{{ number_format($batch->remaining_qty, 2) }} {{ $batch->product->base_unit }}</td>
                                    <td>{{ number_format($batch->remaining_qty, 2) }} {{ $batch->product->base_unit }}</td>
                                    <td>{{ number_format($batch->cost_per_unit, 0) }}</td>
                                    <td>{{ number_format($batch->remaining_qty * $batch->cost_per_unit, 0) }}</td>
                                </tr>
                                @endforeach
                            @endforeach
                            
                            @foreach($data['finished']->groupBy(fn($b) => $b->product->name) as $productName => $batches)
                                <tr class>
                                    <td colspan="6" class="fw-bold text-primary">{{ $productName }} (Finished/Packaged)</td>
                                </tr>
                                @foreach($batches as $batch)
                                <tr>
                                    <td>
                                        @if($batch->productVariant)
                                            <span class="badge bg-info">Packaged Variant</span>
                                        @else
                                            <span class="badge bg-success">Finished Product</span>
                                        @endif
                                    </td>
                                    <td class="ps-4">
                                        @if($batch->productVariant)
                                            - {{ $batch->productVariant->name }}
                                        @endif
                                        <br><span class="text-muted fs-10">Batch: {{ $batch->batch_no }}</span>
                                    </td>
                                    
                                    <td>
                                        {{ number_format($batch->remaining_qty, 2) }} 
                                        {{ $batch->productVariant ? $batch->productVariant->unit_type : $batch->product->base_unit }}
                                    </td>
                                    <td>
                                        @if($batch->productVariant)
                                            {{ number_format($batch->remaining_qty * $batch->productVariant->unit_qty, 2) }} {{ $batch->productVariant->product->base_unit }}
                                        @else
                                            {{ number_format($batch->remaining_qty, 2) }} {{ $batch->product->base_unit }}
                                        @endif
                                    </td>
                                    <td>{{ number_format($batch->cost_per_unit, 0) }}</td>
                                    <td>{{ number_format($batch->remaining_qty * $batch->cost_per_unit, 0) }}</td>
                                </tr>
                                @endforeach
                            @endforeach
                            
                            @if(count($data['raw']) == 0 && count($data['finished']) == 0)
                            <tr>
                                <td colspan="6" class="text-center text-muted">No stock available in this warehouse.</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection

