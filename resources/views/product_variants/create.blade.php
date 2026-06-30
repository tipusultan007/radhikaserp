@extends('layouts.vertical', ['page_title' => 'Create Product Variant', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('css')
@endsection

@section('content')
    <div class="container-fluid">
         <div class="row">
            <div class="col-12">
                <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                    <h4 class="page-title">Add Product Variant</h4>
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('product-variants.index') }}">Product Variants</a></li>
                        <li class="breadcrumb-item active">Add</li>
                    </ol>
                </div>
            </div>
         </div>

         <div class="row">
             <div class="col-lg-8">
                 <div class="card">
                     <div class="card-body">
                         <h4 class="header-title mb-3">Variant Specifications</h4>

                         @if ($errors->any())
                             <div class="alert alert-danger">
                                 <ul class="mb-0">
                                     @foreach ($errors->all() as $error)
                                         <li>{{ $error }}</li>
                                     @endforeach
                                 </ul>
                             </div>
                         @endif

                         <form action="{{ route('product-variants.store') }}" method="POST">
                             @csrf

                             <div class="mb-3">
                                 <label for="product_id" class="form-label">Master Product <span class="text-danger">*</span></label>
                                 <select id="product_id" name="product_id" class="form-control select2" data-toggle="select2" required>
                                     <option value="">Select Master Product</option>
                                     @foreach ($products as $product)
                                         <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                             {{ $product->name }} ({{ $product->sku }}) - {{ ucfirst($product->type) }}
                                         </option>
                                     @endforeach
                                 </select>
                             </div>

                             <div class="mb-3">
                                 <label for="name" class="form-label">Variant Name <span class="text-danger">*</span></label>
                                 <input type="text" id="name" name="name" class="form-control" placeholder="e.g. 1kg, 500g, Pack of 10" value="{{ old('name') }}" required>
                             </div>

                             <div class="mb-3">
                                 <label for="sku" class="form-label">Variant SKU <span class="text-danger">*</span></label>
                                 <input type="text" id="sku" name="sku" class="form-control" placeholder="e.g. SOYA-1KG" value="{{ old('sku') }}" required>
                             </div>

                             <div class="mb-3">
                                 <label for="barcode" class="form-label">Barcode</label>
                                 <input type="text" id="barcode" name="barcode" class="form-control" placeholder="e.g. 880123456789" value="{{ old('barcode') }}">
                             </div>

                             <div class="row">
                                 <div class="col-md-6">
                                     <div class="mb-3">
                                         <label for="unit_qty" class="form-label">Unit Quantity (Multiplier) <span class="text-danger">*</span></label>
                                         <input type="number" step="1" id="unit_qty" name="unit_qty" class="form-control" placeholder="e.g. 1.00 or 0.50" value="{{ old('unit_qty', '1.00') }}" required>
                                     </div>
                                 </div>
                                 <div class="col-md-6">
                                     <div class="mb-3">
                                         <label for="unit_type" class="form-label">Unit Type <span class="text-danger">*</span></label>
                                         <select id="unit_type" name="unit_type" class="form-select" required>
                                             @php $selectedUnit = old('unit_type', 'kg'); @endphp
                                             <option value="kg" {{ $selectedUnit == 'kg' ? 'selected' : '' }}>Kilograms (kg)</option>
                                             <option value="g" {{ $selectedUnit == 'g' ? 'selected' : '' }}>Grams (g)</option>
                                             <option value="pcs" {{ $selectedUnit == 'pcs' ? 'selected' : '' }}>Pieces (pcs)</option>
                                             <option value="liter" {{ $selectedUnit == 'liter' ? 'selected' : '' }}>Liters (L)</option>
                                             <option value="ml" {{ $selectedUnit == 'ml' ? 'selected' : '' }}>Milliliters (ml)</option>
                                             <option value="box" {{ $selectedUnit == 'box' ? 'selected' : '' }}>Box</option>
                                             <option value="packet" {{ $selectedUnit == 'packet' ? 'selected' : '' }}>Packet</option>
                                         </select>
                                     </div>
                                 </div>
                             </div>

                             <div class="mb-3">
                                 <label for="price" class="form-label">Selling Price ($)</label>
                                 <input type="number" step="1" id="price" name="price" class="form-control" placeholder="e.g. 25.00" value="{{ old('price', '0.00') }}">
                             </div>

                             <div class="mb-3">
                                 <div class="form-check form-switch">
                                     <input type="checkbox" class="form-check-input"  name="status" value="1" checked>
                                     <label class="form-check-label" for="status">Active Status</label>
                                 </div>
                             </div>

                             <div class="mt-4">
                                 <button type="submit" class="btn btn-primary me-1">Save Variant</button>
                                 <a href="{{ route('product-variants.index') }}" class="btn btn-light">Cancel</a>
                             </div>
                         </form>
                     </div>
                 </div>
             </div>
         </div>
    </div>
@endsection

