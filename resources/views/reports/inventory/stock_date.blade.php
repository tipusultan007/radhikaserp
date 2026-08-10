@extends('layouts.report', ['title' => 'Stock by Date'])

@section('report_content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box">
            <h4 class="page-title">Stock by Date (Historical)</h4>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('reports.inventory.date') }}" method="GET" class="row gy-2 gx-2 align-items-center mb-4">
                    <div class="col-auto">
                        <label class="visually-hidden" for="date">Date</label>
                        <input type="date" class="form-control" id="date" name="date" value="{{ $date }}">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">Generate Report</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered datatable">
                        <thead class="table-light">
                            <tr>
                                <th>Warehouse</th>
                                <th>Type</th>
                                <th>Product / Variant</th>
                                <th>Stock Qty</th>
                                <th>Total Weight</th>
                                <th>Est. Total Value (৳)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(collect($stock)->groupBy('product_name') as $productName => $items)
                                <tr class="table-light">
                                    <td colspan="6" class="fw-bold text-primary">{{ $productName }}</td>
                                </tr>
                                @foreach($items as $item)
                                <tr>
                                    <td>{{ $item['warehouse'] }}</td>
                                    <td>
                                        @if($item['type'] == 'raw')
                                            <span class="badge bg-secondary">Raw</span>
                                        @else
                                            <span class="badge bg-success">Finished</span>
                                        @endif
                                    </td>
                                    <td class="ps-4">
                                        @if($item['variant_name'] !== 'N/A')
                                            - {{ $item['variant_name'] }}
                                        @else
                                            Standalone
                                        @endif
                                    </td>
                                    <td>{{ number_format($item['qty'], 2) }} {{ $item['unit'] }}</td>
                                    <td>
                                        @if($item['variant_name'] !== 'N/A' && !empty($item['variant_unit_qty']))
                                            {{ number_format($item['qty'] * $item['variant_unit_qty'], 2) }} {{ $item['base_unit'] }}
                                        @else
                                            {{ number_format($item['qty'], 2) }} {{ $item['base_unit'] }}
                                        @endif
                                    </td>
                                    <td>{{ number_format($item['value'], 0) }}</td>
                                </tr>
                                @endforeach
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
    $('.datatable').DataTable({
        ordering: false,
        pageLength: 50
    });
});
</script>
@endpush

