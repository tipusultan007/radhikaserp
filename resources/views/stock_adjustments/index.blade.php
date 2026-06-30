@extends('layouts.vertical', ['page_title' => 'Stock Adjustments', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                <h4 class="page-title">Stock Adjustments</h4>
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                    <li class="breadcrumb-item active">Adjustments</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-3 align-items-center">
                        <div class="col-sm-4">
                            <a href="{{ route('stock-adjustments.create') }}" class="btn btn-danger rounded-pill"><i class="ri-add-line me-1"></i> Request Adjustment</a>
                        </div>
                    </div>
                    
                    <div class="card bg-light mb-3 shadow-none border">
                        <div class="card-body py-2 px-3">
                            <form action="{{ route('stock-adjustments.index') }}" method="GET">
                                <div class="row gy-2 gx-2 align-items-end">
                                    <div class="col-md-2">
                                        <label class="form-label mb-1">Start Date</label>
                                        <input type="text" class="form-control flatpickr-date" name="start_date" value="{{ request('start_date') }}" placeholder="YYYY-MM-DD">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label mb-1">End Date</label>
                                        <input type="text" class="form-control flatpickr-date" name="end_date" value="{{ request('end_date') }}" placeholder="YYYY-MM-DD">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label mb-1">Warehouse</label>
                                        <select class="form-select" name="warehouse_id">
                                            <option value="">Any Warehouse</option>
                                            @foreach($warehouses as $wh)
                                                <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label mb-1">Status</label>
                                        <select class="form-select" name="status">
                                            <option value="">All Statuses</option>
                                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line me-1"></i> Filter</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    <div class="table-responsive">
                        <table class="table table-centered table-striped dt-responsive nowrap w-100">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Warehouse</th>
                                    <th>Batch / Product</th>
                                    <th>Type</th>
                                    <th>Qty</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($adjustments as $adj)
                                <tr>
                                    <td>{{ $adj->created_at->format('Y-m-d') }}</td>
                                    <td>{{ $adj->warehouse->name }}</td>
                                    <td>{{ $adj->batch->batch_no ?? 'N/A' }} <br> <small>{{ $adj->product->name ?? '' }}</small></td>
                                    <td>
                                        <span class="badge {{ $adj->type == 'add' ? 'bg-success' : 'bg-danger' }}">
                                            {{ strtoupper($adj->type) }}
                                        </span>
                                    </td>
                                    <td>{{ number_format($adj->qty, 3) }}</td>
                                    <td>
                                        @if($adj->status == 'pending')
                                            <span class="badge bg-warning text-dark">Pending Approval</span>
                                        @elseif($adj->status == 'approved')
                                            <span class="badge bg-success">Approved</span>
                                        @else
                                            <span class="badge bg-secondary">Rejected</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($adj->status == 'pending')
                                        <form action="{{ route('stock-adjustments.updateStatus', $adj) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" name="action" value="approve" class="btn btn-sm btn-success">Approve</button>
                                            <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger">Reject</button>
                                        </form>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        {{ $adjustments->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
