@extends('layouts.vertical', ['title' => 'Profit & Margins Report'])

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box">
            <h4 class="page-title">Profit & Margins Report</h4>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-3">
        <div class="card text-center bg-primary text-white">
            <div class="card-body">
                <h3>${{ number_format($totalRevenue, 0) }}</h3>
                <p class="mb-0">Total Revenue</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center bg-danger text-white">
            <div class="card-body">
                <h3>${{ number_format($totalCogs, 0) }}</h3>
                <p class="mb-0">Total COGS</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center bg-success text-white">
            <div class="card-body">
                <h3>${{ number_format($totalProfit, 0) }}</h3>
                <p class="mb-0">Gross Profit</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center bg-info text-white">
            <div class="card-body">
                <h3>{{ number_format($averageMargin, 0) }}%</h3>
                <p class="mb-0">Average Margin</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('reports.sales.profit') }}" method="GET" class="row gy-2 gx-2 align-items-center mb-4">
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
                    <table class="table table-bordered datatable">
                        <thead class="table-light">
                            <tr>
                                <th>Invoice</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Revenue</th>
                                <th>COGS</th>
                                <th>Gross Profit</th>
                                <th>Margin (%)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($profitData as $data)
                            <tr>
                                <td>
                                    <a href="{{ route('sales.show', $data['sale']->id) }}" class="fw-bold">{{ $data['sale']->invoice_no }}</a>
                                </td>
                                <td>{{ \Carbon\Carbon::parse($data['sale']->date)->format('M d, Y') }}</td>
                                <td>{{ $data['sale']->customer->name ?? 'Walk-in Customer' }}</td>
                                <td class="text-success">${{ number_format($data['revenue'], 0) }}</td>
                                <td class="text-danger">${{ number_format($data['cogs'], 0) }}</td>
                                <td class="fw-bold">${{ number_format($data['profit'], 0) }}</td>
                                <td>
                                    <span class="badge {{ $data['margin'] >= 20 ? 'bg-success' : ($data['margin'] > 0 ? 'bg-warning' : 'bg-danger') }}">
                                        {{ number_format($data['margin'], 1) }}%
                                    </span>
                                </td>
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
    $('.datatable').DataTable({
        "order": [[ 1, "desc" ]]
    });
});
</script>
@endpush

