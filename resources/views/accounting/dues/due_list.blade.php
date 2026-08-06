@extends('layouts.vertical', ['page_title' => 'Due Customers', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                <h4 class="page-title">Due Customers</h4>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('customer-dues.list-export', request()->all()) }}" class="btn btn-success btn-sm"><i class="ri-file-excel-2-line me-1"></i> Export CSV</a>
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                        <li class="breadcrumb-item">Accounting</li>
                        <li class="breadcrumb-item active">Due Customers</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card bg-light mb-3 shadow-none border">
                <div class="card-body py-2 px-3">
                    <form action="{{ route('customer-dues.list') }}" method="GET">
                        <div class="row gy-2 gx-2 align-items-center">
                            <div class="col-md-4">
                                <label class="form-label mb-1">Search Customer</label>
                                <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Search by name or phone">
                            </div>
                            <div class="col-md-2 mt-auto">
                                <button class="btn btn-primary w-100" type="submit"><i class="ri-search-line me-1"></i> Search</button>
                            </div>
                            @if(request('search'))
                                <div class="col-md-1 mt-auto">
                                    <a href="{{ route('customer-dues.list') }}" class="btn btn-light w-100">Clear</a>
                                </div>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-centered table-striped dt-responsive nowrap w-100 mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Customer Name</th>
                                    <th>Phone</th>
                                    <th>Address</th>
                                    <th class="text-end">Wallet Balance (৳)</th>
                                    <th class="text-end text-danger">Total Due (৳)</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($customers as $customer)
                                <tr>
                                    <td>
                                        <a href="{{ route('customers.show', $customer->id) }}" class="fw-bold text-body">
                                            {{ $customer->name }}
                                        </a>
                                    </td>
                                    <td>{{ $customer->phone ?? 'N/A' }}</td>
                                    <td>{{ $customer->address ?? 'N/A' }}</td>
                                    <td class="text-end fw-semibold">{{ number_format($customer->wallet_balance, 2) }}</td>
                                    <td class="text-end fw-bold text-danger">{{ number_format($customer->total_due, 2) }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('customer-dues.index', ['customer_id' => $customer->id]) }}" class="btn btn-sm btn-soft-primary" title="Make Payment">
                                            <i class="ri-money-dollar-circle-line me-1"></i> Pay
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No due customers found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($customers->hasPages())
                <div class="card-footer">
                    {{ $customers->links('pagination::bootstrap-5') }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
