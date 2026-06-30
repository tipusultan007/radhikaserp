@extends('layouts.vertical', ['title' => 'Monthly Sales Report'])

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box">
            <h4 class="page-title">Monthly Sales Report ({{ $year }})</h4>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('reports.sales.monthly') }}" method="GET" class="row gy-2 gx-2 align-items-center mb-4">
                    <div class="col-auto">
                        <label for="year">Select Year</label>
                        <select name="year" id="year" class="form-select">
                            @for($i = date('Y'); $i >= date('Y') - 5; $i--)
                                <option value="{{ $i }}" {{ $year == $i ? 'selected' : '' }}>{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-auto align-self-end">
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>Month</th>
                                <th>Total Orders</th>
                                <th>Total Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sales as $sale)
                            <tr>
                                <td><strong>{{ \Carbon\Carbon::create()->month($sale->sale_month)->format('F') }}</strong></td>
                                <td>{{ $sale->total_orders }}</td>
                                <td class="text-success fw-bold">${{ number_format($sale->total_revenue, 0) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">No sales found for this year.</td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if($sales->count() > 0)
                        <tfoot>
                            <tr class="table-primary fw-bold">
                                <td>TOTAL FOR {{ $year }}</td>
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

