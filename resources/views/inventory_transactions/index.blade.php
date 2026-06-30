@extends('layouts.vertical', ['page_title' => 'Inventory Transactions', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                <h4 class="page-title">Inventory Transactions</h4>
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                    <li class="breadcrumb-item active">Transactions</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card bg-light mb-3 shadow-none border">
                <div class="card-body py-2 px-3">
                    <form action="{{ route('inventory-transactions.index') }}" method="GET">
                        <div class="row gy-2 gx-2 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label mb-1">Start Date</label>
                                <input type="text" class="form-control flatpickr-date" name="start_date" value="{{ request('start_date') }}" placeholder="YYYY-MM-DD">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label mb-1">End Date</label>
                                <input type="text" class="form-control flatpickr-date" name="end_date" value="{{ request('end_date') }}" placeholder="YYYY-MM-DD">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label mb-1">Warehouse</label>
                                <select class="form-select" name="warehouse_id">
                                    <option value="">All Warehouses</option>
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label mb-1">Product</label>
                                <select class="form-select" name="product_id">
                                    <option value="">All Products</option>
                                    @foreach($products as $prod)
                                        <option value="{{ $prod->id }}" {{ request('product_id') == $prod->id ? 'selected' : '' }}>{{ $prod->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label mb-1">Type</label>
                                <select class="form-select" name="type">
                                    <option value="">All Types</option>
                                    @foreach($types as $t)
                                        <option value="{{ $t }}" {{ request('type') == $t ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $t)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line me-1"></i> Filter</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-centered table-striped dt-responsive nowrap w-100">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Warehouse</th>
                                    <th>Product / Variant</th>
                                    <th>Batch No</th>
                                    <th>Type</th>
                                    <th>Qty In</th>
                                    <th>Qty Out</th>
                                    <th>Ref</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($transactions as $txn)
                                <tr>
                                    <td>{{ $txn->date->format('Y-m-d') }}</td>
                                    <td>{{ $txn->warehouse->name ?? 'N/A' }}</td>
                                    <td>
                                        <b>{{ $txn->product->name ?? 'N/A' }}</b>
                                        <br>
                                        <small class="text-muted">{{ $txn->productVariant->name ?? '' }}</small>
                                    </td>
                                    <td>{{ $txn->batch->batch_no ?? 'N/A' }}</td>
                                    <td>
                                        @php
                                            $badgeClass = 'bg-secondary';
                                            if(in_array($txn->type, ['import', 'transfer_in', 'repack_output'])) $badgeClass = 'bg-success';
                                            elseif(in_array($txn->type, ['sale', 'transfer_out', 'repack_input'])) $badgeClass = 'bg-danger';
                                            elseif($txn->type == 'adjustment') $badgeClass = 'bg-warning text-dark';
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">{{ strtoupper(str_replace('_', ' ', $txn->type)) }}</span>
                                    </td>
                                    <td class="text-success">{{ $txn->qty_in > 0 ? number_format($txn->qty_in, 3) : '-' }}</td>
                                    <td class="text-danger">{{ $txn->qty_out > 0 ? number_format($txn->qty_out, 3) : '-' }}</td>
                                    <td>
                                        @if($txn->reference_type && $txn->reference)
                                            <span class="badge bg-light text-dark border">{{ class_basename($txn->reference_type) }} #{{ $txn->reference_id }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        {{ $transactions->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
