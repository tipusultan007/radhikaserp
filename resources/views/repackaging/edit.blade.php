@extends('layouts.vertical', ['page_title' => 'Edit Repackaging Order', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('content')
    <div class="container-fluid">
         <div class="row">
            <div class="col-12">
                <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                    <h4 class="page-title">Edit Repackaging: {{ $repackaging->ref_no }}</h4>
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('repackaging.index') }}">Repackaging</a></li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </div>
            </div>
         </div>

         <form action="{{ route('repackaging.update', $repackaging->id) }}" method="POST" id="repackagingForm">
             @csrf
             @method('PUT')
             
             <!-- Order Details Card -->
             <div class="row">
                 <div class="col-lg-12">
                     <div class="card shadow-sm border-0">
                         <div class="card-header bg-light border-bottom">
                             <h4 class="header-title mb-0">Order Details</h4>
                         </div>
                         <div class="card-body">
                             @if ($errors->any())
                                 <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                     <ul class="mb-0">
                                         @foreach ($errors->all() as $error)
                                             <li>{{ $error }}</li>
                                         @endforeach
                                     </ul>
                                     <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                 </div>
                             @endif

                             <div class="row gx-3">
                                 <div class="col-md-6">
                                     <div class="mb-3">
                                         <label class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                                         <div class="input-group">
                                             <span class="input-group-text"><i class="ri-calendar-event-line"></i></span>
                                             <input type="text" name="date" class="form-control flatpickr-date" value="{{ old('date', $repackaging->date->format('Y-m-d')) }}" required>
                                         </div>
                                     </div>
                                 </div>
                                 <div class="col-md-6">
                                     <div class="mb-3">
                                         <label class="form-label fw-semibold">Warehouse <span class="text-danger">*</span></label>
                                         <select name="warehouse_id" class="form-select select2" required>
                                             <option value="">Select Warehouse...</option>
                                             @foreach($warehouses as $wh)
                                                 <option value="{{ $wh->id }}" {{ $repackaging->warehouse_id == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                                             @endforeach
                                         </select>
                                     </div>
                                 </div>
                             </div>
                         </div>
                     </div>
                 </div>
             </div>

             <!-- Conversion Split -->
             <div class="row mt-2">
                 <!-- Input Section -->
                 <div class="col-md-6">
                     <div class="card shadow-sm border-0 h-100 border-top border-danger border-3">
                         <div class="card-body">
                             <div class="d-flex align-items-center mb-4">
                                 <div class="avatar-sm flex-shrink-0">
                                     <span class="avatar-title bg-danger-subtle text-danger rounded fs-3">
                                         <i class="ri-download-2-line"></i>
                                     </span>
                                 </div>
                                 <div class="flex-grow-1 ms-3">
                                     <h4 class="mb-0 text-danger">Raw Input</h4>
                                     <span class="text-muted fs-13">Materials to be consumed</span>
                                 </div>
                             </div>

                             @php
                                 $input = $repackaging->inputs->first();
                                 $inputQty = $input ? $input->qty_used : 0;
                                 $inputProductId = $input ? $input->product_id : '';
                             @endphp

                             <div class="mb-4">
                                 <label class="form-label fw-semibold">Input Product <span class="text-danger">*</span></label>
                                 <select name="input_product_id" id="input_product_id" class="form-select select2" required>
                                     <option value="" data-unit="">Search Product...</option>
                                     @foreach($inputProducts as $product)
                                         <option value="{{ $product->id }}" data-unit="{{ $product->base_unit }}" {{ $inputProductId == $product->id ? 'selected' : '' }}>{{ $product->name }} ({{ ucfirst($product->type) }} - {{ $product->base_unit }})</option>
                                     @endforeach
                                 </select>
                                 <small class="text-muted mt-1 d-block"><i class="ri-information-line"></i> FIFO logic consumes oldest batches automatically.</small>
                             </div>
                             
                             <div class="mb-3">
                                 <label class="form-label fw-semibold">Total Quantity to Consume <span class="text-danger">*</span></label>
                                 <div class="input-group input-group-lg">
                                     <input type="number" step="0.001" min="0.001" name="input_qty" id="input_qty" class="form-control" value="{{ $inputQty }}" placeholder="0.00" required>
                                     <span class="input-group-text fw-bold text-danger" id="input_unit_display">Unit</span>
                                 </div>
                                 <div class="mt-2 text-end">
                                    <span class="badge bg-danger-subtle text-danger fs-13 px-2 py-1" id="input_summary" style="display:none;"></span>
                                 </div>
                             </div>
                         </div>
                     </div>
                 </div>
                 
                 <!-- Output Section -->
                 <div class="col-md-6">
                     <div class="card shadow-sm border-0 h-100 border-top border-success border-3 mt-3 mt-md-0">
                         <div class="card-body">
                             <div class="d-flex align-items-center mb-4">
                                 <div class="avatar-sm flex-shrink-0">
                                     <span class="avatar-title bg-success-subtle text-success rounded fs-3">
                                         <i class="ri-upload-2-line"></i>
                                     </span>
                                 </div>
                                 <div class="flex-grow-1 ms-3">
                                     <h4 class="mb-0 text-success">Finished Output</h4>
                                     <span class="text-muted fs-13">Products to be generated</span>
                                 </div>
                             </div>

                             @php
                                 $output = $repackaging->outputs->first();
                                 $outputQty = $output ? $output->qty_produced : 0;
                                 $outputItem = '';
                                 if($output) {
                                     if($output->product_variant_id) {
                                         $outputItem = 'variant_' . $output->product_variant_id;
                                     } else {
                                         $outputItem = 'product_' . $output->product_id;
                                     }
                                 }
                             @endphp

                             <div class="mb-4">
                                 <label class="form-label fw-semibold">Output Item (Product or Variant) <span class="text-danger">*</span></label>
                                 <select name="output_item" id="output_item" class="form-select select2" required>
                                     <option value="" data-unit="" data-qty="1">Search Target Item...</option>
                                     <optgroup label="Standalone Products">
                                         @foreach($finishedProducts as $product)
                                             <option value="product_{{ $product->id }}" data-unit="{{ $product->base_unit }}" data-qty="1" {{ $outputItem == 'product_'.$product->id ? 'selected' : '' }}>{{ $product->name }} ({{ $product->base_unit }})</option>
                                         @endforeach
                                     </optgroup>
                                     <optgroup label="Product Variants">
                                         @foreach($variants as $variant)
                                             <option value="variant_{{ $variant->id }}" data-unit="{{ $variant->product->base_unit }}" data-qty="{{ $variant->unit_qty }}" {{ $outputItem == 'variant_'.$variant->id ? 'selected' : '' }}>{{ $variant->product->name }} - {{ $variant->name }}</option>
                                         @endforeach
                                     </optgroup>
                                 </select>
                             </div>
                             
                             <div class="mb-3">
                                 <label class="form-label fw-semibold">Quantity Produced (Packages/Units) <span class="text-danger">*</span></label>
                                 <div class="input-group input-group-lg">
                                     <input type="number" step="0.001" min="0.001" name="output_qty" id="output_qty" class="form-control" value="{{ $outputQty }}" placeholder="0.00" required>
                                     <span class="input-group-text fw-bold text-success" id="output_unit_display">Pkg</span>
                                 </div>
                                 <div class="mt-2 text-end">
                                    <span class="badge bg-success-subtle text-success fs-13 px-2 py-1" id="output_summary" style="display:none;"></span>
                                 </div>
                             </div>
                         </div>
                     </div>
                 </div>
             </div>

             <!-- Yield & Expenses -->
             <div class="row mt-3">
                 <div class="col-12">
                     <div class="card shadow-sm border-0">
                         <div class="card-body">
                             <div class="alert alert-info border-0 shadow-none bg-primary-subtle text-primary p-3 mb-4 rounded" id="yield_summary_box" style="display:none;">
                                 <div class="d-flex align-items-center">
                                     <i class="ri-scales-3-line fs-3 me-3"></i>
                                     <div>
                                         <h5 class="mb-1 fw-bold">Conversion Yield Analysis</h5>
                                         <span id="yield_text" class="fs-14"></span>
                                     </div>
                                 </div>
                             </div>

                             <div class="row gx-3">
                                 <div class="col-md-6">
                                     <div class="mb-3">
                                         <label class="form-label fw-semibold">Repackaging Expenses (Labor/Packing) - Optional</label>
                                         <div class="input-group">
                                             <span class="input-group-text">$</span>
                                             <input type="number" step="1" min="0" name="expenses" class="form-control" value="{{ $repackaging->outputs->sum('unit_cost') - $repackaging->inputs->sum('unit_cost') }}">
                                         </div>
                                         <small class="text-muted mt-1 d-block"><i class="ri-information-line"></i> This cost will be capitalized into the finished stock value.</small>
                                     </div>
                                 </div>
                                 <div class="col-md-6">
                                     <div class="mb-3">
                                         <label class="form-label fw-semibold">Internal Notes</label>
                                         <textarea name="notes" class="form-control" rows="2" placeholder="Any additional notes about this batch...">{{ $repackaging->notes }}</textarea>
                                     </div>
                                 </div>
                             </div>
                         </div>
                         
                         <div class="card-footer bg-white border-top text-end py-3">
                             <a href="{{ route('repackaging.index') }}" class="btn btn-light me-2"><i class="ri-close-line"></i> Cancel</a>
                             <button type="submit" class="btn btn-primary" id="submitBtn"><i class="ri-save-line"></i> Update Conversion Order</button>
                         </div>
                     </div>
                 </div>
             </div>
         </form>
    </div>
@endsection

@section('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container .select2-selection--single {
        height: 38px !important;
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 38px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
</style>
@endsection

@section('script-bottom')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    
    // Initialize Select2
    $('.select2').select2({ width: '100%' });

    function calculateYield() {
        // Input logic
        let inputOption = $('#input_product_id').find(':selected');
        let inputUnit = inputOption.data('unit') || 'Unit';
        let inputQty = parseFloat($('#input_qty').val()) || 0;
        
        $('#input_unit_display').text(inputUnit);
        
        if (inputOption.val() && inputQty > 0) {
            $('#input_summary').text(`Total Consumed: ${inputQty} ${inputUnit}`).show();
        } else {
            $('#input_summary').hide();
        }

        // Output logic
        let outputOption = $('#output_item').find(':selected');
        let outputUnit = outputOption.data('unit') || 'Pkg';
        let unitQty = parseFloat(outputOption.data('qty')) || 1;
        let outputQty = parseFloat($('#output_qty').val()) || 0;
        let totalOutputWeight = outputQty * unitQty;
        
        let displayLabel = outputOption.val() && outputOption.val().startsWith('variant') ? 'Packages' : outputUnit;
        $('#output_unit_display').text(displayLabel);
        
        if (outputOption.val() && outputQty > 0) {
            $('#output_summary').text(`Equivalent Base Weight: ${totalOutputWeight.toFixed(3)} ${outputUnit}`).show();
        } else {
            $('#output_summary').hide();
        }

        // Yield Calculation
        if (inputOption.val() && inputQty > 0 && outputOption.val() && outputQty > 0 && inputUnit === outputUnit) {
            $('#yield_summary_box').slideDown();
            let diff = totalOutputWeight - inputQty;
            if (diff > 0) {
                $('#yield_text').html(`<span class="text-success fw-bold"><i class="ri-arrow-up-line"></i> Gain Detected:</span> You are producing <strong class="text-dark">${diff.toFixed(3)} ${inputUnit}</strong> more than you are consuming. System will log an automatic inventory gain.`);
            } else if (diff < 0) {
                $('#yield_text').html(`<span class="text-danger fw-bold"><i class="ri-arrow-down-line"></i> Loss Detected:</span> You are producing <strong class="text-dark">${Math.abs(diff).toFixed(3)} ${inputUnit}</strong> less than you are consuming. System will log an automatic inventory loss.`);
            } else {
                $('#yield_text').html(`<span class="text-dark fw-bold"><i class="ri-check-line text-success"></i> Exact Match:</span> Input and Output weights match perfectly. No loss or gain will be recorded.`);
            }
        } else if (inputUnit !== outputUnit && inputOption.val() && outputOption.val()) {
            $('#yield_summary_box').slideDown();
            $('#yield_text').html(`<span class="text-warning fw-bold"><i class="ri-error-warning-line"></i> Unit Mismatch:</span> Cannot accurately calculate yield because base units differ (${inputUnit} vs ${outputUnit}).`);
        } else {
            $('#yield_summary_box').slideUp();
        }
    }

    $('#input_product_id, #output_item').on('change', calculateYield);
    $('#input_qty, #output_qty').on('input', calculateYield);
    
    // Initial call to set state on edit page load
    calculateYield();
    
    // Prevent double submission
    $('#repackagingForm').submit(function() {
        $('#submitBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...');
    });
});
</script>
@endsection

