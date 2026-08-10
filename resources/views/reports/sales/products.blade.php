@extends('layouts.report', ['title' => 'Product Sales Velocity'])

@section('report_content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box">
            <h4 class="page-title">Product Sales Velocity</h4>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('reports.sales.products') }}" method="GET" class="row gy-2 gx-2 align-items-center mb-4">
                    <div class="col-auto">
                        <label for="start_date">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="{{ $startDate }}">
                    </div>
                    <div class="col-auto">
                        <label for="end_date">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="{{ $endDate }}">
                    </div>
                    <div class="col-4">
                        <label for="customer_id">Customer</label>
                        <select name="customer_id" id="customer_id" class="form-select" data-toggle="select2" data-allow-clear="true" data-placeholder="All Customers">
                            <option value="">All Customers</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ $customerId == $customer->id ? 'selected' : '' }}>{{ $customer->name }} ({{ $customer->phone ?? 'N/A' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto align-self-end">
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped datatable">
                        <thead class="table-light">
                            <tr>
                                <th>Product Name</th>
                                <th>Variant</th>
                                <th>Quantity Sold</th>
                                <th>Total Weight (kg)</th>
                                <th>Total Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($productData as $product)
                                <!-- Parent Row -->
                                <tr class="table-secondary fw-bold">
                                    <td colspan="2">{{ $product['product_name'] }}</td>
                                    <td>{{ number_format($product['total_qty'], 0) }}</td>
                                    <td>{{ number_format($product['total_weight'], 3) }}</td>
                                    <td class="text-success">{{ number_format($product['total_revenue'], 0) }}</td>
                                </tr>
                                <!-- Variant Rows -->
                                @foreach($product['variants'] as $variant)
                                <tr>
                                    <td></td>
                                    <td><i class="ri-corner-down-right-line text-muted ms-2"></i> {{ $variant['variant_name'] }}</td>
                                    <td>{{ number_format($variant['qty_sold'], 0) }}</td>
                                    <td>{{ number_format($variant['weight'], 3) }}</td>
                                    <td class="text-success">{{ number_format($variant['revenue'], 0) }}</td>
                                </tr>
                                @endforeach
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No product sales found for this period.</td>
                            </tr>
                            @endforelse
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

