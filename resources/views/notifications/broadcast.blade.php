@extends('layouts.vertical', ['page_title' => 'Push Notifications Broadcast', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('css')
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container .select2-selection--single {
        height: 38px !important;
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 38px;
        padding-left: 12px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">

    <!-- Page Title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <form action="{{ route('push-notifications.trigger-dues') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-warning shadow-sm me-1" onclick="return confirm('Send automated due reminder push notifications to all customers with pending balance now?')">
                            <i class="ri-alarm-warning-line me-1"></i> Send Due Reminders Now
                        </button>
                    </form>
                </div>
                <h4 class="page-title"><i class="ri-notification-3-line me-1"></i> Customer Push Notifications</h4>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-checkbox-circle-fill me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-fill me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <!-- Notification Form -->
        <div class="col-lg-5 col-xl-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title text-white mb-0"><i class="ri-send-plane-fill me-1"></i> Send Push Notification</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('push-notifications.send') }}" method="POST">
                        @csrf

                        <!-- Target Audience -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Target Audience <span class="text-danger">*</span></label>
                            <select name="target" id="targetSelect" class="form-select select2" data-toggle="select2" required>
                                <option value="all">📢 All Customers (Broadcast Offer/Promo)</option>
                                <option value="specific">👤 Specific Customer</option>
                            </select>
                        </div>

                        <!-- Specific Customer Selection -->
                        <div class="mb-3 d-none" id="customerSelectWrapper">
                            <label class="form-label fw-bold">Select Customer <span class="text-danger">*</span></label>
                            <select name="customer_id" id="customerSelect" class="form-select select2" data-toggle="select2">
                                <option value="">-- Choose Customer --</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->phone }}) - Due: ৳{{ number_format($customer->total_due, 2) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Notification Type -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Type <span class="text-danger">*</span></label>
                            <select name="type" id="typeSelect" class="form-select select2" data-toggle="select2" required>
                                <option value="promotion">🏷️ Promotion / Offer</option>
                                <option value="announcement">📢 Announcement</option>
                                <option value="due_reminder">🔔 Payment / Due Reminder</option>
                                <option value="info">ℹ️ General Info</option>
                            </select>
                        </div>

                        <!-- Title -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Notification Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Special 20% Offer Today!" required>
                        </div>

                        <!-- Message -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Notification Body / Message <span class="text-danger">*</span></label>
                            <textarea name="message" class="form-control" rows="4" placeholder="Enter notification text that will pop up on customer phones..." required></textarea>
                            <small class="text-muted">This message will be delivered as a Push Notification banner even if the customer app is closed.</small>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="ri-send-plane-2-line me-1"></i> Dispatch Push Notification
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- History & Status Table -->
        <div class="col-lg-7 col-xl-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="ri-history-line me-1"></i> Recent Sent Push Notifications</h5>
                    <span class="badge bg-soft-info text-info">Total Dispatched: {{ $recentNotifications->total() }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-centered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Recipients</th>
                                    <th>Type</th>
                                    <th>Title & Message</th>
                                    <th>Sent At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentNotifications as $notif)
                                    <tr>
                                        <td>
                                            <span class="fw-semibold">{{ $notif->customer_name }}</span>
                                        </td>
                                        <td>
                                            @php
                                                $type = $notif->data['type'] ?? 'info';
                                                $badgeClass = match($type) {
                                                    'promotion' => 'bg-success',
                                                    'announcement' => 'bg-primary',
                                                    'due_reminder' => 'bg-warning text-dark',
                                                    default => 'bg-secondary'
                                                };
                                            @endphp
                                            <span class="badge {{ $badgeClass }}">{{ ucfirst(str_replace('_', ' ', $type)) }}</span>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark">{{ $notif->data['title'] ?? 'N/A' }}</div>
                                            <small class="text-muted">{{ Str::limit($notif->data['message'] ?? '', 80) }}</small>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ \Carbon\Carbon::parse($notif->created_at)->diffForHumans() }}</small>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No recent push notifications found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($recentNotifications->hasPages())
                <div class="card-footer py-2">
                    <div class="d-flex justify-content-end">
                        {{ $recentNotifications->links() }}
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<!-- jQuery & Select2 JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize Select2 on all dropdowns
    $('.select2').select2({
        width: '100%'
    });

    // Handle toggle for target audience select
    $('#targetSelect').on('change', function() {
        var target = $(this).val();
        var wrapper = $('#customerSelectWrapper');
        if (target === 'specific') {
            wrapper.removeClass('d-none');
        } else {
            wrapper.addClass('d-none');
        }
    });
});
</script>
@endsection
