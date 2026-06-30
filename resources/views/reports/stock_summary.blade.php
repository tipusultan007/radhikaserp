@extends('layouts.vertical', ['page_title' => 'Stock Summary', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Stock Summary</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title mb-3">Raw Materials</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Warehouse</th>
                                    <th>Remaining Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rawBatches as $batch)
                                <tr>
                                    <td>{{ $batch->product->name }}</td>
                                    <td>{{ $batch->warehouse->name }}</td>
                                    <td>{{ $batch->remaining_qty }} {{ $batch->product->base_unit }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title mb-3">Packaged Goods (Variants)</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Variant</th>
                                    <th>Warehouse</th>
                                    <th>Remaining Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($packagedBatches as $batch)
                                <tr>
                                    <td>{{ $batch->productVariant->product->name }} - {{ $batch->productVariant->name }}</td>
                                    <td>{{ $batch->warehouse->name }}</td>
                                    <td>{{ $batch->remaining_qty }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
