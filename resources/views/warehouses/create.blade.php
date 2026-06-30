@extends('layouts.vertical', ['page_title' => 'Create Warehouse', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('css')
@endsection

@section('content')
    <!-- Start Content-->
    <div class="container-fluid">

         <!-- start page title -->
         <div class="row">
            <div class="col-12">
                <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                    <h4 class="page-title">Add Warehouse</h4>
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('warehouses.index') }}">Warehouses</a></li>
                        <li class="breadcrumb-item active">Add</li>
                    </ol>
                </div>
            </div>
         </div>
         <!-- end page title -->

         <div class="row">
             <div class="col-lg-8">
                 <div class="card">
                     <div class="card-body">
                         <h4 class="header-title mb-3">Warehouse Information</h4>

                         @if ($errors->any())
                             <div class="alert alert-danger">
                                 <ul class="mb-0">
                                     @foreach ($errors->all() as $error)
                                         <li>{{ $error }}</li>
                                     @endforeach
                                 </ul>
                             </div>
                         @endif

                         <form action="{{ route('warehouses.store') }}" method="POST">
                             @csrf

                             <div class="mb-3">
                                 <label for="name" class="form-label">Warehouse Name <span class="text-danger">*</span></label>
                                 <input type="text" id="name" name="name" class="form-control" placeholder="e.g. Main Warehouse" value="{{ old('name') }}" required>
                             </div>

                             <div class="mb-3">
                                 <label for="code" class="form-label">Warehouse Code <span class="text-danger">*</span></label>
                                 <input type="text" id="code" name="code" class="form-control" placeholder="e.g. WH-001" value="{{ old('code') }}" required>
                             </div>

                             <div class="mb-3">
                                 <label for="address" class="form-label">Address</label>
                                 <textarea id="address" name="address" class="form-control" rows="3" placeholder="Enter warehouse location address">{{ old('address') }}</textarea>
                             </div>

                             <div class="mb-3">
                                 <label for="manager_id" class="form-label">Manager</label>
                                 <select id="manager_id" name="manager_id" class="form-control select2" data-toggle="select2">
                                     <option value="">Select Manager</option>
                                     @foreach ($users as $user)
                                         <option value="{{ $user->id }}" {{ old('manager_id') == $user->id ? 'selected' : '' }}>
                                             {{ $user->name }} ({{ $user->email }})
                                         </option>
                                     @endforeach
                                 </select>
                             </div>

                             <div class="mb-3">
                                 <div class="form-check form-switch">
                                     <input type="checkbox" class="form-check-input" name="status" value="1" checked>
                                     <label class="form-check-label" for="status">Active Status</label>
                                 </div>
                             </div>

                             <div class="mt-4">
                                 <button type="submit" class="btn btn-primary me-1">Save Warehouse</button>
                                 <a href="{{ route('warehouses.index') }}" class="btn btn-light">Cancel</a>
                             </div>
                         </form>
                     </div>
                 </div>
             </div>
         </div>

    </div> <!-- container -->
@endsection

@section('script')
@endsection
