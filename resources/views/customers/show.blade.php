@extends('layouts.vertical', ['page_title' => 'Customer Details', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                <h4 class="page-title">Customer Details: {{ $customer->name }}</h4>
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Customers</a></li>
                    <li class="breadcrumb-item active">View</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Customer Info -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title mb-3">Information</h4>
                    <p><strong>Name:</strong> {{ $customer->name }}</p>
                    <p><strong>Phone:</strong> {{ $customer->phone }}</p>
                    <p><strong>Address:</strong> {{ $customer->address ?: 'N/A' }}</p>
                    <hr>
                    <p><strong>Credit Limit:</strong> <span class="text-success">${{ number_format($customer->credit_limit, 0) }}</span></p>
                    <p><strong>Opening Balance:</strong> ${{ number_format($customer->opening_balance, 0) }}</p>
                    <p><strong>Wallet Balance:</strong> <span class="text-success fw-bold">${{ number_format($customer->wallet_balance, 0) }}</span></p>
                    <p><strong>Total Due (Current):</strong> <span class="text-danger fw-bold fs-4">${{ number_format($customer->total_due, 0) }}</span></p>
                    <div class="mt-3 d-flex gap-2">
                        <a href="{{ route('customers.edit', $customer->id) }}" class="btn btn-warning btn-sm">Edit Customer</a>
                    </div>
                </div>
            </div>

            <!-- Payment Form -->
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title mb-3">Record Payment</h4>
                    <form action="{{ route('customer-dues.pay') }}" method="POST">
                        @csrf
                        <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                        
                        <div class="mb-3">
                            <label class="form-label">Total Payment Amount ($)</label>
                            <input type="number" step="1" name="amount" class="form-control" required>
                            @if($customer->wallet_balance > 0)
                                <small class="text-success d-block mt-1">Customer has a wallet balance of <strong>${{ number_format($customer->wallet_balance, 0) }}</strong>. It will be deducted first.</small>
                            @else
                                <small class="text-muted d-block mt-1">If the customer has a <strong>Wallet Balance</strong>, it will be automatically deducted first.</small>
                            @endif
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
                            <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" required>
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

        <!-- Sales & Ledger Tabs -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <ul class="nav nav-tabs nav-bordered mb-3">
                        <li class="nav-item">
                            <a href="#ledger-tab" data-bs-toggle="tab" aria-expanded="true" class="nav-link active">
                                <i class="ri-book-read-line"></i> Ledger Statement
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#sales-tab" data-bs-toggle="tab" aria-expanded="false" class="nav-link">
                                <i class="ri-shopping-cart-2-line"></i> Sales History
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <!-- Ledger Tab -->
                        <div class="tab-pane show active" id="ledger-tab">
                            <h4 class="header-title mb-3">Account Ledger</h4>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Journal Ref</th>
                                            <th>Notes</th>
                                            <th class="text-end text-danger">Debit (Due Increase)</th>
                                            <th class="text-end text-success">Credit (Due Decrease)</th>
                                            <th class="text-end">Running Balance</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $runningBalance = 0; @endphp
                                        @forelse($ledgerEntries as $entry)
                                            @php
                                                // Debit increases Accounts Receivable, Credit decreases it
                                                if($entry->type == 'debit') {
                                                    $runningBalance += $entry->amount;
                                                } else {
                                                    $runningBalance -= $entry->amount;
                                                }
                                            @endphp
                                            <tr>
                                                <td>{{ \Carbon\Carbon::parse($entry->journal->date)->format('d M, Y') }}</td>
                                                <td>
                                                    @if($entry->journal->reference_type == 'App\Models\Sale')
                                                        <a href="{{ route('sales.show', $entry->journal->reference_id) }}">{{ $entry->journal->journal_no }}</a>
                                                    @else
                                                        {{ $entry->journal->journal_no }}
                                                    @endif
                                                </td>
                                                <td>{{ $entry->journal->notes }}</td>
                                                <td class="text-end text-danger">{{ $entry->type == 'debit' ? number_format($entry->amount, 0) : '-' }}</td>
                                                <td class="text-end text-success">{{ $entry->type == 'credit' ? number_format($entry->amount, 0) : '-' }}</td>
                                                <td class="text-end fw-bold">{{ number_format($runningBalance, 0) }}</td>
                                                <td class="text-end">
                                                    @if($entry->journal->reference_type == 'App\Models\Customer' && $entry->journal->notes != 'Opening Balance' && $entry->journal->notes != 'Sale Invoice Generated')
                                                        <div class="dropdown">
                                                            <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                                <i class="ri-settings-3-line"></i>
                                                            </button>
                                                            <ul class="dropdown-menu dropdown-menu-end">
                                                                <li><a class="dropdown-item text-primary" href="{{ route('customer-dues.edit', $entry->journal->id) }}"><i class="ri-edit-box-line me-2"></i> Edit</a></li>
                                                                <li>
                                                                    <form id="delete-form-{{ $entry->journal->id }}" action="{{ route('customer-dues.destroy', $entry->journal->id) }}" method="POST" class="d-inline">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="button" class="dropdown-item text-danger" onclick="confirmDelete('{{ $entry->journal->id }}')"><i class="ri-delete-bin-line me-2"></i> Delete</button>
                                                                    </form>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="6" class="text-center">No ledger entries found.</td></tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-light">
                                            <th colspan="5" class="text-end">Final Due Balance:</th>
                                            <th class="text-end text-danger fs-4">${{ number_format($runningBalance, 0) }}</th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Sales Tab -->
                        <div class="tab-pane" id="sales-tab">
                            <h4 class="header-title mb-3">Sales Orders</h4>
                            <div class="table-responsive">
                                <table class="table table-striped dt-responsive nowrap w-100" id="basic-datatable">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Invoice No</th>
                                            <th>Total</th>
                                            <th>Paid</th>
                                            <th>Due</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($customer->sales as $sale)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($sale->date)->format('Y-m-d') }}</td>
                                            <td><a href="{{ route('sales.show', $sale->id) }}">{{ $sale->invoice_no }}</a></td>
                                            <td>${{ number_format($sale->total, 0) }}</td>
                                            <td class="text-success">${{ number_format($sale->paid_amount, 0) }}</td>
                                            <td class="text-danger">${{ number_format($sale->due_amount, 0) }}</td>
                                            <td>
                                                @if($sale->payment_status == 'paid')
                                                    <span class="badge bg-success">Paid</span>
                                                @elseif($sale->payment_status == 'partial')
                                                    <span class="badge bg-warning">Partial</span>
                                                @else
                                                    <span class="badge bg-danger">Due</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="ri-settings-3-line"></i> Action
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li><a class="dropdown-item" href="{{ route('sales.show', $sale->id) }}"><i class="ri-eye-line me-1"></i> View</a></li>
                                                        <!-- Add future actions here like Download PDF -->
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
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
    function confirmDelete(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this! The payment will be deleted and balances will be updated.",
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

