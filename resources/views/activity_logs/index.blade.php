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
                                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                                <h6 class="fw-bold text-primary mb-0">
                                                    <i class="ri-file-text-line me-1"></i> Full Log Details — ID #{{ $log->id }}
                                                </h6>
                                                <span class="badge bg-light text-dark">Timestamp: {{ $log->created_at->format('F j, Y - g:i:s A') }}</span>
                                            </div>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <div class="fw-bold text-dark mb-1"><i class="ri-chat-3-line me-1"></i> Full Description:</div>
                                                    <div class="p-2 rounded bg-light border text-break font-monospace fs-13" style="white-space: pre-wrap; max-height: 200px; overflow-y: auto;">
                                                        {{ $log->description ?? 'No description record available.' }}
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="fw-bold text-dark mb-1"><i class="ri-database-2-line me-1"></i> Target Entity:</div>
                                                    <div class="p-2 rounded bg-light border fs-13">
                                                        <div><strong>Class:</strong> <code>{{ $log->reference_type }}</code></div>
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
