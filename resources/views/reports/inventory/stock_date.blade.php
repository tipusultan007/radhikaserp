@extends('layouts.vertical', ['title' => 'Stock by Date'])

@section('content')
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
                                <th>Calculated Qty</th>
                                <th>Total Weight</th>
                                <th>Est. Total Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stock as $item)
                            <tr>
                                <td>{{ $item['warehouse'] }}</td>
                                <td>
                                    @if($item['type'] == 'raw')
                                        <span class="badge bg-secondary">Raw</span>
                                    @else
                                        <span class="badge bg-success">Finished</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $item['product_name'] }} 
                                    @if($item['variant_name'] !== 'N/A')
                                        - {{ $item['variant_name'] }}
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
                                <td>${{ number_format($item['value'], 0) }}</td>
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

