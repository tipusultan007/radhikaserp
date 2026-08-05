@extends('layouts.vertical', ['page_title' => 'Batch Tracking', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('css')
@endsection

@section('content')
    <div class="container-fluid">
         <div class="row">
            <div class="col-12">
                <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                    <h4 class="page-title">Batch Tracking</h4>
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                        <li class="breadcrumb-item active">Batches</li>
                    </ol>
                </div>
            </div>
         </div>

         @if (session('success'))
             <div class="alert alert-success alert-dismissible fade show" role="alert">
                 {{ session('success') }}
                 <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
             </div>
         @endif
         @if (session('error'))
             <div class="alert alert-danger alert-dismissible fade show" role="alert">
                 {{ session('error') }}
                 <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
             </div>
         @endif

         <div class="row">
             <div class="col-12">
                 <div class="card">
                     <div class="card-body">
                         <div class="row mb-3 align-items-center">
                             <div class="col-sm-8">
                                 <form action="{{ route('batches.index') }}" method="GET" class="d-flex justify-content-sm-start" id="searchForm">
                                     <div class="input-group dropdown" style="max-width: 350px;">
                                         <input type="text" class="form-control" name="search" placeholder="Search batch, product, warehouse..." value="{{ request('search') }}" autocomplete="off">
                                         <button class="btn btn-primary" type="submit"><i class="ri-search-line"></i></button>
                                         @if(request('search'))
                                             <a href="{{ route('batches.index') }}" class="btn btn-light" title="Clear Search"><i class="ri-close-line"></i></a>
                                         @endif
                                     </div>
                                 </form>
                             </div>
                         </div>
                         <div class="table-responsive">
                             <table class="table table-centered table-striped dt-responsive nowrap w-100" id="batches-datatable">
                                 <thead>
                                     <tr>
                                         <th>Batch No</th>
                                         <th>Product</th>
                                         <th>Warehouse</th>
                                         <th>Qty In</th>
                                         <th>Qty Out</th>
                                         <th>Remaining Qty</th>
                                         <th>Cost/Unit</th>
                                         <th>Status</th>
                                         <th style="width: 125px;">Action</th>
                                     </tr>
                                 </thead>
                                 <tbody>
                                     @foreach ($batches as $batch)
                                         <tr>
                                             <td><b>{{ $batch->batch_no }}</b></td>
                                             <td>{{ $batch->product->name ?? 'N/A' }}</td>
                                             <td>{{ $batch->warehouse->name ?? 'N/A' }}</td>
                                             <td>{{ number_format($batch->qty_in, 3) }}</td>
                                             <td>{{ number_format($batch->qty_out, 3) }}</td>
                                             <td>
                                                 <strong>{{ number_format($batch->remaining_qty, 3) }}</strong>
                                             </td>
                                             <td>${{ number_format($batch->cost_per_unit, 0) }}</td>
                                             <td>
                                                 @if($batch->remaining_qty > 0)
                                                     <span class="badge bg-success">Available</span>
                                                 @else
                                                     <span class="badge bg-danger">Depleted</span>
                                                 @endif
                                             </td>
                                             <td class="text-end">
                                                 <div class="dropdown">
                                                     <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                         <i class="ri-settings-3-line"></i> Actions
                                                     </button>
                                                     <ul class="dropdown-menu dropdown-menu-end">
                                                         <li><a class="dropdown-item text-info" href="{{ route('batches.show', $batch) }}"><i class="ri-eye-line me-2"></i> View</a></li>
                                                         <li><a class="dropdown-item text-primary" href="{{ route('batches.edit', $batch) }}"><i class="ri-edit-box-line me-2"></i> Edit</a></li>
                                                         <li>
                                                             <form id="delete-form-{{ $batch->id }}" action="{{ route('batches.destroy', $batch) }}" method="POST" class="d-inline">
                                                                 @csrf
                                                                 @method('DELETE')
                                                                 <button type="button" class="dropdown-item text-danger" onclick="confirmDelete('{{ $batch->id }}')"><i class="ri-delete-bin-line me-2"></i> Delete</button>
                                                             </form>
                                                         </li>
                                                     </ul>
                                                 </div>
                                             </td>
                                         </tr>
                                     @endforeach
                                 </tbody>
                             </table>
                         </div>
                         <div class="d-flex justify-content-end mt-3">
                             {{ $batches->appends(request()->query())->links('pagination::bootstrap-5') }}
                         </div>
                     </div>
                 </div>
             </div>
         </div>
    </div>
@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function confirmDelete(batchId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this! If this batch has sales or transfers, it cannot be deleted.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-form-' + batchId).submit();
                }
            })
        }
    </script>
@endsection
