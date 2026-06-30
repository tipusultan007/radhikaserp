@extends('layouts.vertical', ['page_title' => 'Supplier Payables', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                <h4 class="page-title">Supplier Payments</h4>
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                    <li class="breadcrumb-item active">Supplier Payables</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="card bg-light mb-3 shadow-none border">
                        <div class="card-body py-2 px-3">
                            <form action="{{ route('supplier-payables.index') }}" method="GET">
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
                                        <label class="form-label mb-1">Supplier</label>
                                        <select class="form-select select2" name="supplier_id" data-toggle="select2">
                                            <option value="">All Suppliers</option>
                                            @foreach($suppliersList as $supp)
                                                <option value="{{ $supp->id }}" {{ request('supplier_id') == $supp->id ? 'selected' : '' }}>
                                                    {{ $supp->name }} {{ $supp->phone ? '('.$supp->phone.')' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label mb-1">Payment Method</label>
                                        <select class="form-select" name="payment_method">
                                            <option value="">All Methods</option>
                                            @foreach($paymentMethods as $method)
                                                <option value="{{ $method->id }}" {{ request('payment_method') == $method->id ? 'selected' : '' }}>
                                                    {{ $method->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button class="btn btn-primary w-100" type="submit"><i class="ri-search-line me-1"></i> Filter</button>
                                    </div>
                                    @if(request('start_date') || request('end_date') || request('supplier_id') || request('payment_method'))
                                        <div class="col-md-1">
                                            <a href="{{ route('supplier-payables.index') }}" class="btn btn-light w-100">Clear</a>
                                        </div>
                                    @endif
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-centered table-striped dt-responsive nowrap w-100">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Journal No</th>
                                    <th>Supplier</th>
                                    <th>Amount Paid</th>
                                    <th>Method</th>
                                    <th>Notes</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($payments as $payment)
                                    @php
                                        $cashEntry = $payment->entries->where('type', 'credit')->first();
                                        $amount = $cashEntry ? $cashEntry->amount : 0;
                                        $method = $cashEntry ? $cashEntry->account->name : 'N/A';
                                    @endphp
                                <tr>
                                    <td>{{ $payment->date->format('d M, Y') }}</td>
                                    <td>{{ $payment->journal_no }}</td>
                                    <td>
                                        <b>{{ $payment->reference ? $payment->reference->name : 'N/A' }}</b>
                                        <br><small class="text-muted">{{ $payment->reference ? $payment->reference->phone : '' }}</small>
                                    </td>
                                    <td class="fw-bold text-success">${{ number_format($amount, 0) }}</td>
                                    <td><span class="badge bg-secondary">{{ $method }}</span></td>
                                    <td><small>{{ Str::limit($payment->notes, 30) }}</small></td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="ri-settings-3-line"></i> Actions
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item text-primary" href="{{ route('supplier-payables.edit', $payment->id) }}"><i class="ri-edit-box-line me-2"></i> Edit</a></li>
                                                <li>
                                                    <form id="delete-form-{{ $payment->id }}" action="{{ route('supplier-payables.destroy', $payment->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button" class="dropdown-item text-danger" onclick="confirmDelete('{{ $payment->id }}')"><i class="ri-delete-bin-line me-2"></i> Delete</button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No payments found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        {{ $payments->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title mb-3">Record Payment to Supplier</h4>
                    <form action="{{ route('supplier-payables.pay') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Supplier</label>
                            <select name="supplier_id" class="form-select select2" data-toggle="select2" required>
                                <option value="">Select Supplier...</option>
                                @foreach($suppliersList as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }} (Bal: ${{ number_format($s->total_payable, 0) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount Paid ($)</label>
                            <input type="number" step="1" name="amount" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <select name="payment_method" class="form-select">
                                <option value="">Default (Cash)</option>
                                @if(isset($paymentMethods))
                                    @foreach($paymentMethods as $method)
                                        <option value="{{ $method->id }}">{{ $method->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date</label>
                            <input type="text" name="date" class="form-control flatpickr-date" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reference (Cheque/Txn ID)</label>
                            <input type="text" name="reference" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes (Optional)</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Record Payment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
    @vite(['resources/js/pages/demo.form-advanced.js'])
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function confirmDelete(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "This will permanently delete the payment and restore the supplier balance.",
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
@endsection

