@extends('layouts.vertical', ['page_title' => 'Suppliers', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('css')
@endsection

@section('content')
    <div class="container-fluid">
         <div class="row">
            <div class="col-12">
                <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                    <h4 class="page-title">Suppliers</h4>
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                        <li class="breadcrumb-item active">Suppliers</li>
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
                         <div class="row mb-3 align-items-center">
                             <div class="col-sm-4">
                                 <a href="{{ route('suppliers.create') }}" class="btn btn-danger mb-2"><i class="ri-add-line me-1"></i> Add Supplier</a>
                             </div>
                             <div class="col-sm-8">
                                 <form action="{{ route('suppliers.index') }}" method="GET" class="d-flex justify-content-sm-end">
                                     <div class="input-group" style="max-width: 350px;">
                                         <input type="text" class="form-control" name="search" placeholder="Search by Name, Phone, or Country..." value="{{ request('search') }}">
                                         <button class="btn btn-primary" type="submit"><i class="ri-search-line"></i></button>
                                         @if(request('search'))
                                             <a href="{{ route('suppliers.index') }}" class="btn btn-light" title="Clear Search"><i class="ri-close-line"></i></a>
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
                                         <th>Phone</th>
                                         <th>Address</th>
                                         <th>Country</th>
                                         <th>Total Payable (TK)</th>
                                         <th style="width: 125px;">Action</th>
                                     </tr>
                                 </thead>
                                 <tbody>
                                     @forelse ($suppliers as $supplier)
                                         <tr>
                                             <td><strong>{{ $supplier->name }}</strong></td>
                                             <td>{{ $supplier->phone }}</td>
                                             <td>{{ $supplier->address ?? 'N/A' }}</td>
                                             <td>{{ $supplier->country ?? 'N/A' }}</td>
                                             <td><span class="text-danger fw-semibold">{{ number_format($supplier->total_payable, 0) }}</span></td>
                                             <td>
                                                 <a href="{{ route('suppliers.edit', $supplier->id) }}" class="text-reset fs-16 px-1" title="Edit"> 
                                                     <i class="ri-settings-3-line"></i>
                                                 </a>
                                                 <form action="{{ route('suppliers.destroy', $supplier->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Are you sure you want to delete this supplier?');">
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
                                             <td colspan="6" class="text-center">No suppliers found.</td>
                                         </tr>
                                     @endforelse
                                 </tbody>
                             </table>
                         </div>
                         
                         <div class="mt-3">
                             {{ $suppliers->links('pagination::bootstrap-5') }}
                         </div>
                     </div>
                 </div>
             </div>
         </div>
    </div>
@endsection

