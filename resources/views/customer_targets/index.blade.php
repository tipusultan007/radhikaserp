@extends('layouts.vertical', ['page_title' => 'Customer Monthly Targets & Bonuses', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('css')
<style>
    .stat-card {
        border-radius: 10px;
        transition: transform 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-2px);
    }
</style>
@endsection

@section('content')
    <div class="container-fluid">
        {{-- Page Title --}}
        <div class="row">
            <div class="col-12">
                <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                    <h4 class="page-title">Customer Monthly Targets & Bonuses</h4>
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Customers</a></li>
                        <li class="breadcrumb-item active">Monthly Targets</li>
                    </ol>
                </div>
            </div>
        </div>

        {{-- Flash Messages --}}
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

        {{-- Statistics Row --}}
        <div class="row g-3 mb-3">
            <div class="col-sm-6 col-xl-3">
                <div class="card stat-card border-start border-primary border-3 shadow-none mb-0">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-muted text-uppercase fs-12 mb-1">Total Campaigns</h6>
                                <h3 class="my-0 fw-bold">{{ $stats['total'] }}</h3>
                            </div>
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-20">
                                    <i class="ri-flag-line"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="card stat-card border-start border-success border-3 shadow-none mb-0">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-muted text-uppercase fs-12 mb-1">Active Schemes</h6>
                                <h3 class="my-0 fw-bold text-success">{{ $stats['active'] }}</h3>
                            </div>
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-success-subtle text-success rounded-circle fs-20">
                                    <i class="ri-checkbox-circle-line"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="card stat-card border-start border-info border-3 shadow-none mb-0">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-muted text-uppercase fs-12 mb-1">Current Month Schemes</h6>
                                <h3 class="my-0 fw-bold text-info">{{ $stats['current_month'] }}</h3>
                            </div>
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-info-subtle text-info rounded-circle fs-20">
                                    <i class="ri-calendar-event-line"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="card stat-card border-start border-warning border-3 shadow-none mb-0">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-muted text-uppercase fs-12 mb-1">Total Bonus Disbursed</h6>
                                <h3 class="my-0 fw-bold text-warning">৳ {{ number_format($stats['total_disbursed'], 2) }}</h3>
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

        {{-- Main Content Card --}}
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h5 class="card-title mb-0">Monthly Purchase Incentive Campaigns</h5>
                        <div>
                            <a href="{{ route('customer-targets.create') }}" class="btn btn-primary btn-sm">
                                <i class="ri-add-line me-1"></i> Create Target Campaign
                            </a>
                        </div>
                    </div>

                    <div class="card-body">
                        {{-- Filters --}}
                        <form action="{{ route('customer-targets.index') }}" method="GET" class="mb-3">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label fs-12">Search Campaign</label>
                                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by name..." value="{{ request('search') }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fs-12">Target Month</label>
                                    <input type="month" name="month" class="form-control form-control-sm" value="{{ request('month') }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fs-12">Status</label>
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="">All Statuses</option>
                                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                    </select>
                                </div>
                                <div class="col-md-3 d-flex gap-2">
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="ri-filter-line me-1"></i> Filter
                                    </button>
                                    <a href="{{ route('customer-targets.index') }}" class="btn btn-outline-secondary btn-sm">
                                        Reset
                                    </a>
                                </div>
                            </div>
                        </form>

                        {{-- Table --}}
                        <div class="table-responsive">
                            <table class="table table-hover table-centered mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Campaign Name</th>
                                        <th>Target Month</th>
                                        <th>Period</th>
                                        <th>Qualifying Products</th>
                                        <th>Status</th>
                                        <th>Created By</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($schemes as $scheme)
                                        <tr>
                                            <td>
                                                <a href="{{ route('customer-targets.show', $scheme->id) }}" class="fw-bold text-primary">
                                                    {{ $scheme->name }}
                                                </a>
                                                @if($scheme->description)
                                                    <div class="text-muted fs-12 text-truncate" style="max-width: 280px;">{{ $scheme->description }}</div>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($scheme->target_month)
                                                    <span class="badge bg-light text-dark border">
                                                        <i class="ri-calendar-line me-1"></i> {{ $scheme->target_month }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-light text-muted border">Custom Period</span>
                                                @endif
                                            </td>
                                            <td class="fs-12">
                                                {{ $scheme->start_date ? $scheme->start_date->format('d M, Y') : '' }} &mdash;
                                                @if($scheme->end_date)
                                                    {{ $scheme->end_date->format('d M, Y') }}
                                                @else
                                                    <span class="badge bg-info-subtle text-info"><i class="ri-infinite-line me-1"></i> Ongoing (Until stopped)</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-info-subtle text-info">
                                                    <i class="ri-archive-line me-1"></i> {{ $scheme->items_count }} Product{{ $scheme->items_count > 1 ? 's' : '' }}
                                                </span>
                                            </td>
                                            <td>
                                                @if ($scheme->status === 'active')
                                                    <span class="badge bg-success-subtle text-success">Active</span>
                                                @elseif ($scheme->status === 'completed')
                                                    <span class="badge bg-secondary-subtle text-secondary">Completed</span>
                                                @else
                                                    <span class="badge bg-danger-subtle text-danger">Cancelled</span>
                                                @endif
                                            </td>
                                            <td class="fs-12">
                                                {{ $scheme->creator ? $scheme->creator->name : 'System' }}
                                            </td>
                                            <td class="text-end">
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                        Actions
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a class="dropdown-item text-primary" href="{{ route('customer-targets.show', $scheme->id) }}">
                                                                <i class="ri-bar-chart-box-line me-2"></i> View Report & Bonuses
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('customer-targets.edit', $scheme->id) }}">
                                                                <i class="ri-edit-line me-2"></i> Edit Campaign
                                                            </a>
                                                        </li>
                                                        @if ($scheme->status === 'active')
                                                            <li>
                                                                <form action="{{ route('customer-targets.stop', $scheme->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to stop this campaign? Its end date will be set to today.');">
                                                                    @csrf
                                                                    <button type="submit" class="dropdown-item text-warning">
                                                                        <i class="ri-stop-circle-line me-2"></i> Stop Campaign
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        @endif
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <form action="{{ route('customer-targets.destroy', $scheme->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this campaign?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="dropdown-item text-danger">
                                                                    <i class="ri-delete-bin-line me-2"></i> Delete
                                                                </button>
                                                            </form>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">
                                                <i class="ri-inbox-line fs-24 d-block mb-1"></i>
                                                No customer target campaigns found. Click <strong>"Create Target Campaign"</strong> to get started!
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{-- Pagination --}}
                        <div class="mt-3">
                            {{ $schemes->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

