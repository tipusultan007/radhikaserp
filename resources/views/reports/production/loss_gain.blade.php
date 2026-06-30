@extends('layouts.vertical', ['title' => 'Loss & Gain Report'])

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box">
            <h4 class="page-title">Manufacturing Loss & Gain Report</h4>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-4">
        <div class="card text-center bg-danger text-white">
            <div class="card-body">
                <h3>{{ number_format($totalLoss, 3) }} Units</h3>
                <p class="mb-0">Total Manufacturing Loss</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center bg-success text-white">
            <div class="card-body">
                <h3>{{ number_format($totalGain, 3) }} Units</h3>
                <p class="mb-0">Total Manufacturing Gain</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center {{ $net >= 0 ? 'bg-primary' : 'bg-warning' }} text-white">
            <div class="card-body">
                <h3>{{ $net > 0 ? '+' : '' }}{{ number_format($net, 3) }} Units</h3>
                <p class="mb-0">Net Variance</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('reports.production.loss_gain') }}" method="GET" class="row gy-2 gx-2 align-items-center mb-4">
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
                                <th>Order Ref</th>
                                <th>Type</th>
                                <th>Quantity Variance</th>
                                <th>Reason / Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($adjustments as $adj)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($adj->repackagingOrder->date)->format('M d, Y') }}</td>
                                <td><a href="{{ route('repackaging.show', $adj->repackaging_order_id) }}">{{ $adj->repackagingOrder->ref_no }}</a></td>
                                <td>
                                    @if($adj->type == 'loss')
                                        <span class="badge bg-danger">Loss</span>
                                    @else
                                        <span class="badge bg-success">Gain</span>
                                    @endif
                                </td>
                                <td class="fw-bold {{ $adj->type == 'loss' ? 'text-danger' : 'text-success' }}">
                                    {{ $adj->type == 'loss' ? '-' : '+' }}{{ number_format($adj->qty, 3) }}
                                </td>
                                <td>{{ $adj->reason ?? 'System calculated during repackaging' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No loss or gain adjustments recorded for this period.</td>
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
