@extends('layouts.vertical', ['page_title' => 'Supplier Details', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                <h4 class="page-title">Supplier Details: {{ $supplier->name }}</h4>
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('suppliers.index') }}">Suppliers</a></li>
                    <li class="breadcrumb-item active">View</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Supplier Info -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title mb-3">Information</h4>
                    <p><strong>Name:</strong> {{ $supplier->name }}</p>
                    <p><strong>Phone:</strong> {{ $supplier->phone }}</p>
                    <p><strong>Address:</strong> {{ $supplier->address ?: 'N/A' }}</p>
                    <p><strong>Country:</strong> {{ $supplier->country ?: 'N/A' }}</p>
                    <hr>
                    <p><strong>Total Payable (Current):</strong> <span class="text-danger fw-bold fs-4">${{ number_format($supplier->total_payable, 0) }}</span></p>
                    <div class="mt-3 d-flex gap-2">
                        <a href="{{ route('suppliers.edit', $supplier->id) }}" class="btn btn-warning btn-sm">Edit Supplier</a>
                    </div>
                </div>
            </div>

            <!-- Payment Form -->
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title mb-3">Record Payment</h4>
                    <form action="{{ route('supplier-payables.pay') }}" method="POST">
                        @csrf
                        <input type="hidden" name="supplier_id" value="{{ $supplier->id }}">
                        
                        <div class="mb-3">
                            <label class="form-label">Total Payment Amount ($)</label>
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

        <!-- Ledger Tab -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <ul class="nav nav-tabs nav-bordered mb-3">
                        <li class="nav-item">
                            <a href="#ledger-tab" data-bs-toggle="tab" aria-expanded="true" class="nav-link active">
                                <i class="ri-book-read-line"></i> Ledger Statement
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
                                            <th class="text-end text-success">Credit (Payable Increase)</th>
                                            <th class="text-end text-danger">Debit (Payable Decrease)</th>
                                            <th class="text-end">Running Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $runningBalance = 0; @endphp
                                        @forelse($ledgerEntries as $entry)
                                            @php
                                                // Credit increases Accounts Payable, Debit decreases it
                                                if($entry->type == 'credit') {
                                                    $runningBalance += $entry->amount;
                                                } else {
                                                    $runningBalance -= $entry->amount;
                                                }
                                            @endphp
                                            <tr>
                                                <td>{{ \Carbon\Carbon::parse($entry->journal->date)->format('d M, Y') }}</td>
                                                <td>{{ $entry->journal->journal_no }}</td>
                                                <td>{{ $entry->journal->notes }}</td>
                                                <td class="text-end text-success">{{ $entry->type == 'credit' ? number_format($entry->amount, 0) : '-' }}</td>
                                                <td class="text-end text-danger">{{ $entry->type == 'debit' ? number_format($entry->amount, 0) : '-' }}</td>
                                                <td class="text-end fw-bold">{{ number_format($runningBalance, 0) }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="6" class="text-center">No ledger entries found.</td></tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-light">
                                            <th colspan="5" class="text-end">Final Payable Balance:</th>
                                            <th class="text-end text-danger fs-4">${{ number_format($runningBalance, 0) }}</th>
                                        </tr>
                                    </tfoot>
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

