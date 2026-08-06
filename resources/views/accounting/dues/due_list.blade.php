@extends('layouts.vertical', ['page_title' => 'Due Customers', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('css')
@endsection

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

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ── Filters ─────────────────────────────────────────────────────────── --}}
    <div class="row">
        <div class="col-12">
            <div class="card bg-light mb-3 shadow-none border">
                <div class="card-body py-2 px-3">
                    <form action="{{ route('customer-dues.list') }}" method="GET" id="filterForm">
                        <div class="row gy-2 gx-2 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label mb-1">Search Customer</label>
                                <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Search by name or phone">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label mb-1">District</label>
                                <select name="district" id="districtFilter" class="form-control" data-toggle="select2" data-allow-clear="true" data-placeholder="All Districts">
                                    <option value="">All Districts</option>
                                    @foreach($districts as $district)
                                        <option value="{{ $district }}" {{ request('district') == $district ? 'selected' : '' }}>{{ $district }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mt-auto">
                                <button class="btn btn-primary w-100" type="submit"><i class="ri-search-line me-1"></i> Search</button>
                            </div>
                            @if(request('search') || request('district'))
                                <div class="col-md-1 mt-auto">
                                    <a href="{{ route('customer-dues.list') }}" class="btn btn-light w-100"><i class="ri-refresh-line"></i> Clear</a>
                                </div>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            {{-- ── Table ─────────────────────────────────────────────────────── --}}
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-centered table-striped dt-responsive nowrap w-100 mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Customer Name</th>
                                    <th>Phone</th>
                                    <th>District</th>
                                    <th>Company</th>
                                    <th class="text-end">Wallet (৳)</th>
                                    <th class="text-end text-danger">Due (৳)</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($customers as $i => $customer)
                                <tr>
                                    <td>{{ $customers->firstItem() + $i }}</td>
                                    <td>
                                        <a href="{{ route('customers.show', $customer->id) }}" class="fw-bold text-body">
                                            {{ $customer->name }}
                                        </a>
                                    </td>
                                    <td>{{ $customer->phone ?? 'N/A' }}</td>
                                    <td>
                                        @if($customer->district)
                                            <span class="badge bg-soft-primary text-primary">{{ $customer->district }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $customer->company ?: '—' }}</td>
                                    <td class="text-end fw-semibold text-success">{{ number_format($customer->wallet_balance, 2) }}</td>
                                    <td class="text-end fw-bold text-danger">{{ number_format($customer->total_due, 2) }}</td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-danger pay-btn"
                                            data-customer-id="{{ $customer->id }}"
                                            data-customer-name="{{ $customer->name }}"
                                            data-total-due="{{ $customer->total_due }}"
                                            data-wallet="{{ $customer->wallet_balance }}"
                                            data-bs-toggle="modal"
                                            data-bs-target="#paymentModal">
                                            <i class="ri-money-dollar-circle-line me-1"></i> Pay
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">No due customers found.</td>
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

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- Payment Modal                                                              --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <form action="{{ route('customer-dues.pay') }}" method="POST" id="paymentForm">
                @csrf
                <input type="hidden" name="customer_id" id="modal_customer_id">

                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="paymentModalLabel">
                        <i class="ri-money-dollar-circle-line me-1"></i> Collect Payment
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    {{-- Customer Info Banner --}}
                    <div class="alert alert-danger py-2 mb-3" id="modal_info_banner">
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold" id="modal_customer_name"></span>
                            <span>Due: <strong id="modal_due_display" class="text-danger"></strong></span>
                        </div>
                        <small class="text-muted">Wallet Balance: <span id="modal_wallet_display"></span> ৳</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                            <input type="text" name="date" class="form-control flatpickr-date" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Amount (৳) <span class="text-danger">*</span></label>
                            <input type="number" name="amount" id="modal_amount" class="form-control" min="1" step="0.01" placeholder="0.00" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Payment Method</label>
                        <select name="payment_method" class="form-control" data-toggle="select2">
                            <option value="">— Cash (Default) —</option>
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method->id }}">{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Reference</label>
                            <input type="text" name="reference" class="form-control" placeholder="e.g. Receipt #123">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Notes</label>
                            <input type="text" name="notes" class="form-control" placeholder="Optional note">
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="ri-check-line me-1"></i> Record Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // ── District filter: auto-submit on select2 change ────────────────────
        var jq = window.jQuery;
        if (jq) {
            jq('#districtFilter').on('select2:select select2:clear', function() {
                document.getElementById('filterForm').submit();
            });
        }

        // ── Payment Modal: populate from row data ─────────────────────────────
        document.querySelectorAll('.pay-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var customerId   = this.dataset.customerId;
                var customerName = this.dataset.customerName;
                var totalDue     = parseFloat(this.dataset.totalDue);
                var wallet       = parseFloat(this.dataset.wallet);

                document.getElementById('modal_customer_id').value   = customerId;
                document.getElementById('modal_customer_name').textContent = customerName;
                document.getElementById('modal_due_display').textContent   = new Intl.NumberFormat('en-BD').format(totalDue) + ' ৳';
                document.getElementById('modal_wallet_display').textContent = new Intl.NumberFormat('en-BD').format(wallet);

                // Pre-fill amount with total due
                var amountInput = document.getElementById('modal_amount');
                amountInput.value = totalDue.toFixed(2);
                amountInput.max   = '';
            });
        });

        // ── Re-init Select2 inside modal after it opens (for payment method) ──
        var paymentModal = document.getElementById('paymentModal');
        if (paymentModal) {
            paymentModal.addEventListener('shown.bs.modal', function() {
                if (window.jQuery) {
                    window.jQuery('[data-toggle="select2"]', paymentModal).each(function() {
                        if (!window.jQuery(this).data('select2')) {
                            window.jQuery(this).select2({ dropdownParent: window.jQuery(paymentModal) });
                        }
                    });
                }
                // Focus amount field
                document.getElementById('modal_amount').focus();
            });
        }
    });
</script>
@endsection
