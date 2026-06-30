@extends('layouts.vertical', ['page_title' => 'Stock Transfers', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                <h4 class="page-title">Stock Transfers</h4>
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                    <li class="breadcrumb-item active">Transfers</li>
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
                            <a href="{{ route('stock-transfers.create') }}" class="btn btn-danger rounded-pill"><i class="ri-add-line me-1"></i> New Transfer</a>
                        </div>
                    </div>
                    
                    <div class="card bg-light mb-3 shadow-none border">
                        <div class="card-body py-2 px-3">
                            <form action="{{ route('stock-transfers.index') }}" method="GET">
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
                                        <label class="form-label mb-1">Transfer No</label>
                                        <input type="text" class="form-control" name="transfer_no" value="{{ request('transfer_no') }}" placeholder="e.g. TRF-123">
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
                                    <th>Transfer No</th>
                                    <th>Date</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($transfers as $transfer)
                                <tr>
                                    <td>{{ $transfer->transfer_no }}</td>
                                    <td>{{ $transfer->created_at->format('Y-m-d') }}</td>
                                    <td>{{ $transfer->fromWarehouse->name }}</td>
                                    <td>{{ $transfer->toWarehouse->name }}</td>
                                    <td>
                                        @if($transfer->status == 'draft')
                                            <span class="badge bg-secondary">Draft</span>
                                        @elseif($transfer->status == 'sent')
                                            <span class="badge bg-warning">Sent</span>
                                        @else
                                            <span class="badge bg-success">Received</span>
                                        @endif
                                    </td>
                                    <td>
                                        <form action="{{ route('stock-transfers.updateStatus', $transfer) }}" method="POST" class="d-inline">
                                            @csrf
                                            @if($transfer->status == 'draft')
                                                <button type="submit" name="action" value="send" class="btn btn-sm btn-warning">Send Transfer</button>
                                            @elseif($transfer->status == 'sent')
                                                <button type="submit" name="action" value="receive" class="btn btn-sm btn-success">Mark Received</button>
                                            @endif
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        {{ $transfers->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
