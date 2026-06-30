@extends('layouts.vertical', ['page_title' => 'Customer Payments', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                <h4 class="page-title">Customer Payments</h4>
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                    <li class="breadcrumb-item active">Customer Dues</li>
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
                            <form action="{{ route('customer-dues.index') }}" method="GET">
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
                                        <label class="form-label mb-1">Customer</label>
                                        <select class="form-select select2" name="customer_id" data-toggle="select2">
                                            <option value="">All Customers</option>
                                            @foreach($customersList as $cust)
                                                <option value="{{ $cust->id }}" {{ request('customer_id') == $cust->id ? 'selected' : '' }}>
                                                    {{ $cust->name }} {{ $cust->phone ? '('.$cust->phone.')' : '' }}
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
                                    @if(request('start_date') || request('end_date') || request('customer_id') || request('payment_method'))
                                        <div class="col-md-1">
                                            <a href="{{ route('customer-dues.index') }}" class="btn btn-light w-100">Clear</a>
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
                                    <th>Customer</th>
                                    <th>Cash Paid</th>
                                    <th>Wallet Used</th>
                                    <th>Added to Wallet</th>
                                    <th>Method</th>
                                    <th>Notes</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($payments as $payment)
                                    @php
                                        $cashEntry = $payment->entries->where('type', 'debit')->where('account_id', '!=', function($q) { /* ignoring closure logic */ })->first(function($e) { return $e->account->name !== 'Customer Advance'; });
                                        $amount = $cashEntry ? $cashEntry->amount : 0;
                                        $method = $cashEntry ? $cashEntry->account->name : 'N/A';
                                        
                                        $walletDebit = $payment->entries->first(function($entry) {
                                            return $entry->type === 'debit' && $entry->account->name === 'Customer Advance';
                                        });
                                        $walletUsed = $walletDebit ? $walletDebit->amount : 0;

                                        $advEntry = $payment->entries->first(function($entry) {
                                            return $entry->type === 'credit' && $entry->account->name === 'Customer Advance';
                                        });
                                        $addedToWallet = $advEntry ? $advEntry->amount : 0;
                                    @endphp
                                <tr>
                                    <td>{{ $payment->date->format('d M, Y') }}</td>
                                    <td>{{ $payment->journal_no }}</td>
                                    <td>
                                        @if($payment->reference)
                                            <a href="{{ route('customers.show', $payment->reference->id) }}">
                                                <b>{{ $payment->reference->name }}</b>
                                            </a>
                                            <br><small class="text-muted">{{ $payment->reference->phone }}</small>
                                        @else
                                            <b>N/A</b>
                                        @endif
                                    </td>
                                    <td class="fw-bold text-success">${{ number_format($amount, 0) }}</td>
                                    <td class="fw-bold text-warning">${{ number_format($walletUsed, 0) }}</td>
                                    <td class="fw-bold text-info">${{ number_format($addedToWallet, 0) }}</td>
                                    <td><span class="badge bg-secondary">{{ $method }}</span></td>
                                    <td><small>{{ Str::limit($payment->notes, 30) }}</small></td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="ri-settings-3-line"></i> Actions
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item text-primary" href="{{ route('customer-dues.edit', $payment->id) }}"><i class="ri-edit-box-line me-2"></i> Edit</a></li>
                                                <li>
                                                    <form id="delete-form-{{ $payment->id }}" action="{{ route('customer-dues.destroy', $payment->id) }}" method="POST" class="d-inline">
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
                    <h4 class="header-title mb-3">Record Payment</h4>
                    <form action="{{ route('customer-dues.pay') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Customer</label>
                            <select name="customer_id" id="customer_select" class="form-select select2" data-toggle="select2" required>
                                <option value="" data-wallet="0">Select Customer...</option>
                                @foreach($customersList as $c)
                                    <option value="{{ $c->id }}" data-wallet="{{ $c->wallet_balance }}">{{ $c->name }} (Due: ${{ number_format($c->total_due, 0) }}, Wallet: ${{ number_format($c->wallet_balance, 0) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Total Payment Amount ($)</label>
                            <input type="number" step="1" name="amount" class="form-control" required>
                            <small class="text-muted d-block mt-1">If the customer has a <strong>Wallet Balance</strong>, it will be automatically deducted first.</small>
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
                            <button type="submit" class="btn btn-primary">Process Payment</button>
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
                text: "This will reverse the payment, recalculate invoice statuses, and restore the customer dues.",
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

