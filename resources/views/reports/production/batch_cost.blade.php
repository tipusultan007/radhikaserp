@extends('layouts.vertical', ['title' => 'Cost per Batch (Internally Produced)'])

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box">
            <h4 class="page-title">Cost per Batch (Internally Produced)</h4>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-4">
                    This report shows the unit cost of batches that were produced internally (via repackaging/manufacturing) rather than imported or purchased directly.
                </p>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>Created Date</th>
                                <th>Batch No</th>
                                <th>Product / Variant</th>
                                <th>Warehouse</th>
                                <th>Produced Qty</th>
                                <th>Unit Cost</th>
                                <th>Total Cost Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($batches as $batch)
                            <tr>
                                <td>{{ $batch->created_at->format('M d, Y') }}</td>
                                <td><strong>{{ $batch->batch_no }}</strong></td>
                                <td>
                                    {{ $batch->product->name }}
                                    @if($batch->productVariant)
                                        - {{ $batch->productVariant->name }}
                                    @endif
                                </td>
                                <td>{{ $batch->warehouse->name ?? 'N/A' }}</td>
                                <td>{{ number_format($batch->qty_in, 3) }}</td>
                                <td class="fw-bold text-danger">${{ number_format($batch->cost_per_unit, 0) }}</td>
                                <td>${{ number_format($batch->qty_in * $batch->cost_per_unit, 0) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">No internally produced batches found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $batches->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

