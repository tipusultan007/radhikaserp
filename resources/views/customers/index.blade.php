@extends('layouts.vertical', ['page_title' => 'Customers', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('css')
@endsection

@section('content')
    <div class="container-fluid">
         <div class="row">
            <div class="col-12">
                <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                    <h4 class="page-title">Customers</h4>
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                        <li class="breadcrumb-item active">Customers</li>
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
                                 <a href="{{ route('customers.create') }}" class="btn btn-danger mb-2"><i class="ri-add-line me-1"></i> Add Customer</a>
                                 <a href="{{ route('customers.export', request()->all()) }}" class="btn btn-success mb-2 ms-1"><i class="ri-file-excel-2-line me-1"></i> Export</a>
                             </div>
                             <div class="col-sm-8">
                                 <form action="{{ route('customers.index') }}" method="GET" class="d-flex justify-content-sm-end" id="searchForm">
                                     <div class="input-group" style="max-width: 350px;">
                                         <input type="text" class="form-control" name="search" id="searchInput" placeholder="Search by Name, Email, or Phone..." value="{{ request('search') }}">
                                         <button class="btn btn-primary" type="submit"><i class="ri-search-line"></i></button>
                                         @if(request('search'))
                                             <a href="{{ route('customers.index') }}" class="btn btn-light" title="Clear Search"><i class="ri-close-line"></i></a>
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
                                         <th>Credit Limit (TK)</th>
                                         <th>Total Due (TK)</th>
                                         <th>Wallet (TK)</th>
                                         <th style="width: 125px;">Action</th>
                                     </tr>
                                 </thead>
                                 <tbody>
                                     @forelse($customers as $customer)
                                         <tr>
                                             <td>{{ $customer->name }}</td>
                                             <td>{{ $customer->phone }}</td>
                                             <td>{{ Str::limit($customer->address, 30) }}</td>
                                             <td>{{ number_format($customer->credit_limit, 0) }}</td>
                                             <td>
                                                 <span class="{{ $customer->total_due > 0 ? 'text-danger fw-bold' : 'text-success' }}">
                                                     {{ number_format($customer->total_due, 0) }}
                                                 </span>
                                             </td>
                                             <td>
                                                 <span class="{{ $customer->wallet_balance > 0 ? 'text-success fw-bold' : '' }}">
                                                     {{ number_format($customer->wallet_balance, 0) }}
                                                 </span>
                                             </td>
                                             <td class="text-end">
                                                 <div class="dropdown">
                                                     <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                         <i class="ri-settings-3-line"></i> Actions
                                                     </button>
                                                     <ul class="dropdown-menu dropdown-menu-end">
                                                         <li><a class="dropdown-item text-info" href="{{ route('customers.show', $customer) }}"><i class="ri-eye-line me-2"></i> View</a></li>
                                                         <li><a class="dropdown-item text-primary" href="{{ route('customers.edit', $customer) }}"><i class="ri-edit-box-line me-2"></i> Edit</a></li>
                                                         <li>
                                                             <form id="delete-form-{{ $customer->id }}" action="{{ route('customers.destroy', $customer) }}" method="POST" class="d-inline">
                                                                 @csrf
                                                                 @method('DELETE')
                                                                 <button type="button" class="dropdown-item text-danger" onclick="confirmDelete('{{ $customer->id }}')"><i class="ri-delete-bin-line me-2"></i> Delete</button>
                                                             </form>
                                                         </li>
                                                     </ul>
                                                 </div>
                                             </td>
                                         </tr>
                                     @empty
                                         <tr>
                                             <td colspan="7" class="text-center">No customers found.</td>
                                         </tr>
                                     @endforelse
                                 </tbody>
                             </table>
                         </div>
                         
                         <div class="mt-3">
                             {{ $customers->links('pagination::bootstrap-5') }}
                         </div>
                     </div>
                 </div>
             </div>
         </div>
    </div>
@endsection

@section('script')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        let timer;
        const searchInput = document.getElementById('searchInput');
        const searchForm = document.getElementById('searchForm');
        
        searchInput.addEventListener('input', function() {
            clearTimeout(timer);
            timer = setTimeout(() => {
                searchForm.submit();
            }, 500); // 500ms debounce
        });
        
        // Focus the input and move cursor to end
        if(searchInput.value) {
            searchInput.focus();
            let val = searchInput.value;
            searchInput.value = '';
            searchInput.value = val;
        }
    });

    function confirmDelete(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this! This will delete the customer and all related data.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('delete-form-' + id).submit();
            }
        })
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection

