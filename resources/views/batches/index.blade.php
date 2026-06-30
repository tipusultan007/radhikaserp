@extends('layouts.vertical', ['page_title' => 'Batch Tracking', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('css')
@endsection

@section('content')
    <div class="container-fluid">
         <div class="row">
            <div class="col-12">
                <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                    <h4 class="page-title">Batch Tracking</h4>
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                        <li class="breadcrumb-item active">Batches</li>
                    </ol>
                </div>
            </div>
         </div>

         <div class="row">
             <div class="col-12">
                 <div class="card">
                     <div class="card-body">
                         <div class="table-responsive">
                             <table class="table table-centered table-striped dt-responsive nowrap w-100" id="batches-datatable">
                                 <thead>
                                     <tr>
                                         <th>Batch No</th>
                                         <th>Product</th>
                                         <th>Warehouse</th>
                                         <th>Qty In</th>
                                         <th>Qty Out</th>
                                         <th>Remaining Qty</th>
                                         <th>Cost/Unit</th>
                                         <th>Status</th>
                                     </tr>
                                 </thead>
                                 <tbody>
                                     @foreach ($batches as $batch)
                                         <tr>
                                             <td><b>{{ $batch->batch_no }}</b></td>
                                             <td>{{ $batch->product->name ?? 'N/A' }}</td>
                                             <td>{{ $batch->warehouse->name ?? 'N/A' }}</td>
                                             <td>{{ number_format($batch->qty_in, 3) }}</td>
                                             <td>{{ number_format($batch->qty_out, 3) }}</td>
                                             <td>
                                                 <strong>{{ number_format($batch->remaining_qty, 3) }}</strong>
                                             </td>
                                             <td>${{ number_format($batch->cost_per_unit, 0) }}</td>
                                             <td>
                                                 @if($batch->remaining_qty > 0)
                                                     <span class="badge bg-success">Available</span>
                                                 @else
                                                     <span class="badge bg-danger">Depleted</span>
                                                 @endif
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
    </div>
@endsection

