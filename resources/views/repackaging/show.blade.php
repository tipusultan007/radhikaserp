@extends('layouts.vertical', ['page_title' => 'Repackaging Details', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('css')
@endsection

@section('content')
    <div class="container-fluid">
         <div class="row">
            <div class="col-12">
                <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                    <h4 class="page-title">Repackaging Details: {{ $repackaging->ref_no }}</h4>
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('repackaging.index') }}">Repackaging</a></li>
                        <li class="breadcrumb-item active">Details</li>
                    </ol>
                </div>
            </div>
         </div>

         <div class="row">
             <div class="col-lg-12">
                 <div class="card">
                     <div class="card-body">
                         <h4 class="header-title mb-3">Order Information</h4>

                         <div class="row mb-4">
                             <div class="col-md-4">
                                 <p><strong>Ref No:</strong> {{ $repackaging->ref_no }}</p>
                                 <p><strong>Date:</strong> {{ $repackaging->date->format('Y-m-d') }}</p>
                             </div>
                             <div class="col-md-4">
                                 <p><strong>Warehouse:</strong> {{ $repackaging->warehouse->name ?? 'N/A' }}</p>
                                 <p><strong>Created By:</strong> {{ $repackaging->creator->name ?? 'N/A' }}</p>
                             </div>
                             <div class="col-md-4">
                                 <p><strong>Notes:</strong> {{ $repackaging->notes }}</p>
                             </div>
                         </div>

                         <div class="row">
                             <div class="col-md-6 border-end">
                                 <h4 class="header-title mb-3 text-danger"><i class="ri-download-2-fill"></i> Consumed Inputs (Raw Batches)</h4>
                                 <div class="table-responsive">
                                     <table class="table table-sm table-bordered">
                                         <thead>
                                             <tr>
                                                 <th>Batch No</th>
                                                 <th>Product</th>
                                                 <th>Qty Used</th>
                                             </tr>
                                         </thead>
                                         <tbody>
                                             @foreach ($repackaging->inputs as $input)
                                                 <tr>
                                                     <td>{{ $input->batch->batch_no ?? 'N/A' }}</td>
                                                     <td>{{ $input->product->name }}</td>
                                                     <td>{{ number_format($input->qty_used, 3) }}</td>
                                                 </tr>
                                             @endforeach
                                         </tbody>
                                     </table>
                                 </div>
                             </div>

                             <div class="col-md-6">
                                 <h4 class="header-title mb-3 text-success"><i class="ri-upload-2-fill"></i> Produced Output</h4>
                                 <div class="table-responsive">
                                     <table class="table table-sm table-bordered">
                                         <thead>
                                             <tr>
                                                 <th>Variant</th>
                                                 <th>Qty Produced</th>
                                                 <th>Unit Cost</th>
                                                 <th>Total Cost (Inc. Exp)</th>
                                             </tr>
                                         </thead>
                                         <tbody>
                                             @foreach ($repackaging->outputs as $output)
                                                 <tr>
                                                     <td>
                                                         @if($output->product_variant_id)
                                                             {{ $output->productVariant->product->name ?? 'N/A' }} - {{ $output->productVariant->name ?? 'N/A' }}
                                                         @else
                                                             {{ $output->product->name ?? 'N/A' }} ({{ $output->product->base_unit ?? '' }})
                                                         @endif
                                                     </td>
                                                     <td>{{ number_format($output->qty_produced, 3) }}</td>
                                                     <td>${{ number_format($output->unit_cost, 0) }}</td>
                                                     <td>${{ number_format($output->total_cost, 0) }}</td>
                                                 </tr>
                                             @endforeach
                                         </tbody>
                                     </table>
                                 </div>
                                 
                                 @if($repackaging->adjustments->count() > 0)
                                     <h5 class="mt-3">Yield Adjustments</h5>
                                     <ul>
                                         @foreach($repackaging->adjustments as $adj)
                                             <li class="{{ $adj->type == 'gain' ? 'text-success' : 'text-danger' }}">
                                                 {{ ucfirst($adj->type) }}: {{ number_format($adj->qty, 3) }} kg ({{ $adj->reason }})
                                             </li>
                                         @endforeach
                                     </ul>
                                 @endif
                             </div>
                         </div>

                         <div class="mt-4">
                             <a href="{{ route('repackaging.index') }}" class="btn btn-light"><i class="ri-arrow-left-line me-1"></i> Back to Repackaging Orders</a>
                         </div>
                     </div>
                 </div>
             </div>
         </div>
    </div>
@endsection

