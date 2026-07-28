@extends('layouts.vertical', ['page_title' => 'Repackaging Details', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('css')
<style>
    .info-box {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        border-left: 4px solid #3bc0c3;
    }
    .info-label {
        font-size: 12px;
        text-transform: uppercase;
        color: #6c757d;
        font-weight: 600;
        margin-bottom: 5px;
    }
    .info-value {
        font-size: 15px;
        font-weight: 500;
        color: #343a40;
        margin: 0;
    }
</style>
@endsection

@section('content')
    <div class="container-fluid">
         <div class="row">
            <div class="col-12">
                <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                    <h4 class="page-title">Repackaging Details</h4>
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('repackaging.index') }}">Repackaging</a></li>
                        <li class="breadcrumb-item active">{{ $repackaging->ref_no }}</li>
                    </ol>
                </div>
            </div>
         </div>

         <div class="row">
             <div class="col-12">
                 <div class="card shadow-sm border-0">
                     <div class="card-header bg-light d-flex justify-content-between align-items-center py-3">
                         <h4 class="header-title mb-0 text-dark"><i class="ri-box-3-fill text-primary me-2 fs-20 align-middle"></i> Order Reference: <span class="text-primary">{{ $repackaging->ref_no }}</span></h4>
                         <div class="d-flex gap-2">
                             <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="ri-printer-line me-1"></i> Print</button>
                             <a href="{{ route('repackaging.index') }}" class="btn btn-sm btn-primary"><i class="ri-arrow-left-line me-1"></i> Back</a>
                         </div>
                     </div>
                     <div class="card-body p-4">
                         
                         <!-- Order Information row -->
                         <div class="row g-3 mb-4 pb-4 border-bottom">
                             <div class="col-md-3 col-sm-6">
                                 <div class="info-box border-primary text-start">
                                     <div class="info-label">Order Date</div>
                                     <p class="info-value"><i class="ri-calendar-event-line me-1 text-primary"></i> {{ $repackaging->date->format('d M, Y') }}</p>
                                 </div>
                             </div>
                             <div class="col-md-3 col-sm-6">
                                 <div class="info-box border-success text-start">
                                     <div class="info-label">Warehouse</div>
                                     <p class="info-value"><i class="ri-store-2-line me-1 text-success"></i> {{ $repackaging->warehouse->name ?? 'N/A' }}</p>
                                 </div>
                             </div>
                             <div class="col-md-3 col-sm-6">
                                 <div class="info-box border-info text-start">
                                     <div class="info-label">Created By</div>
                                     <p class="info-value"><i class="ri-user-line me-1 text-info"></i> {{ $repackaging->creator->name ?? 'N/A' }}</p>
                                 </div>
                             </div>
                             <div class="col-md-3 col-sm-6">
                                 <div class="info-box border-warning text-start">
                                     <div class="info-label">Notes / Remarks</div>
                                     <p class="info-value text-truncate" title="{{ $repackaging->notes }}"><i class="ri-file-text-line me-1 text-warning"></i> {{ $repackaging->notes ?: 'None' }}</p>
                                 </div>
                             </div>
                         </div>

                         <!-- Tables Row -->
                         <div class="row">
                             <!-- Consumed Inputs -->
                             <div class="col-xl-5 col-lg-6 mb-4">
                                 <div class="card border border-danger-subtle h-100 shadow-none mb-0">
                                     <div class="card-header bg-danger-subtle py-2">
                                         <h5 class="card-title text-danger mb-0"><i class="ri-download-2-fill me-1"></i> Consumed Raw Materials</h5>
                                     </div>
                                     <div class="card-body p-0">
                                         <div class="table-responsive">
                                             <table class="table table-sm table-hover table-centered mb-0">
                                                 <thead class="table-light">
                                                     <tr>
                                                         <th class="ps-3">Raw Product</th>
                                                         <th>Batch No</th>
                                                         <th class="text-end pe-3">Qty Used</th>
                                                     </tr>
                                                 </thead>
                                                 <tbody>
                                                     @php $totalInput = 0; @endphp
                                                     @foreach ($repackaging->inputs as $input)
                                                         @php $totalInput += $input->qty_used; @endphp
                                                         <tr>
                                                             <td class="ps-3 fw-medium text-dark">{{ $input->product->name }}</td>
                                                             <td><span class="badge bg-light text-dark border">{{ $input->batch->batch_no ?? 'N/A' }}</span></td>
                                                             <td class="text-end pe-3 fw-bold">{{ number_format($input->qty_used, 3) }}</td>
                                                         </tr>
                                                     @endforeach
                                                 </tbody>
                                                 <tfoot class="table-light border-top">
                                                     <tr>
                                                         <th colspan="2" class="text-end ps-3">Total Input Quantity:</th>
                                                         <th class="text-end pe-3 text-danger fs-15">{{ number_format($totalInput, 3) }}</th>
                                                     </tr>
                                                 </tfoot>
                                             </table>
                                         </div>
                                     </div>
                                 </div>
                             </div>

                             <!-- Produced Output -->
                             <div class="col-xl-7 col-lg-6 mb-4">
                                 <div class="card border border-success-subtle h-100 shadow-none mb-0">
                                     <div class="card-header bg-success-subtle py-2">
                                         <h5 class="card-title text-success mb-0"><i class="ri-upload-2-fill me-1"></i> Produced Finished Goods</h5>
                                     </div>
                                     <div class="card-body p-0">
                                         <div class="table-responsive">
                                             <table class="table table-sm table-hover table-centered mb-0">
                                                 <thead class="table-light">
                                                     <tr>
                                                         <th class="ps-3">Variant / Product</th>
                                                         <th class="text-end">Qty Produced</th>
                                                         <th class="text-end">Unit Cost</th>
                                                         <th class="text-end pe-3">Total Cost</th>
                                                     </tr>
                                                 </thead>
                                                 <tbody>
                                                     @php $totalOutputCost = 0; $totalOutputQty = 0; @endphp
                                                     @foreach ($repackaging->outputs as $output)
                                                         @php 
                                                            $totalOutputCost += $output->total_cost; 
                                                            $totalOutputQty += $output->qty_produced;
                                                         @endphp
                                                         <tr>
                                                             <td class="ps-3 fw-medium text-dark">
                                                                 @if($output->product_variant_id)
                                                                     {{ $output->productVariant->product->name ?? 'N/A' }} <span class="text-muted fs-12 ms-1">({{ $output->productVariant->name ?? 'N/A' }})</span>
                                                                 @else
                                                                     {{ $output->product->name ?? 'N/A' }} <span class="text-muted fs-12 ms-1">({{ $output->product->base_unit ?? '' }})</span>
                                                                 @endif
                                                             </td>
                                                             <td class="text-end fw-bold">{{ number_format($output->qty_produced, 3) }}</td>
                                                             <td class="text-end text-muted">{{ number_format($output->unit_cost, 2) }}</td>
                                                             <td class="text-end pe-3 text-success fw-bold">{{ number_format($output->total_cost, 2) }}</td>
                                                         </tr>
                                                     @endforeach
                                                 </tbody>
                                                 <tfoot class="table-light border-top">
                                                     <tr>
                                                         <th class="text-end ps-3">Grand Total:</th>
                                                         <th class="text-end">{{ number_format($totalOutputQty, 3) }}</th>
                                                         <th></th>
                                                         <th class="text-end pe-3 text-success fs-15">{{ number_format($totalOutputCost, 2) }}</th>
                                                     </tr>
                                                 </tfoot>
                                             </table>
                                         </div>
                                     </div>
                                 </div>
                             </div>
                         </div>

                         <!-- Yield Adjustments Section -->
                         @if($repackaging->adjustments->count() > 0)
                             <div class="row mt-2">
                                 <div class="col-12">
                                     <h5 class="mb-3 text-uppercase fs-13 text-muted"><i class="ri-scales-3-line me-1"></i> Yield Analysis</h5>
                                     <div class="d-flex flex-column gap-2">
                                         @foreach($repackaging->adjustments as $adj)
                                             @if($adj->type == 'gain')
                                                 <div class="alert alert-success border-0 shadow-sm mb-0 d-flex align-items-center">
                                                     <i class="ri-arrow-up-circle-fill fs-24 me-3"></i>
                                                     <div>
                                                         <h5 class="alert-heading font-15 mb-1">Production Gain</h5>
                                                         <p class="mb-0">Yielded an excess of <strong>{{ number_format($adj->qty, 3) }} kg</strong>. Reason: {{ $adj->reason }}</p>
                                                     </div>
                                                 </div>
                                             @else
                                                 <div class="alert alert-danger border-0 shadow-sm mb-0 d-flex align-items-center">
                                                     <i class="ri-arrow-down-circle-fill fs-24 me-3"></i>
                                                     <div>
                                                         <h5 class="alert-heading font-15 mb-1">Production Loss</h5>
                                                         <p class="mb-0">Lost <strong>{{ number_format($adj->qty, 3) }} kg</strong> during repackaging. Reason: {{ $adj->reason }}</p>
                                                     </div>
                                                 </div>
                                             @endif
                                         @endforeach
                                     </div>
                                 </div>
                             </div>
                         @endif

                     </div>
                 </div>
             </div>
         </div>
    </div>
@endsection

