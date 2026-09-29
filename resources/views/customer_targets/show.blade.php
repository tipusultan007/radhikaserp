@extends('layouts.vertical', ['page_title' => 'Target Campaign Report & Disbursement', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('css')
<style>
    .progress {
        height: 12px;
        border-radius: 6px;
    }
    .customer-row:hover {
        background-color: #fcfdfe;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    {{-- Page Title --}}
    <div class="row">
        <div class="col-12">
            <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                <h4 class="page-title">{{ $customerTarget->name }}</h4>
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('customer-targets.index') }}">Customer Targets</a></li>
                    <li class="breadcrumb-item active">Report & Bonuses</li>
                </ol>
            </div>
        </div>
    </div>

    {{-- Alerts --}}
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

    @if (session('info'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="ri-information-line me-1"></i> {{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Campaign Summary Header Card --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h4 class="mb-0 fw-bold">{{ $customerTarget->name }}</h4>
                        @if ($customerTarget->status === 'active')
                            <span class="badge bg-success-subtle text-success fs-12">Active</span>
                        @elseif ($customerTarget->status === 'completed')
                            <span class="badge bg-secondary-subtle text-secondary fs-12">Completed</span>
                        @else
                            <span class="badge bg-danger-subtle text-danger fs-12">Cancelled</span>
                        @endif
                    </div>
                    <p class="text-muted mb-0 fs-13">
                        @if ($customerTarget->target_month)
                            <i class="ri-calendar-line me-1"></i> <strong>Month:</strong> {{ $customerTarget->target_month }}
                            <span class="mx-2">|</span>
                        @endif
                        <i class="ri-time-line me-1"></i> <strong>Period:</strong> {{ $customerTarget->start_date ? $customerTarget->start_date->format('d M, Y') : '' }} &mdash;
                        @if ($customerTarget->end_date)
                            {{ $customerTarget->end_date->format('d M, Y') }}
                        @else
                            <span class="badge bg-info-subtle text-info"><i class="ri-infinite-line me-1"></i> Ongoing (Until stopped)</span>
                        @endif
                        @if ($customerTarget->description)
                            <span class="mx-2">|</span>
                            <span class="text-secondary"><i class="ri-sticky-note-line me-1"></i> {{ $customerTarget->description }}</span>
                        @endif
                    </p>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <a href="{{ route('customer-targets.edit', $customerTarget->id) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="ri-edit-line me-1"></i> Edit Campaign
                    </a>
                    @if ($customerTarget->status === 'active')
                        <form action="{{ route('customer-targets.stop', $customerTarget->id) }}" method="POST" onsubmit="return confirm('Stop this campaign? Its end date will be set to today.');">
                            @csrf
                            <button type="submit" class="btn btn-outline-warning btn-sm">
                                <i class="ri-stop-circle-line me-1"></i> Stop Campaign
                            </button>
                        </form>
                    @endif
                    <form action="{{ route('customer-targets.disburse-all', $customerTarget->id) }}" method="POST" onsubmit="return confirm('Disburse bonus for all qualified customers who haven\'t received it yet? This will credit their wallet balances.');">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="ri-wallet-3-line me-1"></i> Disburse All Qualified
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Metrics Cards --}}
    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-start border-primary border-3 shadow-none mb-0">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted text-uppercase fs-12 mb-1">Participating Customers</h6>
                            <h3 class="my-0 fw-bold">{{ $report['total_participating'] }}</h3>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-20">
                                <i class="ri-user-shared-line"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-start border-success border-3 shadow-none mb-0">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted text-uppercase fs-12 mb-1">Target Fulfilled 🏆</h6>
                            <h3 class="my-0 fw-bold text-success">{{ $report['total_eligible'] }}</h3>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle text-success rounded-circle fs-20">
                                <i class="ri-trophy-line"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-start border-info border-3 shadow-none mb-0">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted text-uppercase fs-12 mb-1">Total Earned Bonus</h6>
                            <h3 class="my-0 fw-bold text-info">৳ {{ number_format($report['total_bonus_earned'], 2) }}</h3>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle text-info rounded-circle fs-20">
                                <i class="ri-coins-line"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-start border-warning border-3 shadow-none mb-0">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted text-uppercase fs-12 mb-1">Total Disbursed</h6>
                            <h3 class="my-0 fw-bold text-warning">৳ {{ number_format($report['total_bonus_disbursed'], 2) }}</h3>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-20">
                                <i class="ri-wallet-3-line"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Scheme Rules Info --}}
    <div class="card mb-3">
        <div class="card-header bg-light py-2" data-bs-toggle="collapse" href="#schemeRulesCollapse" role="button" aria-expanded="false" style="cursor: pointer;">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fs-13 fw-bold text-dark">
                    <i class="ri-list-check-2 me-1"></i> Qualifying Target Products & Rates ({{ $customerTarget->items->count() }} items)
                </h6>
                <span class="text-muted fs-12">Click to toggle</span>
            </div>
        </div>
        <div class="collapse show" id="schemeRulesCollapse">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>Variant</th>
                                <th>Monthly Target Qty</th>
                                <th>Bonus Rate / Unit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($customerTarget->items as $item)
                                <tr>
                                    <td>{{ $item->product ? $item->product->name : 'N/A' }}</td>
                                    <td>{{ $item->productVariant ? $item->productVariant->name : 'All Variants' }}</td>
                                    <td><strong>{{ number_format($item->target_qty, 2) }}</strong> {{ $item->product && $item->product->unit ? $item->product->unit->name : 'units' }}</td>
                                    <td class="text-success fw-bold">৳ {{ number_format($item->bonus_per_unit, 2) }} / unit</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Customer Achievements & Disbursement Table --}}
    <div class="card">
        <div class="card-header border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="card-title mb-0">Customer Purchase Progress & Bonus Status</h5>
            <form action="{{ route('customer-targets.show', $customerTarget->id) }}" method="GET" class="d-flex gap-2">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search customer..." value="{{ request('search') }}">
                <button type="submit" class="btn btn-primary btn-sm">Search</button>
                @if(request('search'))
                    <a href="{{ route('customer-targets.show', $customerTarget->id) }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                @endif
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 20%;">Customer</th>
                            <th style="width: 32%;">Product Purchases vs Targets</th>
                            <th style="width: 15%;">Progress</th>
                            <th style="width: 13%;">Bonus Amount</th>
                            <th style="width: 20%;" class="text-end">Status & Payout</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($report['customers'] as $cData)
                            @php
                                $customer = $cData['customer'];
                                $bonusRecord = $cData['bonus_record'];
                                $isDisbursed = $bonusRecord && $bonusRecord->status === 'disbursed';
                            @endphp
                            <tr class="customer-row">
                                <td>
                                    <a href="{{ route('customers.show', $customer->id) }}" class="fw-bold text-dark d-block">
                                        {{ $customer->name }}
                                    </a>
                                    <span class="text-muted fs-12">{{ $customer->phone }}</span>
                                    @if ($customer->company)
                                        <div class="text-muted fs-12">{{ $customer->company }}</div>
                                    @endif
                                    <div class="fs-11 text-secondary mt-1">
                                        Wallet: <span class="fw-semibold text-primary">৳ {{ number_format($customer->wallet_balance, 2) }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        @foreach ($cData['items_breakdown'] as $bItem)
                                            <div class="d-flex justify-content-between align-items-center fs-12 border-bottom pb-1">
                                                <span>
                                                    <strong>{{ $bItem['product_name'] }}</strong>
                                                    @if ($bItem['variant_name'] !== 'All Variants')
                                                        <span class="badge bg-light text-secondary border">{{ $bItem['variant_name'] }}</span>
                                                    @endif
                                                </span>
                                                <span>
                                                    <span class="{{ $bItem['is_target_met'] ? 'text-success fw-bold' : 'text-dark' }}">
                                                        {{ number_format($bItem['achieved_qty'], 2) }}
                                                    </span>
                                                    / {{ number_format($bItem['target_qty'], 2) }}
                                                    @if ($bItem['is_target_met'])
                                                        <i class="ri-checkbox-circle-fill text-success ms-1" title="Target Met"></i>
                                                    @endif
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <span class="fs-12 fw-bold {{ $cData['is_target_met'] ? 'text-success' : 'text-muted' }}">
                                            {{ $cData['progress_percent'] }}%
                                        </span>
                                        @if ($cData['is_target_met'])
                                            <span class="badge bg-success-subtle text-success fs-11">Met!</span>
                                        @else
                                            <span class="badge bg-light text-muted border fs-11">In Progress</span>
                                        @endif
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar {{ $cData['is_target_met'] ? 'bg-success' : 'bg-primary' }}" role="progressbar" style="width: {{ $cData['progress_percent'] }}%" aria-valuenow="{{ $cData['progress_percent'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </td>
                                <td>
                                    @if ($cData['is_target_met'])
                                        <span class="fs-15 fw-bold text-success">
                                            ৳ {{ number_format($cData['total_bonus_amount'], 2) }}
                                        </span>
                                    @else
                                        <span class="text-muted fs-13">
                                            ৳ 0.00
                                            <div class="fs-11 text-muted">(Target pending)</div>
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if ($isDisbursed)
                                        <div>
                                            <span class="badge bg-success py-1 px-2 fs-12">
                                                <i class="ri-check-double-line me-1"></i> Disbursed
                                            </span>
                                            <div class="fs-11 text-muted mt-1">
                                                {{ $bonusRecord->disbursed_at ? $bonusRecord->disbursed_at->format('d M Y, h:i A') : '' }}
                                            </div>
                                            @if ($bonusRecord->journal)
                                                <div class="fs-11 text-primary">
                                                    <i class="ri-file-list-3-line"></i> {{ $bonusRecord->journal->journal_no }}
                                                </div>
                                            @endif
                                        </div>
                                    @elseif ($cData['is_target_met'] && $cData['total_bonus_amount'] > 0)
                                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#disburseModal_{{ $customer->id }}">
                                            <i class="ri-wallet-3-line me-1"></i> Disburse ৳ {{ number_format($cData['total_bonus_amount'], 2) }}
                                        </button>

                                        {{-- Disburse Modal --}}
                                        <div class="modal fade" id="disburseModal_{{ $customer->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog text-start">
                                                <div class="modal-content">
                                                    <form action="{{ route('customer-targets.disburse', [$customerTarget->id, $customer->id]) }}" method="POST">
                                                        @csrf
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Disburse Bonus to Customer Wallet</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p class="mb-2">Are you sure you want to credit this bonus to <strong>{{ $customer->name }}</strong>'s wallet?</p>
                                                            
                                                            <div class="card bg-light border p-2 mb-3">
                                                                <div class="d-flex justify-content-between mb-1">
                                                                    <span>Bonus Amount:</span>
                                                                    <strong class="text-success fs-15">৳ {{ number_format($cData['total_bonus_amount'], 2) }}</strong>
                                                                </div>
                                                                <div class="d-flex justify-content-between mb-1">
                                                                    <span>Current Wallet Balance:</span>
                                                                    <span>৳ {{ number_format($customer->wallet_balance, 2) }}</span>
                                                                </div>
                                                                <div class="d-flex justify-content-between fw-bold border-top pt-1">
                                                                    <span>New Wallet Balance:</span>
                                                                    <span class="text-primary">৳ {{ number_format($customer->wallet_balance + $cData['total_bonus_amount'], 2) }}</span>
                                                                </div>
                                                            </div>

                                                            <div class="mb-2">
                                                                <label class="form-label fs-12">Disbursement Note (Optional)</label>
                                                                <input type="text" name="notes" class="form-control form-control-sm" placeholder="e.g. Approved incentive for September 2026">
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-success btn-sm">Confirm & Credit Wallet</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="badge bg-light text-muted border">
                                            Target Incomplete
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="ri-user-unfollow-line fs-24 d-block mb-1"></i>
                                    No customer purchases recorded for the qualifying products in this campaign period yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

