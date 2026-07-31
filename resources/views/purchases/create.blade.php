@extends('layouts.vertical', ['page_title' => 'New Purchase Shipment', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('css')
<!-- Select2 CSS -->
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

@section('content')
    @php
        $landedDescriptions = [
            'Forwarder Charge',
            'Insurance Covernote',
            'Custom Duty',
            'Shipping line demurrage Charge',
            'C&F',
            'Undertable assessment purpose',
            'Undertable unstaffing purpose',
            'Transportation for Cargo carry',
            'Unloading-Loading Labour Cost in Port',
            'Maji Bill for container caretaking in Port.',
            'Delivery Expenses with Special permission & Keep down in port.',
            'Document Noting to Section out pass and other expenses',
            'Unloading Labour Cost in Store.',
            'Covered Van-2',
            'Miscellaneous'
        ];
        $selectedType = old('purchase_type', 'imported');
    @endphp

    <div class="container-fluid">
         <div class="row">
            <div class="col-12">
                <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                    <h4 class="page-title">New Purchase Shipment</h4>
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('purchases.index') }}">Purchases</a></li>
                        <li class="breadcrumb-item active">New</li>
                    </ol>
                </div>
            </div>
         </div>

         <form action="{{ route('purchases.store') }}" method="POST" id="purchaseForm">
             @csrf
             <!-- 1. Shipment Details -->
             <div class="row">
                 <div class="col-lg-12">
                     <div class="card shadow-sm border-0">
                         <div class="card-header bg-light border-bottom">
                             <h4 class="header-title mb-0">Shipment Details</h4>
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
                                 <div class="col-md-4">
                                     <div class="mb-3">
                                         <label class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                                         <div class="input-group">
                                            <span class="input-group-text"><i class="ri-calendar-event-line"></i></span>
                                            <input type="text" name="date" class="form-control flatpickr-date" value="{{ old('date', date('Y-m-d')) }}" required>
                                         </div>
                                     </div>
                                 </div>
                                 <div class="col-md-4">
                                     <div class="mb-3">
                                         <label class="form-label fw-semibold">Supplier <span class="text-danger">*</span></label>
                                         <select name="supplier_id" class="form-select select2" required>
                                             <option value="">Search Supplier...</option>
                                             @foreach($suppliers as $supplier)
                                                 <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                                             @endforeach
                                         </select>
                                     </div>
                                 </div>
                                 <div class="col-md-4">
                                     <div class="mb-3">
                                         <label class="form-label fw-semibold">Destination Warehouse <span class="text-danger">*</span></label>
                                         <select name="warehouse_id" class="form-select select2" required>
                                             <option value="">Select Warehouse...</option>
                                             @foreach($warehouses as $wh)
                                                 <option value="{{ $wh->id }}" {{ old('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                                             @endforeach
                                         </select>
                                     </div>
                                 </div>
                             </div>
                         </div>
                     </div>
                 </div>
             </div>

             <!-- 2. Products Table -->
             <div class="row mt-2">
                <div class="col-lg-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-light border-bottom d-flex justify-content-between align-items-center">
                            <h4 class="header-title mb-0">Purchase Items</h4>
                            <button type="button" class="btn btn-sm btn-success" id="addRowBtn">
                                <i class="ri-add-line"></i> Add Product Row
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered mb-0" id="itemsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Product (Raw Material) <span class="text-danger">*</span></th>
                                            <th width="15%">Quantity <span class="text-danger">*</span></th>
                                            <th width="20%">Unit Cost</th>
                                            <th width="15%" class="text-end">Subtotal</th>
                                            <th width="5%" class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    @php
                                        $oldItems = old('items');
                                    @endphp
                                    <tbody id="items-container">
                                        @if($oldItems && is_array($oldItems) && count($oldItems) > 0)
                                            @foreach($oldItems as $index => $oldItem)
                                                <tr class="item-row">
                                                    <td>
                                                        <select name="items[{{ $index }}][product_id]" class="form-select product-select" required>
                                                            <option value="">Search Product...</option>
                                                            @foreach($products as $product)
                                                                <option value="{{ $product->id }}" 
                                                                    data-unit="{{ $product->unit ? $product->unit->short_name : 'Unit' }}"
                                                                    {{ (isset($oldItem['product_id']) && $oldItem['product_id'] == $product->id) ? 'selected' : '' }}>
                                                                    {{ $product->name }} ({{ $product->unit ? $product->unit->short_name : 'Unit' }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <div class="input-group input-group-sm">
                                                            <input type="number" step="0.001" min="0.001" name="items[{{ $index }}][qty]" class="form-control item-qty" placeholder="0.00" value="{{ $oldItem['qty'] ?? '' }}" required>
                                                            <span class="input-group-text unit-addon">Unit</span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="input-group input-group-sm">
                                                            <span class="input-group-text">$</span>
                                                            <input type="number" step="any" min="0" name="items[{{ $index }}][unit_cost]" class="form-control item-cost" placeholder="0.00" value="{{ $oldItem['unit_cost'] ?? 0 }}">
                                                        </div>
                                                    </td>
                                                    <td class="text-end align-middle">
                                                        <span class="fw-bold row-subtotal">$0.00</span>
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        <button type="button" class="btn btn-sm btn-soft-danger remove-row" {{ count($oldItems) == 1 ? 'disabled' : '' }}>
                                                            <i class="ri-delete-bin-line"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr class="item-row">
                                                <td>
                                                    <select name="items[0][product_id]" class="form-select product-select" required>
                                                        <option value="">Search Product...</option>
                                                        @foreach($products as $product)
                                                            <option value="{{ $product->id }}" data-unit="{{ $product->unit ? $product->unit->short_name : 'Unit' }}">{{ $product->name }} ({{ $product->unit ? $product->unit->short_name : 'Unit' }})</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" step="0.001" min="0.001" name="items[0][qty]" class="form-control item-qty" placeholder="0.00" required>
                                                        <span class="input-group-text unit-addon">Unit</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text">$</span>
                                                        <input type="number" step="any" min="0" name="items[0][unit_cost]" class="form-control item-cost" placeholder="0.00" value="0">
                                                    </div>
                                                </td>
                                                <td class="text-end align-middle">
                                                    <span class="fw-bold row-subtotal">$0.00</span>
                                                </td>
                                                <td class="text-center align-middle">
                                                    <button type="button" class="btn btn-sm btn-soft-danger remove-row" disabled>
                                                        <i class="ri-delete-bin-line"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-light">
                                            <td colspan="3" class="text-end fw-bold">Grand Total:</td>
                                            <td class="text-end fw-bold text-success fs-4" id="grandTotal">$0.00</td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
             </div>

             <!-- 3. Purchase Type & Additional Expenses Section -->
             <div class="row mt-2">
                 <div class="col-lg-12">
                     <div class="card shadow-sm border-0">
                         <div class="card-header bg-light border-bottom d-flex justify-content-between align-items-center">
                             <h4 class="header-title mb-0">Purchase Type & Additional Expenses</h4>
                             <div>
                                 <label class="form-label fw-semibold me-2 mb-0">Select Purchase Type:</label>
                                 <div class="form-check form-check-inline mb-0">
                                     <input class="form-check-input purchase-type-radio" type="radio" name="purchase_type" id="purchase_type_imported" value="imported" {{ $selectedType == 'imported' ? 'checked' : '' }}>
                                     <label class="form-check-label fw-semibold text-primary" for="purchase_type_imported">
                                         <i class="ri-global-line me-1"></i> Imported
                                     </label>
                                 </div>
                                 <div class="form-check form-check-inline mb-0">
                                     <input class="form-check-input purchase-type-radio" type="radio" name="purchase_type" id="purchase_type_local" value="local" {{ $selectedType == 'local' ? 'checked' : '' }}>
                                     <label class="form-check-label fw-semibold text-success" for="purchase_type_local">
                                         <i class="ri-map-pin-user-line me-1"></i> Local
                                     </label>
                                 </div>
                             </div>
                         </div>
                         
                         <!-- Local Expenses Container -->
                         <div id="local-cost-container" class="card-body" style="{{ $selectedType == 'local' ? '' : 'display: none;' }}">
                             <div class="row">
                                 <div class="col-md-4">
                                     <label class="form-label fw-semibold">Delivery Cost ($ / ৳)</label>
                                     <div class="input-group">
                                         <span class="input-group-text">$</span>
                                         <input type="number" step="any" min="0" name="delivery_cost" id="delivery_cost" class="form-control" placeholder="0.00" value="{{ old('delivery_cost', '0') }}">
                                     </div>
                                 </div>
                             </div>
                         </div>

                         <!-- Imported Landed Cost Breakdown Container -->
                         <div id="imported-cost-container" class="card-body p-0" style="{{ $selectedType == 'imported' ? '' : 'display: none;' }}">
                             <div class="table-responsive">
                                 <table class="table table-bordered table-hover mb-0 align-middle">
                                     <thead class="table-light">
                                         <tr>
                                             <th style="width: 60px;" class="text-center">S/L</th>
                                             <th>Description</th>
                                             <th style="width: 250px;" class="text-end">Amount (৳)</th>
                                         </tr>
                                     </thead>
                                     <tbody>
                                         @foreach($landedDescriptions as $idx => $desc)
                                             @php
                                                 $oldRow = old('cost_breakdown.'.$idx);
                                                 $val = $oldRow['amount'] ?? ($oldRow['bd_cost'] ?? '');
                                             @endphp
                                             <tr class="landed-cost-row">
                                                 <td class="text-center fw-bold text-muted">{{ $idx + 1 }}</td>
                                                 <td>
                                                     <input type="hidden" name="cost_breakdown[{{ $idx }}][description]" value="{{ $desc }}">
                                                     <span class="fw-semibold text-dark">{{ $desc }}</span>
                                                 </td>
                                                 <td>
                                                     <div class="input-group input-group-sm">
                                                         <span class="input-group-text">৳</span>
                                                         <input type="number" step="any" min="0" name="cost_breakdown[{{ $idx }}][amount]" class="form-control text-end cost-bd fw-bold" placeholder="0.00" value="{{ $val }}">
                                                     </div>
                                                 </td>
                                             </tr>
                                         @endforeach
                                     </tbody>
                                     <tfoot>
                                         <tr class="table-light">
                                             <td colspan="2" class="text-end fw-bold">Total Landed Cost:</td>
                                             <td class="text-end fw-bold text-success fs-5" id="totalLandedCostBd">৳0.00</td>
                                         </tr>
                                     </tfoot>
                                 </table>
                             </div>
                         </div>
                         
                         <div class="card-footer bg-white border-top text-end py-3">
                             <a href="{{ route('purchases.index') }}" class="btn btn-light me-2"><i class="ri-close-line"></i> Cancel</a>
                             <button type="submit" class="btn btn-primary" id="submitBtn"><i class="ri-save-line"></i> Confirm & Save Purchase</button>
                         </div>
                     </div>
                 </div>
             </div>
          </form>
    </div>
@endsection

@section('script-bottom')
<!-- jQuery & Select2 JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    let rowIdx = {{ ($oldItems && is_array($oldItems)) ? count($oldItems) : 1 }};

    // Clean product options template
    let productOptionsHtml = `<option value="">Search Product...</option>@foreach($products as $product)<option value="{{ $product->id }}" data-unit="{{ $product->unit ? $product->unit->short_name : 'Unit' }}">{{ addslashes($product->name) }} ({{ $product->unit ? $product->unit->short_name : 'Unit' }})</option>@endforeach`;

    // Initialize Select2 on existing elements
    $('.select2').select2({ width: '100%' });
    $('.product-select').select2({ width: '100%' });

    // Toggle Purchase Type Containers
    $(document).on('change', '.purchase-type-radio', function() {
        let type = $(this).val();
        if (type === 'local') {
            $('#local-cost-container').slideDown(200);
            $('#imported-cost-container').slideUp(200);
        } else {
            $('#local-cost-container').slideUp(200);
            $('#imported-cost-container').slideDown(200);
        }
    });

    $(document).on('input', '.cost-bd', function() {
        calculateLandedTotals();
    });

    function calculateLandedTotals() {
        let totalLanded = 0;
        $('.cost-bd').each(function() {
            let val = parseFloat($(this).val()) || 0;
            totalLanded += val;
        });
        $('#totalLandedCostBd').text('৳' + totalLanded.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    }
    calculateLandedTotals();

    // Update Unit addon when product is selected
    $(document).on('change', '.product-select', function() {
        let unit = $(this).find(':selected').data('unit') || 'Unit';
        $(this).closest('tr').find('.unit-addon').text(unit);
    });

    // Update initial unit addons for existing pre-selected options
    $('.product-select').each(function() {
        let unit = $(this).find(':selected').data('unit') || 'Unit';
        $(this).closest('tr').find('.unit-addon').text(unit);
    });

    // Calculate subtotal and grand total
    function calculateTotals() {
        let grandTotal = 0;
        $('.item-row').each(function() {
            let qty = parseFloat($(this).find('.item-qty').val()) || 0;
            let cost = parseFloat($(this).find('.item-cost').val()) || 0;
            let subtotal = qty * cost;
            $(this).find('.row-subtotal').text('$' + subtotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            grandTotal += subtotal;
        });
        $('#grandTotal').text('$' + grandTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

        // Disable remove button if only one row left
        let rowCount = $('.item-row').length;
        if(rowCount === 1) {
            $('.remove-row').prop('disabled', true);
        } else {
            $('.remove-row').prop('disabled', false);
        }
    }

    // Initial calculation
    calculateTotals();

    // Bind events for calculation
    $(document).on('input', '.item-qty, .item-cost', function() {
        calculateTotals();
    });

    // Add new row
    $('#addRowBtn').click(function() {
        let newRow = `
        <tr class="item-row">
            <td>
                <select name="items[${rowIdx}][product_id]" class="form-select product-select" required>
                    ${productOptionsHtml}
                </select>
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <input type="number" step="0.001" min="0.001" name="items[${rowIdx}][qty]" class="form-control item-qty" placeholder="0.00" required>
                    <span class="input-group-text unit-addon">Unit</span>
                </div>
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">$</span>
                    <input type="number" step="any" min="0" name="items[${rowIdx}][unit_cost]" class="form-control item-cost" placeholder="0.00" value="0">
                </div>
            </td>
            <td class="text-end align-middle">
                <span class="fw-bold row-subtotal">$0.00</span>
            </td>
            <td class="text-center align-middle">
                <button type="button" class="btn btn-sm btn-soft-danger remove-row">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </td>
        </tr>`;

        $('#items-container').append(newRow);
        
        // Re-initialize select2 for the new row
        let newSelect = $('#items-container').find('.product-select').last();
        newSelect.select2({ width: '100%' });
        newSelect.val('').trigger('change');
        
        rowIdx++;
        calculateTotals();
    });

    // Remove row
    $(document).on('click', '.remove-row', function() {
        if ($('.item-row').length > 1) {
            let select = $(this).closest('tr').find('.product-select');
            if (select.hasClass('select2-hidden-accessible')) {
                select.select2('destroy');
            }
            
            $(this).closest('tr').remove();
            calculateTotals();
        }
    });
    
    // Prevent double submission
    $('#purchaseForm').submit(function() {
        $('#submitBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');
    });
});
</script>
@endsection
