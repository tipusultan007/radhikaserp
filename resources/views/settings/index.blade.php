@extends('layouts.vertical', ['page_title' => 'System Settings', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                <h4 class="page-title"><i class="ri-settings-4-line me-1"></i> System Settings</h4>
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                    <li class="breadcrumb-item active">Settings</li>
                </ol>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8 col-xl-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light border-bottom">
                    <h5 class="card-title mb-0 d-flex align-items-center">
                        <i class="ri-shield-check-fill text-primary me-2 fs-4"></i>
                        Customer Credit Limit Configuration
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('settings.update') }}" method="POST">
                        @csrf

                        {{-- Setting 1: Enable Credit Limit --}}
                        <div class="p-3 rounded border mb-3 bg-light bg-opacity-50">
                            <div class="d-flex align-items-start justify-content-between">
                                <div class="me-3">
                                    <h6 class="fw-bold mb-1 fs-14">
                                        <i class="ri-toggle-line text-primary me-1"></i> Enable Customer Credit Limit Enforcement
                                    </h6>
                                    <p class="text-muted font-13 mb-0">
                                        When <strong>enabled</strong>, the system blocks sales and POS orders if the transaction causes a customer's total due to exceed their configured credit limit.
                                    </p>
                                    <p class="text-muted font-13 mb-0 mt-1">
                                        When <strong>disabled</strong>, all orders and sales proceed normally without any credit limit restrictions.
                                    </p>
                                </div>
                                <div class="form-check form-switch form-switch-lg mt-1">
                                    <input type="checkbox" class="form-check-input" id="enable_customer_credit_limit" 
                                           name="enable_customer_credit_limit" value="1" 
                                           {{ $enableCreditLimit ? 'checked' : '' }} style="cursor: pointer; transform: scale(1.3);">
                                </div>
                            </div>
                            <div class="mt-2 pt-2 border-top">
                                <span class="badge {{ $enableCreditLimit ? 'bg-soft-success text-success border border-success' : 'bg-soft-secondary text-secondary' }} px-2 py-1 font-12">
                                    Current Status: <strong>{{ $enableCreditLimit ? 'ACTIVE & ENFORCED' : 'DISABLED (NO LIMIT RESTRICTIONS)' }}</strong>
                                </span>
                            </div>
                        </div>

                        {{-- Setting 2: Strict Zero Mode --}}
                        <div class="p-3 rounded border mb-4 bg-light bg-opacity-50">
                            <div class="d-flex align-items-start justify-content-between">
                                <div class="me-3">
                                    <h6 class="fw-bold mb-1 fs-14">
                                        <i class="ri-prohibited-line text-warning me-1"></i> Strict Zero Limit Mode (No Credit for 0 Limit)
                                    </h6>
                                    <p class="text-muted font-13 mb-0">
                                        If <strong>checked</strong>, any customer with a credit limit of <code>৳ 0.00</code> will be strictly forbidden from purchasing on credit (must pay 100% upfront).
                                    </p>
                                    <p class="text-muted font-13 mb-0 mt-1">
                                        If <strong>unchecked (recommended)</strong>, customers with <code>৳ 0.00</code> credit limit have unconstrained credit until you explicitly set a specific limit (e.g. ৳ 50,000) on their profile.
                                    </p>
                                </div>
                                <div class="form-check form-switch form-switch-lg mt-1">
                                    <input type="checkbox" class="form-check-input" id="credit_limit_strict_zero" 
                                           name="credit_limit_strict_zero" value="1" 
                                           {{ $strictZero ? 'checked' : '' }} style="cursor: pointer; transform: scale(1.3);">
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('customers.index') }}" class="btn btn-light"><i class="ri-arrow-left-line me-1"></i> Back to Customers</a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="ri-save-3-line me-1"></i> Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-xl-5">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light border-bottom">
                    <h5 class="card-title mb-0"><i class="ri-information-line text-info me-1"></i> How Credit Limits Work</h5>
                </div>
                <div class="card-body font-13 text-muted">
                    <ul class="ps-3 mb-0">
                        <li class="mb-2">
                            <strong>Setting Limits:</strong> Go to <a href="{{ route('customers.index') }}">Customers</a> &rarr; Edit a customer &rarr; set <strong>Credit Limit (in TK)</strong>.
                        </li>
                        <li class="mb-2">
                            <strong>Formula:</strong> <code>Projected Due = Current Due + Sale Due Amount</code>. If <code>Projected Due &gt; Credit Limit</code>, the sale is blocked with an informative message.
                        </li>
                        <li class="mb-2">
                            <strong>Wallet Payments:</strong> If a customer pays using their advance wallet, only the unpaid due portion is counted against their credit limit.
                        </li>
                        <li>
                            <strong>Switch Anytime:</strong> You can turn this enforcement on or off at any moment without affecting existing records.
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
