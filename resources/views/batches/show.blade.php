@extends('layouts.vertical', ['page_title' => 'Batch Details', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('content')
    <div class="container-fluid">
         <div class="row">
            <div class="col-12">
                <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                    <h4 class="page-title">Batch Details</h4>
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('batches.index') }}">Batches</a></li>
                        <li class="breadcrumb-item active">Details</li>
                    </ol>
                </div>
            </div>
         </div>

         <div class="row">
             <div class="col-lg-8">
                 <div class="card">
                     <div class="card-body">
                         <h4 class="header-title mb-3">Batch #{{ $batch->batch_no }}</h4>
                         <div class="table-responsive">
                             <table class="table table-bordered mb-0">
                                 <tbody>
                                     <tr>
                                         <th scope="row" style="width: 30%;">Batch No</th>
                                         <td>{{ $batch->batch_no }}</td>
                                     </tr>
                                     <tr>
                                         <th scope="row">Product</th>
                                         <td>{{ $batch->product->name ?? 'N/A' }}</td>
                                     </tr>
                                     <tr>
                                         <th scope="row">Warehouse</th>
                                         <td>{{ $batch->warehouse->name ?? 'N/A' }}</td>
                                     </tr>
                                     <tr>
                                         <th scope="row">Purchase ID</th>
                                         <td>
                                             @if($batch->purchase_id)
                                                <a href="{{ route('purchases.show', $batch->purchase_id) }}">#{{ $batch->purchase_id }}</a>
                                             @else
                                                N/A
                                             @endif
                                         </td>
                                     </tr>
                                     <tr>
                                         <th scope="row">Cost Per Unit</th>
                                         <td>${{ number_format($batch->cost_per_unit, 2) }}</td>
                                     </tr>
                                     <tr>
                                         <th scope="row">Qty In</th>
                                         <td>{{ number_format($batch->qty_in, 3) }}</td>
                                     </tr>
                                     <tr>
                                         <th scope="row">Qty Out</th>
                                         <td>{{ number_format($batch->qty_out, 3) }}</td>
                                     </tr>
                                     <tr>
                                         <th scope="row">Remaining Qty</th>
                                         <td><strong>{{ number_format($batch->remaining_qty, 3) }}</strong></td>
                                     </tr>
                                     <tr>
                                         <th scope="row">Expiry Date</th>
                                         <td>{{ $batch->expiry_date ? \Carbon\Carbon::parse($batch->expiry_date)->format('M d, Y') : 'N/A' }}</td>
                                     </tr>
                                     <tr>
                                         <th scope="row">Status</th>
                                         <td>
                                             @if($batch->remaining_qty > 0)
                                                 <span class="badge bg-success">Available</span>
                                             @else
                                                 <span class="badge bg-danger">Depleted</span>
                                             @endif
                                         </td>
                                     </tr>
                                 </tbody>
                             </table>
                         </div>
                         <div class="mt-4">
                             <a href="{{ route('batches.edit', $batch->id) }}" class="btn btn-primary me-1"><i class="ri-edit-box-line"></i> Edit Batch</a>
                             <a href="{{ route('batches.index') }}" class="btn btn-light">Back to List</a>
                         </div>
                     </div>
                 </div>
             </div>
         </div>
    </div>
@endsection
