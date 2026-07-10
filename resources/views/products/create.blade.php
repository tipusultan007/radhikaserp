@extends('layouts.vertical', ['page_title' => 'Create Product', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('css')
@endsection

@section('content')
    <div class="container-fluid">
         <div class="row">
            <div class="col-12">
                <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                    <h4 class="page-title">Add Master Product</h4>
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Master Products</a></li>
                        <li class="breadcrumb-item active">Add</li>
                    </ol>
                </div>
            </div>
         </div>

         <div class="row">
             <div class="col-lg-8">
                 <div class="card">
                     <div class="card-body">
                         <h4 class="header-title mb-3">Product Specifications</h4>

                         @if ($errors->any())
                             <div class="alert alert-danger">
                                 <ul class="mb-0">
                                     @foreach ($errors->all() as $error)
                                         <li>{{ $error }}</li>
                                     @endforeach
                                 </ul>
                             </div>
                         @endif

                         <form action="{{ route('products.store') }}" method="POST">
                             @csrf

                             <div class="mb-3">
                                 <label for="name" class="form-label">Product Name <span class="text-danger">*</span></label>
                                 <input type="text" id="name" name="name" class="form-control" placeholder="e.g. Soya Bean" value="{{ old('name') }}" required>
                             </div>


                             <div class="mb-3">
                                 <label for="type" class="form-label">Product Type <span class="text-danger">*</span></label>
                                 <select id="type" name="type" class="form-control" required>
                                     <option value="raw" {{ old('type') === 'raw' ? 'selected' : '' }}>Raw Stock</option>
                                     <option value="finished" {{ old('type') === 'finished' ? 'selected' : '' }}>Finished Goods</option>
                                 </select>
                             </div>

                             <div class="mb-3">
                                 <label for="unit_id" class="form-label">Base Unit <span class="text-danger">*</span></label>
                                 <select id="unit_id" name="unit_id" class="form-control" required>
                                     <option value="">Select Unit</option>
                                     @foreach($units as $unit)
                                         <option value="{{ $unit->id }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>
                                             {{ $unit->name }} ({{ $unit->short_name }})
                                         </option>
                                     @endforeach
                                 </select>
                             </div>

                             <div class="mb-3">
                                 <div class="form-check form-switch">
                                     <input type="checkbox" class="form-check-input"  name="status" value="1" checked>
                                     <label class="form-check-label" for="status">Active Status</label>
                                 </div>
                             </div>
                             
                             <hr class="my-4">
                             <h4 class="header-title mb-3">Product Variants</h4>
                             <p class="text-muted font-14">Define at least one variant for this product (e.g. 1kg packet, 5kg bulk). The SKU will be auto-generated.</p>
                             
                             <div class="table-responsive">
                                 <table class="table table-bordered" id="variants-table">
                                     <thead class="table-light">
                                         <tr>
                                             <th>Variant Name <span class="text-danger">*</span></th>
                                             <th>Unit <span class="text-danger">*</span></th>
                                             <th>Unit Qty <span class="text-danger">*</span></th>
                                             <th>Selling Price</th>
                                             <th>Action</th>
                                         </tr>
                                     </thead>
                                     <tbody id="variants-body">
                                         <!-- Initial Row -->
                                         <tr class="variant-row">
                                             <td>
                                                 <input type="text" name="variants[0][name]" class="form-control" placeholder="e.g. Default or 1kg" required>
                                             </td>
                                             <td>
                                                 <select name="variants[0][unit_id]" class="form-select" required>
                                                     <option value="">Select Unit</option>
                                                     @foreach($units as $unit)
                                                         <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->short_name }})</option>
                                                     @endforeach
                                                 </select>
                                             </td>
                                             <td>
                                                 <input type="number" step="any" name="variants[0][unit_qty]" class="form-control" value="1.00" required>
                                             </td>
                                             <td>
                                                 <input type="number" step="any" name="variants[0][price]" class="form-control" value="0.00">
                                             </td>
                                             <td>
                                                 <button type="button" class="btn btn-danger btn-sm remove-variant" disabled><i class="ri-delete-bin-line"></i></button>
                                             </td>
                                         </tr>
                                     </tbody>
                                 </table>
                                 <button type="button" class="btn btn-soft-primary btn-sm mt-2" id="add-variant-btn"><i class="ri-add-line me-1"></i> Add Another Variant</button>
                             </div>

                             <div class="mt-4">
                                 <button type="submit" class="btn btn-primary me-1">Save Product</button>
                                 <a href="{{ route('products.index') }}" class="btn btn-light">Cancel</a>
                             </div>
                         </form>
                     </div>
                 </div>
             </div>
         </div>
    </div>
@endsection

@section('script')
<script>
    $(document).ready(function() {
        let variantIndex = 1;
        
        // Disable remove button if only 1 row
        function updateRemoveButtons() {
            const rows = $('.variant-row');
            if (rows.length === 1) {
                rows.find('.remove-variant').prop('disabled', true);
            } else {
                rows.find('.remove-variant').prop('disabled', false);
            }
        }
        
        $('#add-variant-btn').click(function() {
            const rowHtml = `
                <tr class="variant-row">
                    <td>
                        <input type="text" name="variants[${variantIndex}][name]" class="form-control" placeholder="e.g. 5kg Bulk" required>
                    </td>
                    <td>
                        <select name="variants[${variantIndex}][unit_id]" class="form-select" required>
                            <option value="">Select Unit</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->short_name }})</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="number" step="any" name="variants[${variantIndex}][unit_qty]" class="form-control" value="1.00" required>
                    </td>
                    <td>
                        <input type="number" step="any" name="variants[${variantIndex}][price]" class="form-control" value="0.00">
                    </td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm remove-variant"><i class="ri-delete-bin-line"></i></button>
                    </td>
                </tr>
            `;
            $('#variants-body').append(rowHtml);
            variantIndex++;
            updateRemoveButtons();
        });
        
        $(document).on('click', '.remove-variant', function() {
            if ($('.variant-row').length > 1) {
                $(this).closest('tr').remove();
                updateRemoveButtons();
            }
        });
    });
</script>
@endsection
