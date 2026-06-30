@extends('layouts.vertical')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('investments.index') }}">Investments</a></li>
                        <li class="breadcrumb-item active">Transaction Details</li>
                    </ol>
                </div>
                <h4 class="page-title">Investment / Withdraw Details</h4>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Transaction Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless table-sm">
                        <tr>
                            <td class="fw-bold" style="width: 35%;">Date:</td>
                            <td>{{ $investment->date->format('d M Y') }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Type:</td>
                            <td>
                                @if($investment->type === 'investment')
                                    <span class="badge bg-success">Investment</span>
                                @else
                                    <span class="badge bg-danger">Withdraw</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Amount:</td>
                            <td class="fs-4 text-primary">{{ number_format($investment->amount, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Payment Account:</td>
                            <td>{{ $investment->account->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Investor Name:</td>
                            <td>{{ $investment->investor_name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Reference:</td>
                            <td>{{ $investment->reference ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Notes:</td>
                            <td>{{ $investment->notes ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Recorded By:</td>
                            <td>{{ $investment->creator->name ?? 'System' }}</td>
                        </tr>
                    </table>

                    <div class="mt-4">
                        <a href="{{ route('investments.edit', $investment->id) }}" class="btn btn-primary"><i class="mdi mdi-square-edit-outline me-1"></i> Edit Transaction</a>
                        <a href="{{ route('investments.index') }}" class="btn btn-light ms-2">Back to List</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-primary border">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0 text-white">Accounting Journal Entry</h5>
                </div>
                <div class="card-body">
                    @if($investment->journal)
                        <p><strong>Journal No:</strong> {{ $investment->journal->journal_no }}</p>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm text-center">
                                <thead class="table-light">
                                    <tr>
                                        <th>Account</th>
                                        <th>Debit</th>
                                        <th>Credit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($investment->journal->entries as $entry)
                                        <tr>
                                            <td class="text-start">{{ $entry->account->name }}</td>
                                            <td>{{ $entry->type === 'debit' ? number_format($entry->amount, 2) : '-' }}</td>
                                            <td>{{ $entry->type === 'credit' ? number_format($entry->amount, 2) : '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light fw-bold">
                                    <tr>
                                        <td class="text-end">Total</td>
                                        <td>{{ number_format($investment->amount, 2) }}</td>
                                        <td>{{ number_format($investment->amount, 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-warning mb-0">No journal entry found for this transaction.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
