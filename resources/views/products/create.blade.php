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
                                 <label for="sku" class="form-label">SKU <span class="text-danger">*</span></label>
                                 <input type="text" id="sku" name="sku" class="form-control" placeholder="e.g. PROD-SOYA" value="{{ old('sku') }}" required>
                             </div>

                             <div class="mb-3">
                                 <label for="type" class="form-label">Product Type <span class="text-danger">*</span></label>
                                 <select id="type" name="type" class="form-control" required>
                                     <option value="raw" {{ old('type') === 'raw' ? 'selected' : '' }}>Raw Stock</option>
                                     <option value="finished" {{ old('type') === 'finished' ? 'selected' : '' }}>Finished Goods</option>
                                 </select>
                             </div>

                             <div class="mb-3">
                                 <label for="base_unit" class="form-label">Base Unit <span class="text-danger">*</span></label>
                                 <input type="text" id="base_unit" name="base_unit" class="form-control" placeholder="e.g. kg, pcs, bag" value="{{ old('base_unit', 'kg') }}" required>
                             </div>

                             <div class="mb-3">
                                 <div class="form-check form-switch">
                                     <input type="checkbox" class="form-check-input"  name="status" value="1" checked>
                                     <label class="form-check-label" for="status">Active Status</label>
                                 </div>
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
