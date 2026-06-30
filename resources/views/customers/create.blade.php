@extends('layouts.vertical', ['page_title' => 'Create Customer', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('css')
@endsection

@section('content')
    <div class="container-fluid">
         <div class="row">
            <div class="col-12">
                <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                    <h4 class="page-title">Add Customer</h4>
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Customers</a></li>
                        <li class="breadcrumb-item active">Add</li>
                    </ol>
                </div>
            </div>
         </div>

         <div class="row">
             <div class="col-lg-8">
                 <div class="card">
                     <div class="card-body">
                         <h4 class="header-title mb-3">Customer Information</h4>

                         @if ($errors->any())
                             <div class="alert alert-danger">
                                 <ul class="mb-0">
                                     @foreach ($errors->all() as $error)
                                         <li>{{ $error }}</li>
                                     @endforeach
                                 </ul>
                             </div>
                         @endif

                         <form action="{{ route('customers.store') }}" method="POST">
                             @csrf

                             <div class="row">
                                 <div class="col-md-6 mb-3">
                                     <label for="name" class="form-label">Customer Name <span class="text-danger">*</span></label>
                                     <input type="text" id="name" name="name" class="form-control" placeholder="e.g. John Doe" value="{{ old('name') }}" required>
                                 </div>

                                 <div class="col-md-6 mb-3">
                                     <label for="phone" class="form-label">Phone <span class="text-danger">*</span></label>
                                     <input type="text" id="phone" name="phone" class="form-control" placeholder="e.g. +8801700000000" value="{{ old('phone') }}" required>
                                 </div>
                             </div>

                             <div class="mb-3">
                                 <label for="email" class="form-label">Email</label>
                                 <input type="email" id="email" name="email" class="form-control" placeholder="e.g. john@example.com" value="{{ old('email') }}">
                             </div>

                             <div class="mb-3">
                                 <label for="address" class="form-label">Address</label>
                                 <textarea id="address" name="address" class="form-control" rows="3" placeholder="Enter customer address">{{ old('address') }}</textarea>
                             </div>

                             <div class="row">
                                 <div class="col-md-6 mb-3">
                                     <label for="credit_limit" class="form-label">Credit Limit (in TK)</label>
                                     <input type="number" step="1" id="credit_limit" name="credit_limit" class="form-control" placeholder="50000.00" value="{{ old('credit_limit', '0.00') }}">
                                 </div>
                                 <div class="col-md-6 mb-3">
                                     <label for="opening_balance" class="form-label">Opening Balance (in TK)</label>
                                     <input type="number" step="1" id="opening_balance" name="opening_balance" class="form-control" placeholder="0.00" value="{{ old('opening_balance', '0.00') }}">
                                 </div>
                             </div>

                             <hr class="my-4">
                             
                             <h5 class="mb-3 text-uppercase bg-light p-2"><i class="ri-lock-password-line me-1"></i> Authentication</h5>

                             <div class="mb-3">
                                 <label for="password" class="form-label">Password</label>
                                 <input type="password" id="password" name="password" class="form-control" placeholder="Enter password (optional)">
                                 <small class="text-muted">Enter a password to allow the customer to log into the customer app.</small>
                             </div>

                             <div class="mt-4">
                                 <button type="submit" class="btn btn-primary me-1">Save Customer</button>
                                 <a href="{{ route('customers.index') }}" class="btn btn-light">Cancel</a>
                             </div>
                         </form>
                     </div>
                 </div>
             </div>
         </div>
    </div>
@endsection

