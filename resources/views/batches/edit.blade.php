@extends('layouts.vertical', ['page_title' => 'Edit Batch', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('content')
    <div class="container-fluid">
         <div class="row">
            <div class="col-12">
                <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                    <h4 class="page-title">Edit Batch</h4>
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('batches.index') }}">Batches</a></li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </div>
            </div>
         </div>

         <div class="row">
             <div class="col-lg-8">
                 <div class="card">
                     <div class="card-body">
                         <h4 class="header-title mb-3">Update Batch Information</h4>

                         @if ($errors->any())
                             <div class="alert alert-danger">
                                 <ul class="mb-0">
                                     @foreach ($errors->all() as $error)
                                         <li>{{ $error }}</li>
                                     @endforeach
                                 </ul>
                             </div>
                         @endif
                         @if (session('error'))
                             <div class="alert alert-danger">
                                 {{ session('error') }}
                             </div>
                         @endif

                         <form action="{{ route('batches.update', $batch->id) }}" method="POST">
                             @csrf
                             @method('PUT')

                             <div class="row">
                                 <div class="col-md-6 mb-3">
                                     <label for="batch_no" class="form-label">Batch No <span class="text-danger">*</span></label>
                                     <input type="text" id="batch_no" name="batch_no" class="form-control" value="{{ old('batch_no', $batch->batch_no) }}" required>
                                 </div>

                                 <div class="col-md-6 mb-3">
                                     <label for="cost_per_unit" class="form-label">Cost / Unit <span class="text-danger">*</span></label>
                                     <input type="number" step="0.01" id="cost_per_unit" name="cost_per_unit" class="form-control" value="{{ old('cost_per_unit', $batch->cost_per_unit) }}" required>
                                 </div>
                             </div>

                             <div class="row">
                                 <div class="col-md-6 mb-3">
                                     <label for="expiry_date" class="form-label">Expiry Date</label>
                                     <input type="date" id="expiry_date" name="expiry_date" class="form-control" value="{{ old('expiry_date', $batch->expiry_date ? \Carbon\Carbon::parse($batch->expiry_date)->format('Y-m-d') : '') }}">
                                 </div>
                             </div>

                             <div class="row">
                                 <div class="col-md-4 mb-3">
                                     <label class="form-label">Product</label>
                                     <input type="text" class="form-control" value="{{ $batch->product->name ?? 'N/A' }}" disabled>
                                 </div>
                                 <div class="col-md-4 mb-3">
                                     <label class="form-label">Warehouse</label>
                                     <input type="text" class="form-control" value="{{ $batch->warehouse->name ?? 'N/A' }}" disabled>
                                 </div>
                                 <div class="col-md-4 mb-3">
                                     <label class="form-label">Remaining Qty</label>
                                     <input type="text" class="form-control" value="{{ $batch->remaining_qty }}" disabled>
                                 </div>
                             </div>

                             <div class="mt-4">
                                 <button type="submit" class="btn btn-primary me-1">Update Batch</button>
                                 <a href="{{ route('batches.index') }}" class="btn btn-light">Cancel</a>
                             </div>
                         </form>
                     </div>
                 </div>
             </div>
         </div>
    </div>
@endsection
