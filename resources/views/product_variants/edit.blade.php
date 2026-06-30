@extends('layouts.vertical', ['page_title' => 'Edit Product Variant', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('css')
@endsection

@section('content')
    <div class="container-fluid">
         <div class="row">
            <div class="col-12">
                <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                    <h4 class="page-title">Edit Product Variant</h4>
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('product-variants.index') }}">Product Variants</a></li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </div>
            </div>
         </div>

         <div class="row">
             <div class="col-lg-8">
                 <div class="card">
                     <div class="card-body">
                         <h4 class="header-title mb-3">Update Variant Specifications</h4>

                         @if ($errors->any())
                             <div class="alert alert-danger">
                                 <ul class="mb-0">
                                     @foreach ($errors->all() as $error)
                                         <li>{{ $error }}</li>
                                     @endforeach
                                 </ul>
                             </div>
                         @endif

                         <form action="{{ route('product-variants.update', $productVariant->id) }}" method="POST">
                             @csrf
                             @method('PUT')

                             <div class="mb-3">
                                 <label for="product_id" class="form-label">Master Product <span class="text-danger">*</span></label>
                                 <select id="product_id" name="product_id" class="form-control select2" data-toggle="select2" required>
                                     <option value="">Select Master Product</option>
                                     @foreach ($products as $product)
                                         <option value="{{ $product->id }}" {{ old('product_id', $productVariant->product_id) == $product->id ? 'selected' : '' }}>
                                             {{ $product->name }} ({{ $product->sku }}) - {{ ucfirst($product->type) }}
                                         </option>
                                     @endforeach
                                 </select>
                             </div>

                             <div class="mb-3">
                                 <label for="name" class="form-label">Variant Name <span class="text-danger">*</span></label>
                                 <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $productVariant->name) }}" required>
                             </div>

                             <div class="mb-3">
                                 <label for="sku" class="form-label">Variant SKU <span class="text-danger">*</span></label>
                                 <input type="text" id="sku" name="sku" class="form-control" value="{{ old('sku', $productVariant->sku) }}" required>
                             </div>

                             <div class="mb-3">
                                 <label for="barcode" class="form-label">Barcode</label>
                                 <input type="text" id="barcode" name="barcode" class="form-control" value="{{ old('barcode', $productVariant->barcode) }}">
                             </div>

                             <div class="row">
                                 <div class="col-md-6">
                                     <div class="mb-3">
                                         <label for="unit_qty" class="form-label">Unit Quantity (Multiplier) <span class="text-danger">*</span></label>
                                         <input type="number" step="1" id="unit_qty" name="unit_qty" class="form-control" value="{{ old('unit_qty', $productVariant->unit_qty) }}" required>
                                     </div>
                                 </div>
                                 <div class="col-md-6">
                                     <div class="mb-3">
                                         <label for="unit_type" class="form-label">Unit Type <span class="text-danger">*</span></label>
                                         <select id="unit_type" name="unit_type" class="form-select" required>
                                             @php $selectedUnit = old('unit_type', $productVariant->unit_type); @endphp
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
                                 <input type="number" step="1" id="price" name="price" class="form-control" placeholder="e.g. 25.00" value="{{ old('price', $productVariant->price) }}">
                             </div>

                             <div class="mb-3">
                                 <div class="form-check form-switch">
                                     <input type="checkbox" class="form-check-input"  name="status" value="1" {{ old('status', $productVariant->status) ? 'checked' : '' }}>
                                     <label class="form-check-label" for="status">Active Status</label>
                                 </div>
                             </div>

                             <div class="mt-4">
                                 <button type="submit" class="btn btn-primary me-1">Update Variant</button>
                                 <a href="{{ route('product-variants.index') }}" class="btn btn-light">Cancel</a>
                             </div>
                         </form>
                     </div>
                 </div>
             </div>
         </div>
    </div>
@endsection

