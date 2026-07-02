@extends('layouts.vertical', ['page_title' => 'Repackaging Orders', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('css')
@endsection

@section('content')
    <div class="container-fluid">
         <div class="row">
            <div class="col-12">
                <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                    <h4 class="page-title">Repackaging Orders</h4>
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                        <li class="breadcrumb-item active">Repackaging</li>
                    </ol>
                </div>
            </div>
         </div>

         <div class="row">
             <div class="col-12">
                 <div class="card">
                     <div class="card-body">
                         <div class="row mb-3 align-items-center">
                             <div class="col-sm-4">
                                 <a href="{{ route('repackaging.create') }}" class="btn btn-danger rounded-pill"><i class="ri-add-line me-1"></i> New Repackaging</a>
                             </div>
                         </div>
                         
                         <div class="card bg-light mb-3 shadow-none border">
                             <div class="card-body py-2 px-3">
                                 <form action="{{ route('repackaging.index') }}" method="GET">
                                     <div class="row gy-2 gx-2 align-items-end">
                                         <div class="col-md-2">
                                             <label class="form-label mb-1">Start Date</label>
                                             <input type="text" class="form-control flatpickr-date" name="start_date" value="{{ request('start_date') }}" placeholder="YYYY-MM-DD">
                                         </div>
                                         <div class="col-md-2">
                                             <label class="form-label mb-1">End Date</label>
                                             <input type="text" class="form-control flatpickr-date" name="end_date" value="{{ request('end_date') }}" placeholder="YYYY-MM-DD">
                                         </div>
                                         <div class="col-md-3">
                                             <label class="form-label mb-1">Warehouse</label>
                                             <select class="form-select" name="warehouse_id">
                                                 <option value="">Any Warehouse</option>
                                                 @foreach($warehouses as $wh)
                                                     <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                                                 @endforeach
                                             </select>
                                         </div>
                                         <div class="col-md-3">
                                             <label class="form-label mb-1">Order No</label>
                                             <input type="text" class="form-control" name="ref_no" value="{{ request('ref_no') }}" placeholder="e.g. RPK-123">
                                         </div>
                                         <div class="col-md-2">
                                             <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line me-1"></i> Filter</button>
                                         </div>
                                     </div>
                                 </form>
                             </div>
                         </div>

                         @if (session('success'))
                             <div class="alert alert-success">{{ session('success') }}</div>
                         @endif

                         <div class="table-responsive">
                             <table class="table table-centered table-striped dt-responsive nowrap w-100" id="repack-datatable">
                                 <thead>
                                     <tr>
                                         <th>Order No</th>
                                         <th>Date</th>
                                         <th>Warehouse</th>
                                         <th>Consumed (Input)</th>
                                         <th>Produced (Output)</th>
                                         <th>Notes</th>
                                         <th style="width: 130px;">Action</th>
                                     </tr>
                                 </thead>
                                 <tbody>
                                     @foreach ($orders as $order)
                                         @php
                                             $inputs = [];
                                             foreach($order->inputs as $input) {
                                                 $inputs[] = number_format($input->qty_used, 0) . ' ' . ($input->product->base_unit ?? '');
                                             }
                                             $outputs = [];
                                             foreach($order->outputs as $output) {
                                                 if ($output->product_variant_id) {
                                                     $outputs[] = number_format($output->qty_produced, 0) . ' Pkg';
                                                 } else {
                                                     $outputs[] = number_format($output->qty_produced, 0) . ' ' . ($output->product->base_unit ?? '');
                                                 }
                                             }
                                         @endphp
                                         <tr>
                                             <td><b>{{ $order->ref_no }}</b></td>
                                             <td>{{ $order->date->format('Y-m-d') }}</td>
                                             <td>{{ $order->warehouse->name ?? 'N/A' }}</td>
                                             <td>
                                                 @if(!empty($inputs))
                                                    <span class="badge bg-danger-subtle text-danger fs-13">{{ implode(', ', $inputs) }}</span>
                                                 @else
                                                    -
                                                 @endif
                                             </td>
                                             <td>
                                                 @if(!empty($outputs))
                                                    <span class="badge bg-success-subtle text-success fs-13">{{ implode(', ', $outputs) }}</span>
                                                 @else
                                                    -
                                                 @endif
                                             </td>
                                             <td>{{ Str::limit($order->notes, 20) }}</td>
                                             <td>
                                                 <div class="dropdown">
                                                     <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                         Action <i class="mdi mdi-chevron-down"></i>
                                                     </button>
                                                     <ul class="dropdown-menu dropdown-menu-end">
                                                         <li><a class="dropdown-item" href="{{ route('repackaging.show', $order->id) }}"><i class="ri-eye-fill me-2 align-middle text-info"></i> View</a></li>
                                                         @can('edit repackaging')
                                                         <li><a class="dropdown-item" href="{{ route('repackaging.edit', $order->id) }}"><i class="ri-edit-box-line me-2 align-middle text-primary"></i> Edit</a></li>
                                                         @endcan
                                                         @can('delete repackaging')
                                                         <li>
                                                             <form action="{{ route('repackaging.destroy', $order->id) }}" method="POST" class="delete-form m-0 p-0">
                                                                 @csrf
                                                                 @method('DELETE')
                                                                 <button type="button" class="dropdown-item text-danger delete-btn"><i class="ri-delete-bin-line me-2 align-middle"></i> Delete</button>
                                                             </form>
                                                         </li>
                                                         @endcan
                                                     </ul>
                                                 </div>
                                             </td>
                                         </tr>
                                     @endforeach
                                 </tbody>
                             </table>
                         </div>
                         
                         <div class="mt-3">
                             {{ $orders->links('pagination::bootstrap-5') }}
                         </div>
                     </div>
                 </div>
             </div>
         </div>
    </div>
@endsection

@section('script-bottom')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const deleteButtons = document.querySelectorAll('.delete-btn');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const form = this.closest('.delete-form');
                
                Swal.fire({
                    title: 'Are you sure?',
                    text: "Deleting this order will reverse the stock changes. This cannot be undone if finished stock is already consumed!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    });
</script>
@endsection

