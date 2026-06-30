@extends('layouts.vertical', ['title' => 'Batch Movement'])

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box">
            <h4 class="page-title">Batch Movement Report</h4>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('reports.inventory.batch') }}" method="GET" class="row gy-2 gx-2 align-items-center mb-4">
                    <div class="col-auto">
                        <label class="visually-hidden" for="batch_no">Batch Number</label>
                        <input type="text" class="form-control" id="batch_no" name="batch_no" placeholder="Search Batch No" value="{{ request('batch_no') }}">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Batch No</th>
                                <th>Product / Variant</th>
                                <th>Warehouse</th>
                                <th>Total IN</th>
                                <th>Total OUT</th>
                                <th>Remaining</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($batches as $batch)
                            @php
                                $unit = $batch->productVariant ? $batch->productVariant->unit_type : $batch->product->base_unit;
                            @endphp
                            <tr>
                                <td><strong>{{ $batch->batch_no }}</strong></td>
                                <td>
                                    {{ $batch->product->name }}
                                    @if($batch->productVariant)
                                        - {{ $batch->productVariant->name }}
                                    @endif
                                </td>
                                <td>{{ $batch->warehouse->name ?? 'N/A' }}</td>
                                <td class="text-success"><i class="ri-arrow-down-line"></i> {{ number_format($batch->qty_in, 2) }} {{ $unit }}</td>
                                <td class="text-danger"><i class="ri-arrow-up-line"></i> {{ number_format($batch->qty_out, 2) }} {{ $unit }}</td>
                                <td><strong>{{ number_format($batch->remaining_qty, 2) }} {{ $unit }}</strong></td>
                                <td>{{ $batch->created_at->format('M d, Y') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">No batches found</td>
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
