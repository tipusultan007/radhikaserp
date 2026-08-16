@extends('layouts.report', ['title' => 'Batch Movement'])

@section('report_content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex justify-content-between align-items-center mb-3">
            <h4 class="page-title mb-0"><i class="ri-history-line me-1 text-primary"></i> Batch Movement Report</h4>
            <a href="{{ route('reports.inventory.batch') }}" class="btn btn-sm btn-outline-secondary">
                <i class="ri-refresh-line me-1"></i> Reset All Filters
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-light-subtle d-flex justify-content-between align-items-center py-2">
                <h5 class="card-title mb-0 fs-14 text-dark"><i class="ri-filter-3-line me-1"></i> Filter Batch Movements</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('reports.inventory.batch') }}" method="GET">
                    <div class="row g-2">
                        <!-- Batch Number -->
                        <div class="col-md-3 col-sm-6">
                            <label class="form-label fs-12 fw-semibold mb-1" for="batch_no">Batch Number</label>
                            <input type="text" class="form-control form-control-sm" id="batch_no" name="batch_no" placeholder="Search Batch No..." value="{{ request('batch_no') }}">
                        </div>

                        <!-- Product Dropdown -->
                        <div class="col-md-3 col-sm-6">
                            <label class="form-label fs-12 fw-semibold mb-1" for="product_id">Product</label>
                            <select class="form-select form-select-sm" id="product_id" name="product_id">
                                <option value="">-- All Products --</option>
                                @foreach($products as $prod)
                                    <option value="{{ $prod->id }}" {{ request('product_id') == $prod->id ? 'selected' : '' }}>
                                        {{ $prod->name }} ({{ ucfirst($prod->type) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Warehouse Dropdown -->
                        <div class="col-md-3 col-sm-6">
                            <label class="form-label fs-12 fw-semibold mb-1" for="warehouse_id">Warehouse</label>
                            <select class="form-select form-select-sm" id="warehouse_id" name="warehouse_id">
                                <option value="">-- All Warehouses --</option>
                                @foreach($warehouses as $wh)
                                    <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>
                                        {{ $wh->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Stock Status Dropdown -->
                        <div class="col-md-3 col-sm-6">
                            <label class="form-label fs-12 fw-semibold mb-1" for="stock_status">Stock Status</label>
                            <select class="form-select form-select-sm" id="stock_status" name="stock_status">
                                <option value="">-- All Stock Status --</option>
                                <option value="in_stock" {{ request('stock_status') == 'in_stock' ? 'selected' : '' }}>In Stock (> 0)</option>
                                <option value="low_stock" {{ request('stock_status') == 'low_stock' ? 'selected' : '' }}>Low Stock (<= 10)</option>
                                <option value="out_of_stock" {{ request('stock_status') == 'out_of_stock' ? 'selected' : '' }}>Out of Stock (= 0)</option>
                            </select>
                        </div>

                        <!-- Product Type Dropdown -->
                        <div class="col-md-3 col-sm-6">
                            <label class="form-label fs-12 fw-semibold mb-1" for="product_type">Product Type</label>
                            <select class="form-select form-select-sm" id="product_type" name="product_type">
                                <option value="">-- All Types --</option>
                                <option value="raw" {{ request('product_type') == 'raw' ? 'selected' : '' }}>Raw Material</option>
                                <option value="finished" {{ request('product_type') == 'finished' ? 'selected' : '' }}>Finished Goods</option>
                            </select>
                        </div>

                        <!-- Start Date -->
                        <div class="col-md-3 col-sm-6">
                            <label class="form-label fs-12 fw-semibold mb-1" for="start_date">From Date</label>
                            <input type="date" class="form-control form-control-sm" id="start_date" name="start_date" value="{{ request('start_date') }}">
                        </div>

                        <!-- End Date -->
                        <div class="col-md-3 col-sm-6">
                            <label class="form-label fs-12 fw-semibold mb-1" for="end_date">To Date</label>
                            <input type="date" class="form-control form-control-sm" id="end_date" name="end_date" value="{{ request('end_date') }}">
                        </div>

                        <!-- Buttons -->
                        <div class="col-md-3 col-sm-6 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="ri-search-line me-1"></i> Apply Filter
                            </button>
                            <a href="{{ route('reports.inventory.batch') }}" class="btn btn-outline-secondary btn-sm">
                                Clear
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="fs-13 text-muted">Showing <strong>{{ $batches->total() }}</strong> batch records found</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead class="table-light fs-13">
                            <tr>
                                <th>Batch No</th>
                                <th>Product / Variant</th>
                                <th>Warehouse</th>
                                <th class="text-end">Total IN</th>
                                <th class="text-end">Total OUT</th>
                                <th class="text-end">Remaining</th>
                                <th>Created Date</th>
                            </tr>
                        </thead>
                        <tbody class="fs-13">
                            @forelse($batches as $batch)
                            @php
                                $unit = $batch->productVariant ? $batch->productVariant->unit_type : ($batch->product->base_unit ?? '');
                            @endphp
                            <tr>
                                <td><span class="badge bg-light text-dark border fs-12 fw-bold">{{ $batch->batch_no }}</span></td>
                                <td>
                                    <strong>{{ $batch->product->name ?? 'N/A' }}</strong>
                                    @if($batch->productVariant)
                                        <span class="text-muted d-block fs-12">{{ $batch->productVariant->name }}</span>
                                    @endif
                                </td>
                                <td>{{ $batch->warehouse->name ?? 'N/A' }}</td>
                                <td class="text-end text-success fw-semibold"><i class="ri-arrow-down-line"></i> {{ number_format($batch->qty_in, 2) }} {{ $unit }}</td>
                                <td class="text-end text-danger fw-semibold"><i class="ri-arrow-up-line"></i> {{ number_format($batch->qty_out, 2) }} {{ $unit }}</td>
                                <td class="text-end">
                                    @if($batch->remaining_qty <= 0)
                                        <span class="badge bg-secondary fs-12 px-2 py-1">0.00 {{ $unit }} (Out of Stock)</span>
                                    @elseif($batch->remaining_qty <= 10)
                                        <span class="badge bg-danger fs-12 px-2 py-1">{{ number_format($batch->remaining_qty, 2) }} {{ $unit }} (Low)</span>
                                    @else
                                        <span class="badge bg-success fs-12 px-2 py-1">{{ number_format($batch->remaining_qty, 2) }} {{ $unit }}</span>
                                    @endif
                                </td>
                                <td>{{ $batch->created_at ? $batch->created_at->format('M d, Y') : 'N/A' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted fs-13">No batch movement records match your search filter criteria.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3 d-flex justify-content-end">
                    {{ $batches->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
