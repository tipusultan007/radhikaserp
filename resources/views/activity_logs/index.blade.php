@extends('layouts.vertical', ['page_title' => 'Activity Logs', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('content')
<div class="container-fluid">
    <!-- Page Title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                <h4 class="page-title"><i class="ri-history-line me-1"></i> Activity Logs</h4>
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                    <li class="breadcrumb-item active">Activity Logs</li>
                </ol>
            </div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card mb-0">
                <div class="card-body">
                    <form action="{{ route('activity-logs.index') }}" method="GET" class="row g-2 align-items-center">
                        <div class="col-md-5">
                            <div class="input-group">
                                <span class="input-group-text"><i class="ri-search-line"></i></span>
                                <input type="text" name="search" class="form-control" placeholder="Search by description, model, or user..." value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select name="action" class="form-select">
                                <option value="">-- All Actions --</option>
                                <option value="created" {{ request('action') == 'created' ? 'selected' : '' }}>CREATED</option>
                                <option value="updated" {{ request('action') == 'updated' ? 'selected' : '' }}>UPDATED</option>
                                <option value="deleted" {{ request('action') == 'deleted' ? 'selected' : '' }}>DELETED</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="ri-filter-3-line me-1"></i> Filter</button>
                            @if(request('search') || request('action'))
                                <a href="{{ route('activity-logs.index') }}" class="btn btn-light"><i class="ri-refresh-line me-1"></i> Clear</a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="ri-shield-user-line me-1"></i> Audit Trail Records</h5>
                    <span class="badge bg-soft-info text-info">Total Logs: {{ $logs->total() }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-centered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 45px;"></th>
                                    <th>Date & Time</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Entity / Model</th>
                                    <th>Description Summary</th>
                                    <th class="text-end" style="width: 110px;">Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($logs as $log)
                                <tr>
                                    <td class="text-center">
                                        <button class="btn btn-xs btn-outline-secondary rounded-circle" type="button" data-bs-toggle="collapse" data-bs-target="#log-details-{{ $log->id }}" aria-expanded="false" aria-controls="log-details-{{ $log->id }}">
                                            <i class="ri-add-line collapse-icon-{{ $log->id }}"></i>
                                        </button>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $log->created_at->format('Y-m-d H:i:s') }}</div>
                                        <small class="text-muted">{{ $log->created_at->diffForHumans() }}</small>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-dark">{{ $log->user->name ?? 'System' }}</span>
                                        @if(isset($log->user->email))
                                            <br><small class="text-muted">{{ $log->user->email }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $action = strtolower($log->action);
                                            $badgeClass = match($action) {
                                                'created' => 'bg-success',
                                                'updated' => 'bg-primary',
                                                'deleted' => 'bg-danger',
                                                default => 'bg-secondary'
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">{{ strtoupper($log->action) }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-soft-secondary text-secondary">
                                            {{ class_basename($log->reference_type) }} #{{ $log->reference_id }}
                                        </span>
                                    </td>
                                    <td>
                                        <span>{{ Str::limit($log->description, 60) }}</span>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-soft-primary" data-bs-toggle="collapse" data-bs-target="#log-details-{{ $log->id }}">
                                            <i class="ri-eye-line me-1"></i> Expand
                                        </button>
                                    </td>
                                </tr>
                                <!-- Expandable Detail Row -->
                                <tr class="collapse bg-light" id="log-details-{{ $log->id }}">
                                    <td colspan="7" class="p-3">
                                        <div class="card card-body mb-0 border shadow-none bg-white">
                                            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                                <div class="d-flex align-items-center gap-2">
                                                    <h6 class="fw-bold text-primary mb-0">
                                                        <i class="ri-file-text-line me-1"></i> Audit Trail Details — #{{ $log->id }}
                                                    </h6>
                                                    <span class="badge {{ $badgeClass }}">{{ strtoupper($log->action) }}</span>
                                                    <span class="badge bg-soft-secondary text-dark">{{ class_basename($log->reference_type) }} #{{ $log->reference_id }}</span>
                                                </div>
                                                <span class="badge bg-light text-muted border">
                                                    <i class="ri-time-line me-1"></i> {{ $log->created_at->format('F j, Y - g:i:s A') }}
                                                </span>
                                            </div>

                                            <!-- Metadata Cards -->
                                            <div class="row g-3 mb-3">
                                                <div class="col-md-6">
                                                    <div class="fw-bold text-dark mb-1"><i class="ri-information-line me-1"></i> Summary / Activity:</div>
                                                    <div class="p-2 rounded bg-light border text-break fs-13">
                                                        {{ $log->description ?? 'No description record available.' }}
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="fw-bold text-dark mb-1"><i class="ri-database-2-line me-1"></i> Entity Info:</div>
                                                    <div class="p-2 rounded bg-light border fs-13">
                                                        <div><strong>Model:</strong> <code>{{ $log->reference_type }}</code></div>
                                                        <div><strong>Record ID:</strong> #{{ $log->reference_id }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="fw-bold text-dark mb-1"><i class="ri-user-3-line me-1"></i> Performed By:</div>
                                                    <div class="p-2 rounded bg-light border fs-13">
                                                        <div><strong>User:</strong> {{ $log->user->name ?? 'System' }}</div>
                                                        <div><strong>User ID:</strong> {{ $log->user_id ?? 'N/A' }}</div>
                                                        @if(isset($log->user->email))
                                                            <div><strong>Email:</strong> {{ $log->user->email }}</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            @php
                                                $props = $log->properties;
                                                $hasProps = !empty($props) && is_array($props);
                                                $actionType = strtolower($log->action);

                                                $formatKey = function($key) {
                                                    $acronyms = ['id' => 'ID', 'erp' => 'ERP', 'kg' => 'KG', 'sku' => 'SKU', 'url' => 'URL', 'ip' => 'IP', 'qty' => 'Quantity'];
                                                    $customLabels = [
                                                        'customer_id' => 'Customer ID',
                                                        'warehouse_id' => 'Warehouse ID',
                                                        'user_id' => 'User ID',
                                                        'product_id' => 'Product ID',
                                                        'product_variant_id' => 'Product Variant ID',
                                                        'supplier_id' => 'Supplier ID',
                                                        'created_by' => 'Created By (User ID)',
                                                        'payment_method_id' => 'Payment Method ID',
                                                        'chart_of_account_id' => 'Account ID',
                                                        'is_promotional' => 'Promotional Sale',
                                                        'is_active' => 'Active Status',
                                                        'is_payment_method' => 'Is Payment Method',
                                                        'delivery_charge' => 'Delivery Charge',
                                                        'delivery_method' => 'Delivery Method',
                                                        'delivery_type' => 'Delivery Type',
                                                        'delivery_status' => 'Delivery Status',
                                                        'invoice_no' => 'Invoice Number',
                                                        'total_weight' => 'Total Weight (KG)',
                                                        'opening_balance' => 'Opening Balance',
                                                        'wallet_balance' => 'Wallet Balance',
                                                        'due_balance' => 'Due Balance',
                                                        'paid_amount' => 'Paid Amount',
                                                        'due_amount' => 'Due Amount',
                                                    ];
                                                    if (isset($customLabels[$key])) {
                                                        return $customLabels[$key];
                                                    }
                                                    $words = explode('_', strtolower($key));
                                                    foreach ($words as &$word) {
                                                        if (isset($acronyms[$word])) {
                                                            $word = $acronyms[$word];
                                                        } else {
                                                            $word = ucfirst($word);
                                                        }
                                                    }
                                                    return implode(' ', $words);
                                                };

                                                $formatValue = function($key, $val) {
                                                    if ($val === null || $val === '') {
                                                        return '<span class="text-muted fst-italic">— None / Empty —</span>';
                                                    }
                                                    if (is_bool($val) || in_array($key, ['is_promotional', 'is_active', 'is_payment_method'])) {
                                                        return ($val == 1 || $val === true || $val === '1')
                                                            ? '<span class="badge bg-soft-success text-success px-2 py-1"><i class="ri-check-line"></i> Yes</span>'
                                                            : '<span class="badge bg-soft-secondary text-secondary px-2 py-1"><i class="ri-close-line"></i> No</span>';
                                                    }
                                                    if (in_array($key, ['payment_status', 'delivery_status', 'status'])) {
                                                        $valStr = strtolower((string)$val);
                                                        $badgeClass = match($valStr) {
                                                            'paid', 'delivered', 'active', 'completed', 'approved' => 'bg-success',
                                                            'partial', 'dispatched', 'processing', 'accepted' => 'bg-info',
                                                            'due', 'pending', 'draft' => 'bg-warning text-dark',
                                                            'cancelled', 'rejected', 'inactive', 'failed' => 'bg-danger',
                                                            default => 'bg-secondary'
                                                        };
                                                        return '<span class="badge ' . $badgeClass . ' text-uppercase px-2 py-1">' . e(ucfirst($val)) . '</span>';
                                                    }
                                                    if (in_array($key, ['total', 'subtotal', 'discount', 'paid_amount', 'due_amount', 'amount', 'salary', 'opening_balance', 'wallet_balance', 'due_balance', 'delivery_charge', 'unit_price', 'purchase_price', 'sale_price']) && is_numeric($val)) {
                                                        return '<span class="fw-bold text-dark font-monospace">৳ ' . number_format((float)$val, 2) . '</span>';
                                                    }
                                                    if (in_array($key, ['total_weight', 'weight']) && is_numeric($val)) {
                                                        return '<span class="font-monospace fw-semibold text-dark">' . number_format((float)$val, 3) . ' KG</span>';
                                                    }
                                                    if (in_array($key, ['created_at', 'updated_at', 'deleted_at', 'dispatched_at', 'delivered_at', 'date', 'payment_date', 'estimate_delivery_date']) && is_string($val) && strtotime($val)) {
                                                        $hasTime = strlen($val) > 10;
                                                        $format = $hasTime ? 'M d, Y - h:i A' : 'M d, Y';
                                                        return '<span class="text-dark"><i class="ri-calendar-event-line me-1 text-primary"></i>' . date($format, strtotime($val)) . '</span>';
                                                    }
                                                    if (is_array($val)) {
                                                        return '<pre class="m-0 fs-12 p-1 bg-light rounded text-muted font-monospace">' . e(json_encode($val, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
                                                    }
                                                    return '<span class="text-dark fw-medium">' . e((string)$val) . '</span>';
                                                };
                                            @endphp

                                            @if($hasProps)
                                                <!-- Structured Data Viewer -->
                                                <div class="mt-2">
                                                    @if($actionType === 'updated' && (isset($props['old']) || isset($props['attributes'])))
                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <h6 class="fw-bold text-dark mb-0">
                                                                <i class="ri-git-commit-line text-primary me-1"></i> Field-by-Field Modified Changes:
                                                            </h6>
                                                            <button class="btn btn-xs btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#raw-json-{{ $log->id }}">
                                                                <i class="ri-code-line me-1"></i> View Raw JSON
                                                            </button>
                                                        </div>
                                                        <div class="table-responsive">
                                                            <table class="table table-sm table-bordered table-hover mb-2 fs-13 align-middle">
                                                                <thead class="table-light">
                                                                    <tr>
                                                                        <th style="width: 25%;">Field</th>
                                                                        <th style="width: 37.5%;" class="text-danger"><i class="ri-subtract-line me-1"></i> Previous Value (Before)</th>
                                                                        <th style="width: 37.5%;" class="text-success"><i class="ri-add-line me-1"></i> Updated Value (After)</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @php
                                                                        $allKeys = array_unique(array_merge(array_keys($props['old'] ?? []), array_keys($props['attributes'] ?? [])));
                                                                    @endphp
                                                                    @foreach($allKeys as $key)
                                                                        <tr>
                                                                            <td class="fw-semibold text-dark">
                                                                                {{ $formatKey($key) }}
                                                                                <div class="text-muted fs-11 font-monospace">{{ $key }}</div>
                                                                            </td>
                                                                            <td class="bg-soft-danger">{!! $formatValue($key, $props['old'][$key] ?? null) !!}</td>
                                                                            <td class="bg-soft-success">{!! $formatValue($key, $props['attributes'][$key] ?? null) !!}</td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>

                                                    @elseif($actionType === 'created' && isset($props['attributes']))
                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <h6 class="fw-bold text-success mb-0">
                                                                <i class="ri-file-list-3-line me-1"></i> Created Record Snapshot (All Values):
                                                            </h6>
                                                            <button class="btn btn-xs btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#raw-json-{{ $log->id }}">
                                                                <i class="ri-code-line me-1"></i> View Raw JSON
                                                            </button>
                                                        </div>
                                                        <div class="row g-2 mb-2">
                                                            @foreach($props['attributes'] as $attrKey => $attrVal)
                                                                <div class="col-lg-3 col-md-4 col-sm-6">
                                                                    <div class="p-2 border rounded bg-light-subtle h-100 d-flex flex-column justify-content-between">
                                                                        <div>
                                                                            <div class="text-muted fs-11 fw-bold text-uppercase">{{ $formatKey($attrKey) }}</div>
                                                                            <div class="text-muted fs-10 font-monospace mb-1">{{ $attrKey }}</div>
                                                                        </div>
                                                                        <div class="fs-13 text-break">{!! $formatValue($attrKey, $attrVal) !!}</div>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>

                                                    @elseif($actionType === 'deleted' && (isset($props['old']) || isset($props['attributes'])))
                                                        @php
                                                            $delData = $props['old'] ?? ($props['attributes'] ?? []);
                                                        @endphp
                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <h6 class="fw-bold text-danger mb-0">
                                                                <i class="ri-delete-bin-line me-1"></i> Deleted Record Snapshot (Preserved Data at Deletion):
                                                            </h6>
                                                            <button class="btn btn-xs btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#raw-json-{{ $log->id }}">
                                                                <i class="ri-code-line me-1"></i> View Raw JSON
                                                            </button>
                                                        </div>
                                                        <div class="row g-2 mb-2">
                                                            @foreach($delData as $attrKey => $attrVal)
                                                                <div class="col-lg-3 col-md-4 col-sm-6">
                                                                    <div class="p-2 border border-danger-subtle rounded bg-soft-danger bg-opacity-25 h-100 d-flex flex-column justify-content-between">
                                                                        <div>
                                                                            <div class="text-danger fs-11 fw-bold text-uppercase">{{ $formatKey($attrKey) }}</div>
                                                                            <div class="text-muted fs-10 font-monospace mb-1">{{ $attrKey }}</div>
                                                                        </div>
                                                                        <div class="fs-13 text-break">{!! $formatValue($attrKey, $attrVal) !!}</div>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <h6 class="fw-bold text-dark mb-0"><i class="ri-code-line me-1"></i> Log Payload:</h6>
                                                        </div>
                                                        <pre class="p-2 rounded bg-light border font-monospace fs-12 mb-0" style="max-height: 250px; overflow-y: auto;">{{ json_encode($props, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                                    @endif

                                                    <!-- Raw JSON View (Collapsible) -->
                                                    <div class="collapse mt-2" id="raw-json-{{ $log->id }}">
                                                        <div class="card card-body mb-0 bg-light border p-2">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <small class="fw-bold text-muted"><i class="ri-braces-line me-1"></i> RAW JSON PAYLOAD:</small>
                                                            </div>
                                                            <pre class="m-0 font-monospace fs-12" style="max-height: 250px; overflow-y: auto;">{{ json_encode($props, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No activity log records found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($logs->hasPages())
                <div class="card-footer py-2">
                    <div class="d-flex justify-content-end">
                        {{ $logs->links() }}
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var collapseRows = document.querySelectorAll('.collapse');
    collapseRows.forEach(function (el) {
        el.addEventListener('show.bs.collapse', function () {
            var logId = this.id.replace('log-details-', '');
            var icon = document.querySelector('.collapse-icon-' + logId);
            if (icon) {
                icon.classList.remove('ri-add-line');
                icon.classList.add('ri-subtract-line');
            }
        });
        el.addEventListener('hide.bs.collapse', function () {
            var logId = this.id.replace('log-details-', '');
            var icon = document.querySelector('.collapse-icon-' + logId);
            if (icon) {
                icon.classList.remove('ri-subtract-line');
                icon.classList.add('ri-add-line');
            }
        });
    });
});
</script>
@endsection
