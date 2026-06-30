@extends('layouts.vertical', ['title' => 'Daily Sales Report'])

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box">
            <h4 class="page-title">Daily Sales Report</h4>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('reports.sales.daily') }}" method="GET" class="row gy-2 gx-2 align-items-center mb-4">
                    <div class="col-auto">
                        <label for="start_date">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="{{ $startDate }}">
                    </div>
                    <div class="col-auto">
                        <label for="end_date">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="{{ $endDate }}">
                    </div>
                    <div class="col-auto align-self-end">
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped datatable">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Total Orders</th>
                                <th>Total Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sales as $sale)
                            <tr>
                                <td><strong>{{ \Carbon\Carbon::parse($sale->sale_date)->format('M d, Y') }}</strong></td>
                                <td>{{ $sale->total_orders }}</td>
                                <td class="text-success fw-bold">${{ number_format($sale->total_revenue, 0) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">No sales found for this period.</td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if($sales->count() > 0)
                        <tfoot>
                            <tr class="table-primary fw-bold">
                                <td>TOTAL</td>
                                <td>{{ $sales->sum('total_orders') }}</td>
                                <td>${{ number_format($sales->sum('total_revenue'), 0) }}</td>
                            </tr>
                        </tfoot>
                        @endif
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

