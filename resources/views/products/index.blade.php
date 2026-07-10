@extends('layouts.vertical', ['page_title' => 'Master Products', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('css')
@endsection

@section('content')
    <div class="container-fluid">
         <div class="row">
            <div class="col-12">
                <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                    <h4 class="page-title">Master Products</h4>
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                        <li class="breadcrumb-item active">Master Products</li>
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
                             <div class="col-sm-4">
                                 <a href="{{ route('products.create') }}" class="btn btn-danger mb-2"><i class="ri-add-line me-1"></i> Add Product</a>
                             </div>
                             <div class="col-sm-8">
                                 <form action="{{ route('products.index') }}" method="GET" class="d-flex justify-content-sm-end">
                                     <div class="input-group" style="max-width: 450px;">
                                         <select class="form-select" name="type" style="max-width: 150px;">
                                             <option value="">All Types</option>
                                             <option value="raw" {{ request('type') == 'raw' ? 'selected' : '' }}>Raw Stock</option>
                                             <option value="finished" {{ request('type') == 'finished' ? 'selected' : '' }}>Finished Goods</option>
                                         </select>
                                         <input type="text" class="form-control" name="search" placeholder="Search Name or SKU..." value="{{ request('search') }}">
                                         <button class="btn btn-primary" type="submit"><i class="ri-search-line"></i></button>
                                         @if(request('search') || request('type'))
                                             <a href="{{ route('products.index') }}" class="btn btn-light" title="Clear Search"><i class="ri-close-line"></i></a>
                                         @endif
                                     </div>
                                 </form>
                             </div>
                         </div>

                         <div class="table-responsive-sm">
                             <table class="table table-centered table-hover mb-0">
                                 <thead class="table-light">
                                     <tr>
                                         <th>Name</th>
                                         <th>SKU</th>
                                         <th>Type</th>
                                         <th>Base Unit</th>
                                         <th>Status</th>
                                         <th style="width: 125px;">Action</th>
                                     </tr>
                                 </thead>
                                 <tbody>
                                     @forelse ($products as $product)
                                         <tr>
                                             <td><strong>{{ $product->name }}</strong></td>
                                             <td><span class="badge bg-light text-dark">{{ $product->sku }}</span></td>
                                             <td>
                                                 @if ($product->type === 'raw')
                                                     <span class="badge bg-info">Raw Stock</span>
                                                 @else
                                                     <span class="badge bg-primary">Finished Goods</span>
                                                 @endif
                                             </td>
                                             <td>{{ $product->base_unit }}</td>
                                             <td>
                                                 @if ($product->status)
                                                     <span class="badge bg-success">Active</span>
                                                 @else
                                                     <span class="badge bg-secondary">Inactive</span>
                                                 @endif
                                             </td>
                                             <td>
                                                 <div class="dropdown">
                                                     <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                         Action
                                                     </button>
                                                     <ul class="dropdown-menu">
                                                         <li>
                                                             <a class="dropdown-item" href="{{ route('products.edit', $product->id) }}">
                                                                 <i class="ri-pencil-line me-1"></i> Edit
                                                             </a>
                                                         </li>
                                                         <li>
                                                             <form action="{{ route('products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this master product?');">
                                                                 @csrf
                                                                 @method('DELETE')
                                                                 <button type="submit" class="dropdown-item text-danger">
                                                                     <i class="ri-delete-bin-2-line me-1"></i> Delete
                                                                 </button>
                                                             </form>
                                                         </li>
                                                     </ul>
                                                 </div>
                                             </td>
                                         </tr>
                                     @empty
                                         <tr>
                                             <td colspan="6" class="text-center">No master products found.</td>
                                         </tr>
                                     @endforelse
                                 </tbody>
                             </table>
                         </div>
                         
                         <div class="mt-3">
                             {{ $products->links('pagination::bootstrap-5') }}
                         </div>
                     </div>
                 </div>
             </div>
         </div>
    </div>
@endsection
