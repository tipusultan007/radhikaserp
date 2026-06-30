@extends('layouts.vertical', ['page_title' => 'Product Variants', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('css')
@endsection

@section('content')
    <div class="container-fluid">
         <div class="row">
            <div class="col-12">
                <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                    <h4 class="page-title">Product Variants</h4>
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                        <li class="breadcrumb-item active">Product Variants</li>
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

         <div class="row">
             <div class="col-12">
                 <div class="card">
                     <div class="card-body">
                         <div class="row mb-3">
                             <div class="col-sm-4">
                                 <a href="{{ route('product-variants.create') }}" class="btn btn-danger mb-2"><i class="ri-add-line me-1"></i> Add Variant</a>
                             </div>
                         </div>

                         <div class="table-responsive-sm">
                             <table class="table table-centered table-hover mb-0">
                                 <thead class="table-light">
                                     <tr>
                                         <th>Parent Product</th>
                                         <th>Variant Name</th>
                                         <th>Unit Qty</th>
                                         <th>Unit Type</th>
                                         <th>Price</th>
                                         <th>Current Stock</th>
                                         <th>Status</th>
                                         <th style="width: 125px;">Action</th>
                                     </tr>
                                 </thead>
                                 <tbody>
                                     @forelse ($variants as $variant)
                                         <tr>
                                             <td>{{ $variant->product->name }}</td>
                                             <td><strong>{{ $variant->name }}</strong></td>
                                             <td>{{ $variant->unit_qty }}</td>
                                             <td>{{ $variant->unit_type }}</td>
                                             <td>${{ number_format($variant->price, 0) }}</td>
                                             <td>
                                                 @if($variant->current_stock > 0)
                                                     <span class="badge bg-success">{{ (float)$variant->current_stock }}</span>
                                                 @else
                                                     <span class="badge bg-danger">{{ (float)$variant->current_stock }}</span>
                                                 @endif
                                             </td>
                                             <td>
                                                 @if ($variant->status)
                                                     <span class="badge bg-success">Active</span>
                                                 @else
                                                     <span class="badge bg-secondary">Inactive</span>
                                                 @endif
                                             </td>
                                             <td>
                                                 <a href="{{ route('product-variants.edit', $variant->id) }}" class="text-reset fs-16 px-1" title="Edit"> 
                                                     <i class="ri-settings-3-line"></i>
                                                 </a>
                                                 <form action="{{ route('product-variants.destroy', $variant->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Are you sure you want to delete this product variant?');">
                                                     @csrf
                                                     @method('DELETE')
                                                     <button type="submit" class="btn btn-link text-reset fs-16 p-0 border-0" title="Delete">
                                                         <i class="ri-delete-bin-2-line"></i>
                                                     </button>
                                                 </form>
                                             </td>
                                         </tr>
                                     @empty
                                         <tr>
                                             <td colspan="10" class="text-center">No product variants found.</td>
                                         </tr>
                                     @endforelse
                                 </tbody>
                             </table>
                         </div>
                     </div>
                 </div>
             </div>
         </div>
    </div>
@endsection

