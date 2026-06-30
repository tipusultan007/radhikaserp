@extends('layouts.vertical', ['page_title' => 'Journal Entries', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                <h4 class="page-title">Journal Entries</h4>
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                    <li class="breadcrumb-item active">Journals</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card mb-3">
                <div class="card-body">
                    <form action="{{ route('journals.index') }}" method="GET">
                        <div class="row gy-2 gx-2 align-items-end">
                            <div class="col-md-3">
                                <label for="start_date" class="form-label mb-1">Start Date</label>
                                <input type="text" class="form-control flatpickr-date" id="start_date" name="start_date" value="{{ request('start_date') }}" placeholder="YYYY-MM-DD">
                            </div>
                            <div class="col-md-3">
                                <label for="end_date" class="form-label mb-1">End Date</label>
                                <input type="text" class="form-control flatpickr-date" id="end_date" name="end_date" value="{{ request('end_date') }}" placeholder="YYYY-MM-DD">
                            </div>
                            <div class="col-md-3">
                                <label for="journal_no" class="form-label">Journal No</label>
                                <input type="text" class="form-control" id="journal_no" name="journal_no" value="{{ request('journal_no') }}" placeholder="e.g. JNL-123">
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary"><i class="ri-search-line me-1"></i> Filter</button>
                                <a href="{{ route('journals.index') }}" class="btn btn-light ms-1">Clear</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle w-100 mb-0">
                            <thead>
                                <tr class="table-light">
                                    <th>Date / Reference</th>
                                    <th>Account</th>
                                    <th class="text-end" style="width: 150px;">Debit ($)</th>
                                    <th class="text-end" style="width: 150px;">Credit ($)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($journals as $journal)
                                    <!-- Journal Header Row -->
                                    <tr class="bg-light">
                                        <td colspan="4" class="py-2 border-bottom-0">
                                            <span class="fs-15"><strong>{{ $journal->date->format('d M, Y') }}</strong></span>
                                            <span class="mx-2">|</span>
                                            <a href="{{ route('journals.show', $journal->id) }}" class="fw-bold">{{ $journal->journal_no }}</a> 
                                            <span class="mx-2">|</span>
                                            <span class="text-muted">{{ $journal->notes }}</span>
                                        </td>
                                    </tr>
                                    
                                    <!-- Journal Entries Rows -->
                                    @foreach($journal->entries as $entry)
                                        <tr>
                                            <td class="border-top-0 border-bottom-0"></td> <!-- Empty for visual grouping -->
                                            <td style="{{ $entry->type == 'credit' ? 'padding-left: 40px;' : 'font-weight: 500;' }}">
                                                {{ $entry->account->name ?? 'Unknown Account' }}
                                            </td>
                                            <td class="text-end text-danger fw-semibold">
                                                {{ $entry->type == 'debit' ? number_format($entry->amount, 0) : '' }}
                                            </td>
                                            <td class="text-end text-success fw-semibold">
                                                {{ $entry->type == 'credit' ? number_format($entry->amount, 0) : '' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                    
                                    <!-- Visual Divider between Journals -->
                                    <tr>
                                        <td colspan="4" class="border-top-0" style="height: 15px;"></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4">No journal entries found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        {{ $journals->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

