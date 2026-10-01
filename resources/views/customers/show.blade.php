@extends('layouts.vertical', ['page_title' => 'Customer Details', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                <h4 class="page-title">Customer Details: {{ $customer->name }}{{ $customer->company ? ' (' . $customer->company . ')' : '' }}</h4>
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
                    <p><strong>Company:</strong> {{ $customer->company ?: '—' }}</p>
                    <p><strong>Phone:</strong> {{ $customer->phone }}</p>
                    <p><strong>Email:</strong> {{ $customer->email ?: '—' }}</p>
                    <p><strong>District:</strong> {{ $customer->district ?: '—' }}</p>
                    <p><strong>Customer Type:</strong> <span class="badge bg-soft-info text-info text-capitalize">{{ str_replace('_', ' ', $customer->customer_type ?? 'customer') }}</span></p>
                    <p><strong>Address:</strong> {{ $customer->address ?: '—' }}</p>
                    <hr>
                    <p><strong>Credit Limit:</strong> <span class="text-success">৳{{ number_format($customer->credit_limit, 0) }}</span></p>
                    <p><strong>Opening Balance:</strong> ৳{{ number_format($customer->opening_balance, 0) }}</p>
                    <p><strong>Wallet Balance:</strong> <span class="text-success fw-bold">৳{{ number_format($customer->wallet_balance, 0) }}</span></p>
                    <p><strong>Total Due (Current):</strong> <span class="text-danger fw-bold fs-4">৳{{ number_format($customer->total_due, 0) }}</span></p>
                    <div class="mt-3 d-flex flex-wrap gap-2">
                        <a href="{{ route('customers.edit', $customer->id) }}" class="btn btn-warning btn-sm"><i class="ri-edit-box-line me-1"></i> Edit Customer</a>
                        <button type="button" class="btn btn-success btn-sm btn-whatsapp-share" style="background-color: #25D366; border-color: #25D366;"
                                data-customer="{{ json_encode([
                                    'id' => $customer->id,
                                    'name' => $customer->name,
                                    'company' => $customer->company,
                                    'phone' => $customer->phone,
                                    'email' => $customer->email,
                                    'district' => $customer->district,
                                    'address' => $customer->address,
                                    'customer_type' => $customer->customer_type,
                                    'credit_limit' => (float)$customer->credit_limit,
                                    'total_due' => (float)$finalRunningBalance,
                                    'wallet_balance' => (float)$customer->wallet_balance,
                                    'opening_balance' => (float)$openingBalance,
                                    'period_debit' => (float)$totalDebit,
                                    'period_credit' => (float)$totalCredit,
                                    'closing_balance' => (float)$finalRunningBalance,
                                    'start_date' => $startDate ?? '',
                                    'end_date' => $endDate ?? '',
                                    'statement_pdf_url' => $statementShortUrl ?? $statementPdfSignedUrl,
                                    'show_url' => route('customers.show', $customer->id)
                                ]) }}">
                            <i class="ri-whatsapp-line me-1"></i> Share via WhatsApp
                        </button>
                        <form action="{{ route('customers.recalculate', $customer->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-info btn-sm" onclick="return confirm('Are you sure you want to recalculate balances? This will sync the wallet and due balances from the ledger.')">
                                <i class="ri-refresh-line"></i> Recalculate Balances
                            </button>
                        </form>
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
                            <label class="form-label">Total Payment Amount (৳)</label>
                            <input type="number" step="1" name="amount" class="form-control" required>
                            @if($customer->wallet_balance > 0)
                                <small class="text-success d-block mt-1">Customer has a wallet balance of <strong>৳{{ number_format($customer->wallet_balance, 0) }}</strong>. It will be deducted first.</small>
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
                        <li class="nav-item">
                            <a href="#payments-tab" data-bs-toggle="tab" aria-expanded="false" class="nav-link">
                                <i class="ri-bank-card-line"></i> Payments
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <!-- Ledger Tab -->
                        <div class="tab-pane show active" id="ledger-tab">
                            {{-- ── Date Range Filter & Actions Bar ─────────────────────── --}}
                            <div class="card border border-light-subtle shadow-none mb-3 bg-light-subtle">
                                <div class="card-body p-3">
                                    <form method="GET" action="{{ route('customers.show', $customer->id) }}">
                                        <div class="row g-2 align-items-end">
                                            <div class="col-md-3 col-sm-6">
                                                <label class="form-label small fw-semibold text-muted mb-1">
                                                    <i class="ri-calendar-event-line me-1"></i>Start Date
                                                </label>
                                                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate ?? '' }}">
                                            </div>
                                            <div class="col-md-3 col-sm-6">
                                                <label class="form-label small fw-semibold text-muted mb-1">
                                                    <i class="ri-calendar-check-line me-1"></i>End Date
                                                </label>
                                                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate ?? '' }}">
                                            </div>
                                            <div class="col-md-6 col-sm-12 d-flex flex-wrap gap-2 justify-content-md-end">
                                                <button type="submit" class="btn btn-primary btn-sm">
                                                    <i class="ri-filter-3-line me-1"></i> Filter
                                                </button>
                                                @if(!empty($startDate) || !empty($endDate))
                                                    <a href="{{ route('customers.show', $customer->id) }}" class="btn btn-outline-secondary btn-sm" title="Reset date filter">
                                                        <i class="ri-refresh-line"></i>
                                                    </a>
                                                @endif
                                                <a href="{{ route('customers.statement.pdf', ['customer' => $customer->id, 'start_date' => $startDate, 'end_date' => $endDate]) }}" target="_blank" class="btn btn-danger btn-sm" title="View or Download PDF Statement">
                                                    <i class="ri-file-pdf-line me-1"></i> Download PDF
                                                </a>
                                                <button type="button" class="btn btn-success btn-sm btn-whatsapp-statement" style="background-color: #25D366; border-color: #25D366;"
                                                        data-customer="{{ json_encode([
                                                            'id' => $customer->id,
                                                            'name' => $customer->name,
                                                            'company' => $customer->company,
                                                            'phone' => $customer->phone,
                                                            'email' => $customer->email,
                                                            'district' => $customer->district,
                                                            'address' => $customer->address,
                                                            'customer_type' => $customer->customer_type,
                                                            'credit_limit' => (float)$customer->credit_limit,
                                                            'total_due' => (float)$finalRunningBalance,
                                                            'wallet_balance' => (float)$customer->wallet_balance,
                                                            'opening_balance' => (float)$openingBalance,
                                                            'period_debit' => (float)$totalDebit,
                                                            'period_credit' => (float)$totalCredit,
                                                            'closing_balance' => (float)$finalRunningBalance,
                                                            'start_date' => $startDate ? \Carbon\Carbon::parse($startDate)->format('d M, Y') : '',
                                                            'end_date' => $endDate ? \Carbon\Carbon::parse($endDate)->format('d M, Y') : '',
                                                            'statement_pdf_url' => $statementShortUrl ?? $statementPdfSignedUrl,
                                                            'show_url' => route('customers.show', $customer->id)
                                                        ]) }}"
                                                        title="Share Statement via WhatsApp">
                                                    <i class="ri-whatsapp-line me-1"></i> Share via WhatsApp
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            {{-- ── Statement Snapshot ──────────────────────────────────── --}}
                            <div class="row g-2 mb-3">
                                <div class="col-sm-3 col-6">
                                    <div class="p-2 border rounded bg-light text-center">
                                        <div class="text-muted font-11 text-uppercase fw-semibold">Opening Balance</div>
                                        <div class="fw-bold fs-5 text-dark">৳{{ number_format($openingBalance, 0) }}</div>
                                    </div>
                                </div>
                                <div class="col-sm-3 col-6">
                                    <div class="p-2 border rounded bg-light text-center">
                                        <div class="text-muted font-11 text-uppercase fw-semibold">Purchases (+)</div>
                                        <div class="fw-bold fs-5 text-danger">৳{{ number_format($totalDebit, 0) }}</div>
                                    </div>
                                </div>
                                <div class="col-sm-3 col-6">
                                    <div class="p-2 border rounded bg-light text-center">
                                        <div class="text-muted font-11 text-uppercase fw-semibold">Payments (-)</div>
                                        <div class="fw-bold fs-5 text-success">৳{{ number_format($totalCredit, 0) }}</div>
                                    </div>
                                </div>
                                <div class="col-sm-3 col-6">
                                    <div class="p-2 border rounded {{ $finalRunningBalance > 0 ? 'bg-danger-subtle border-danger' : 'bg-success-subtle border-success' }} text-center">
                                        <div class="font-11 text-uppercase fw-semibold {{ $finalRunningBalance > 0 ? 'text-danger' : 'text-success' }}">
                                            Closing Due
                                        </div>
                                        <div class="fw-bold fs-5 {{ $finalRunningBalance > 0 ? 'text-danger' : 'text-success' }}">
                                            ৳{{ number_format($finalRunningBalance, 0) }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered table-sm align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Ref / Inv</th>
                                            <th>Notes</th>
                                            <th>Payment Method</th>
                                            <th class="text-end text-danger">Debit</th>
                                            <th class="text-end text-success">Credit</th>
                                            <th class="text-end">Running Balance</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(!empty($startDate) || $openingBalance != 0)
                                            <tr class="table-light">
                                                <td><span class="text-muted">{{ !empty($startDate) ? \Carbon\Carbon::parse($startDate)->format('d M, Y') : '—' }}</span></td>
                                                <td><span class="badge bg-secondary font-11">OPENING</span></td>
                                                <td><em>Opening Balance as of {{ !empty($startDate) ? \Carbon\Carbon::parse($startDate)->format('d M, Y') : 'Start' }}</em></td>
                                                <td class="text-muted">—</td>
                                                <td class="text-end text-danger">{{ $openingBalance > 0 ? '৳' . number_format($openingBalance, 0) : '-' }}</td>
                                                <td class="text-end text-success">{{ $openingBalance < 0 ? '৳' . number_format(abs($openingBalance), 0) : '-' }}</td>
                                                <td class="text-end fw-bold">৳{{ number_format($openingBalance, 0) }}</td>
                                                <td></td>
                                            </tr>
                                        @endif

                                        @forelse($ledgerEntries as $entry)
                                            <tr>
                                                <td>{{ \Carbon\Carbon::parse($entry->journal->date)->format('d M, Y') }}</td>
                                                <td>
                                                    @if($entry->journal->reference_type == 'App\Models\Sale')
                                                        <a href="{{ route('sales.show', $entry->journal->reference_id) }}">{{ $entry->ref_no ?? $entry->journal->journal_no }}</a>
                                                    @else
                                                        {{ $entry->ref_no ?? $entry->journal->journal_no }}
                                                    @endif
                                                </td>
                                                <td>{{ $entry->notes ?? $entry->journal->notes }}</td>
                                                <td>
                                                    @if(!empty($entry->payment_method))
                                                        <span class="badge bg-soft-info text-info font-12"><i class="ri-bank-card-line me-1"></i>{{ $entry->payment_method }}</span>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td class="text-end text-danger">{{ $entry->debit > 0 ? '৳' . number_format($entry->debit, 0) : '-' }}</td>
                                                <td class="text-end text-success">{{ $entry->credit > 0 ? '৳' . number_format($entry->credit, 0) : '-' }}</td>
                                                <td class="text-end fw-bold">৳{{ number_format($entry->running_balance, 0) }}</td>
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
                                            <tr><td colspan="8" class="text-center py-3 text-muted">No ledger entries found for the selected period.</td></tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-light">
                                            <th colspan="4" class="text-end">Period Totals:</th>
                                            <th class="text-end text-danger fs-5">৳ {{ number_format($totalDebit, 0) }}</th>
                                            <th class="text-end text-success fs-5">৳ {{ number_format($totalCredit, 0) }}</th>
                                            <th class="text-end {{ $finalRunningBalance > 0 ? 'text-danger' : 'text-success' }} fs-4">৳ {{ number_format($finalRunningBalance, 0) }}</th>
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
                                            <td>৳{{ number_format($sale->total, 0) }}</td>
                                            <td class="text-success">৳{{ number_format($sale->paid_amount, 0) }}</td>
                                            <td class="text-danger">৳{{ number_format($sale->due_amount, 0) }}</td>
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
                                                        <li>
                                                            <a href="javascript:void(0);" class="dropdown-item text-danger" onclick="confirmDeleteSale({{ $sale->id }})">
                                                                <i class="ri-delete-bin-line me-1"></i> Delete
                                                            </a>
                                                            <form id="delete-sale-form-{{ $sale->id }}" action="{{ route('sales.destroy', $sale->id) }}" method="POST" class="d-none">
                                                                @csrf
                                                                @method('DELETE')
                                                            </form>
                                                        </li>
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

                        <!-- Payments Tab -->
                        <div class="tab-pane" id="payments-tab">
                            <h4 class="header-title mb-3">Payments History</h4>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Journal Ref</th>
                                            <th>Notes</th>
                                            <th class="text-end text-success">Amount (Credit)</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($ledgerEntries->filter(function($entry) { return $entry->credit > 0; }) as $entry)
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
                                                <td class="text-end text-success">৳{{ number_format($entry->credit, 0) }}</td>
                                                <td class="text-end">
                                                    @if($entry->journal->reference_type == 'App\Models\Customer' && $entry->journal->notes != 'Opening Balance' && $entry->journal->notes != 'Sale Invoice Generated')
                                                        <div class="dropdown">
                                                            <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                                <i class="ri-settings-3-line"></i>
                                                            </button>
                                                            <ul class="dropdown-menu dropdown-menu-end">
                                                                <li><a class="dropdown-item text-primary" href="{{ route('customer-dues.edit', $entry->journal->id) }}"><i class="ri-edit-box-line me-2"></i> Edit</a></li>
                                                                <li>
                                                                    <form id="delete-form-payments-{{ $entry->journal->id }}" action="{{ route('customer-dues.destroy', $entry->journal->id) }}" method="POST" class="d-inline">
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
                                            <tr><td colspan="5" class="text-center">No payments found.</td></tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-light">
                                            <th colspan="3" class="text-end">Total Payments:</th>
                                            <th class="text-end text-success fs-5">৳ {{ number_format($totalCredit, 0) }}</th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    {{-- WhatsApp Share Modal Partial --}}
    @include('customers.whatsapp-modal')
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
            confirmButtonText: 'Yes, delete it!',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return fetch('/customer-dues/' + id, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(response.statusText)
                    }
                    return response.json()
                })
                .catch(error => {
                    Swal.showValidationMessage(`Request failed: ${error}`)
                })
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Deleted!',
                    text: 'The payment has been successfully deleted.',
                    icon: 'success'
                }).then(() => {
                    location.reload();
                });
            }
        })
    }
    
    function confirmDeleteSale(id) {
        Swal.fire({
            title: 'Delete Sale?',
            text: "This will delete the sale, all its items, and reverse all balances/stock. This cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete sale!',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return fetch('/sales/' + id, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(response.statusText)
                    }
                    return response.json()
                })
                .catch(error => {
                    Swal.showValidationMessage(`Request failed: ${error}`)
                })
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Deleted!',
                    text: 'The sale has been successfully deleted.',
                    icon: 'success'
                }).then(() => {
                    location.reload();
                });
            }
        })
    }
</script>
@endsection

