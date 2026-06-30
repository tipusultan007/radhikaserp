@extends('layouts.vertical', ['page_title' => 'Edit Supplier', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('css')
@endsection

@section('content')
    <div class="container-fluid">
         <div class="row">
            <div class="col-12">
                <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                    <h4 class="page-title">Edit Supplier</h4>
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('suppliers.index') }}">Suppliers</a></li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </div>
            </div>
         </div>

         <div class="row">
             <div class="col-lg-8">
                 <div class="card">
                     <div class="card-body">
                         <h4 class="header-title mb-3">Update Supplier Information</h4>

                         @if ($errors->any())
                             <div class="alert alert-danger">
                                 <ul class="mb-0">
                                     @foreach ($errors->all() as $error)
                                         <li>{{ $error }}</li>
                                     @endforeach
                                 </ul>
                             </div>
                         @endif

                         <form action="{{ route('suppliers.update', $supplier->id) }}" method="POST">
                             @csrf
                             @method('PUT')

                             <div class="mb-3">
                                 <label for="name" class="form-label">Supplier Name <span class="text-danger">*</span></label>
                                 <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $supplier->name) }}" required>
                             </div>

                             <div class="mb-3">
                                 <label for="phone" class="form-label">Phone <span class="text-danger">*</span></label>
                                 <input type="text" id="phone" name="phone" class="form-control" value="{{ old('phone', $supplier->phone) }}" required>
                             </div>

                             <div class="mb-3">
                                 <label for="address" class="form-label">Address</label>
                                 <textarea id="address" name="address" class="form-control" rows="3">{{ old('address', $supplier->address) }}</textarea>
                             </div>

                             <div class="mb-3">
                                 <label for="country" class="form-label">Country</label>
                                 <input type="text" id="country" name="country" class="form-control" value="{{ old('country', $supplier->country) }}">
                             </div>

                             <div class="mb-3">
                                 <label for="total_payable" class="form-label">Total Payable (in TK)</label>
                                 <input type="number" step="1" id="total_payable" name="total_payable" class="form-control" value="{{ old('total_payable', $supplier->total_payable) }}">
                             </div>

                             <div class="mt-4">
                                 <button type="submit" class="btn btn-primary me-1">Update Supplier</button>
                                 <a href="{{ route('suppliers.index') }}" class="btn btn-light">Cancel</a>
                             </div>
                         </form>
                     </div>
                 </div>
             </div>
         </div>
    </div>
@endsection

