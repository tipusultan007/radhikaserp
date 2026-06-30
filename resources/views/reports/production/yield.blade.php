@extends('layouts.vertical', ['title' => 'Repackaging Yield Report'])

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box">
            <h4 class="page-title">Repackaging Yield Report</h4>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('reports.production.yield') }}" method="GET" class="row gy-2 gx-2 align-items-center mb-4">
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
                                <th>Order Ref</th>
                                <th>Date</th>
                                <th>Input (Kg/Lbs)</th>
                                <th>Output Equivalent</th>
                                <th>Variance</th>
                                <th>Yield %</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($yieldData as $data)
                            <tr>
                                <td><a href="{{ route('repackaging.show', $data['order']->id) }}">{{ $data['order']->ref_no }}</a></td>
                                <td>{{ \Carbon\Carbon::parse($data['order']->date)->format('M d, Y') }}</td>
                                <td>{{ number_format($data['input_weight'], 3) }}</td>
                                <td>{{ number_format($data['output_weight'], 3) }}</td>
                                <td>
                                    @if($data['diff'] > 0)
                                        <span class="text-success">+{{ number_format($data['diff'], 3) }} (Gain)</span>
                                    @elseif($data['diff'] < 0)
                                        <span class="text-danger">{{ number_format($data['diff'], 3) }} (Loss)</span>
                                    @else
                                        <span class="text-muted">0.000</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $data['yield_pct'] >= 100 ? 'bg-success' : 'bg-danger' }}">
                                        {{ number_format($data['yield_pct'], 1) }}%
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No repackaging orders found for this period.</td>
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
    $('.datatable').DataTable({
        "order": [[ 1, "desc" ]]
    });
});
</script>
@endpush
