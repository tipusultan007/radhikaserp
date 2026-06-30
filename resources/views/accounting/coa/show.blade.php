@extends('layouts.vertical', ['page_title' => 'View Account', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                <h4 class="page-title">Account Details: {{ $account->name }}</h4>
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('coa.index') }}">COA</a></li>
                    <li class="breadcrumb-item active">View</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title mb-3">Account Overview</h4>
                    <p><strong>Name:</strong> {{ $account->name }}</p>
                    <p><strong>Type:</strong> <span class="badge bg-primary text-uppercase">{{ $account->type }}</span></p>
                    <p><strong>Opening Balance:</strong> ${{ number_format($account->opening_balance, 0) }}</p>
                    <p><strong>Is Payment Method:</strong> {!! $account->is_payment_method ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' !!}</p>

                    <div class="d-flex gap-2 mt-3">
                        <a href="{{ route('coa.edit', $account->id) }}" class="btn btn-warning btn-sm">Edit</a>
                        <form action="{{ route('coa.destroy', $account->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this account?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title mb-3">Related Journal Entries</h4>
                    
                    <div class="card bg-light mb-3 shadow-none border">
                        <div class="card-body py-2 px-3">
                            <form action="{{ route('coa.show', $account->id) }}" method="GET">
                                <div class="row gy-2 gx-2 align-items-end">
                                    <div class="col-md-3">
                                        <label class="form-label mb-1">Start Date</label>
                                        <input type="text" class="form-control flatpickr-date" name="start_date" value="{{ request('start_date') }}" placeholder="YYYY-MM-DD">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label mb-1">End Date</label>
                                        <input type="text" class="form-control flatpickr-date" name="end_date" value="{{ request('end_date') }}" placeholder="YYYY-MM-DD">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label mb-1">Type</label>
                                        <select class="form-select" name="type">
                                            <option value="">All</option>
                                            <option value="debit" {{ request('type') == 'debit' ? 'selected' : '' }}>Debit</option>
                                            <option value="credit" {{ request('type') == 'credit' ? 'selected' : '' }}>Credit</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line me-1"></i> Filter</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-centered table-striped nowrap w-100">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Journal No</th>
                                    <th>Notes</th>
                                    <th>Debit</th>
                                    <th>Credit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($entries as $entry)
                                <tr>
                                    <td>{{ $entry->journal->date }}</td>
                                    <td>
                                        <a href="{{ route('journals.show', $entry->journal->id) }}">
                                            {{ $entry->journal->journal_no }}
                                        </a>
                                    </td>
                                    <td>{{ $entry->journal->notes }}</td>
                                    <td class="text-success">{{ $entry->type === 'debit' ? '$' . number_format($entry->amount, 0) : '-' }}</td>
                                    <td class="text-danger">{{ $entry->type === 'credit' ? '$' . number_format($entry->amount, 0) : '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $entries->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('script')
    @vite(['resources/js/pages/demo.form-advanced.js'])
@endsection

