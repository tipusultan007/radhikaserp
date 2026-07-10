@extends('layouts.vertical', ['page_title' => 'New Import Shipment', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

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
    <div class="container-fluid">
         <div class="row">
            <div class="col-12">
                <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                    <h4 class="page-title">New Import Shipment</h4>
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('imports.index') }}">Imports</a></li>
                        <li class="breadcrumb-item active">New</li>
                    </ol>
                </div>
            </div>
         </div>

         <form action="{{ route('imports.store') }}" method="POST" id="importForm">
             @csrf
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
                                                 <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
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
                                                 <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                                             @endforeach
                                         </select>
                                     </div>
                                 </div>
                             </div>
                         </div>
                     </div>
                 </div>
             </div>

             <div class="row mt-2">
                <div class="col-lg-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-light border-bottom d-flex justify-content-between align-items-center">
                            <h4 class="header-title mb-0">Import Items</h4>
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
                                            <th width="20%">Unit Cost <span class="text-danger">*</span></th>
                                            <th width="15%" class="text-end">Subtotal</th>
                                            <th width="5%" class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="items-container">
                                        <tr class="item-row">
                                            <td>
                                                <select name="items[0][product_variant_id]" class="form-select product-select" required>
                                                    <option value="">Search Variant...</option>
                                                    @foreach($variants as $variant)
                                                        <option value="{{ $variant->id }}" data-unit="{{ $variant->unit ? $variant->unit->short_name : 'Unit' }}">{{ $variant->product->name }} - {{ $variant->name }}</option>
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
                                                    <input type="number" step="any" min="0.01" name="items[0][unit_cost]" class="form-control item-cost" placeholder="0.00" required>
                                                </div>
                                            </td>
                                            <td class="text-end align-middle">
                                                <span class="fw-bold row-subtotal">$0.00</span>
                                            </td>
                                            <td class="text-center align-middle">
                                                <button type="button" class="btn btn-sm btn-danger remove-row" disabled>
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </td>
                                        </tr>
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
                        <div class="card-footer bg-white border-top text-end py-3">
                            <a href="{{ route('imports.index') }}" class="btn btn-light me-2"><i class="ri-close-line"></i> Cancel</a>
                            <button type="submit" class="btn btn-primary" id="submitBtn"><i class="ri-save-line"></i> Confirm & Save Import</button>
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
    let rowIdx = 1;

    // Initialize Select2 on existing elements
    $('.select2').select2({ width: '100%' });
    $('.product-select').select2({ width: '100%' });

    // Update Unit addon when product is selected
    $(document).on('change', '.product-select', function() {
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

    // Bind events for calculation
    $(document).on('input', '.item-qty, .item-cost', function() {
        calculateTotals();
    });

    // Add new row
    $('#addRowBtn').click(function() {
        // Destroy select2 on original before cloning to avoid issues, or clone cleanly
        let options = $('.product-select:first').html(); // just copy options
        
        let newRow = `
        <tr class="item-row">
            <td>
                <select name="items[${rowIdx}][product_variant_id]" class="form-select product-select" required>
                    ${options}
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
                    <input type="number" step="any" min="0.01" name="items[${rowIdx}][unit_cost]" class="form-control item-cost" placeholder="0.00" required>
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
        $('#items-container').find('.product-select').last().select2({ width: '100%' });
        
        // Clear selection in the new row
        $('#items-container').find('.product-select').last().val('').trigger('change');
        
        rowIdx++;
        calculateTotals();
    });

    // Remove row
    $(document).on('click', '.remove-row', function() {
        if ($('.item-row').length > 1) {
            // Destroy select2 before removing to prevent memory leaks
            let select = $(this).closest('tr').find('.product-select');
            if (select.hasClass('select2-hidden-accessible')) {
                select.select2('destroy');
            }
            
            $(this).closest('tr').remove();
            calculateTotals();
        }
    });
    
    // Prevent double submission
    $('#importForm').submit(function() {
        $('#submitBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');
    });
});
</script>
@endsection


