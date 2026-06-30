@extends('layouts.vertical', ['page_title' => 'Journal Details', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('content')
<div class="container-fluid">

    <div class="row">
        <div class="col-12">
            <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                <h4 class="page-title">Journal Details</h4>
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('journals.index') }}">General Journal</a></li>
                    <li class="breadcrumb-item active">{{ $journal->journal_no }}</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Journal Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Journal No:</strong> {{ $journal->journal_no }}</p>
                    <p><strong>Date:</strong> {{ $journal->date->format('M d, Y') }}</p>
                    <p><strong>Reference:</strong> 
                        @if(class_basename($journal->reference_type) === 'User')
                            Salary Payment (User ID: {{ $journal->reference_id }})
                        @elseif(class_basename($journal->reference_type) === 'Expense')
                            Expense (ID: {{ $journal->reference_id }})
                        @else
                            {{ class_basename($journal->reference_type) }} #{{ $journal->reference_id }}
                        @endif
                    </p>
                    <p><strong>Notes:</strong> {{ $journal->notes ?? 'N/A' }}</p>
                    <p><strong>Created By:</strong> {{ $journal->creator->name ?? 'System' }}</p>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Journal Entries</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-centered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Account Name</th>
                                    <th>Account Type</th>
                                    <th class="text-end">Debit</th>
                                    <th class="text-end">Credit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $totalDebit = 0;
                                    $totalCredit = 0;
                                @endphp
                                @foreach($journal->entries as $entry)
                                    @php
                                        if($entry->type == 'debit') $totalDebit += $entry->amount;
                                        if($entry->type == 'credit') $totalCredit += $entry->amount;
                                    @endphp
                                    <tr>
                                        <td>{{ $entry->account->name }}</td>
                                        <td><span class="badge bg-secondary">{{ ucfirst($entry->account->type) }}</span></td>
                                        <td class="text-end">
                                            @if($entry->type == 'debit')
                                                ${{ number_format($entry->amount, 0) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($entry->type == 'credit')
                                                ${{ number_format($entry->amount, 0) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="2" class="text-end">Total:</th>
                                    <th class="text-end text-success">${{ number_format($totalDebit, 0) }}</th>
                                    <th class="text-end text-danger">${{ number_format($totalCredit, 0) }}</th>
                                </tr>
                                @if(round($totalDebit, 2) !== round($totalCredit, 2))
                                <tr>
                                    <td colspan="4" class="text-center text-danger fw-bold bg-danger-subtle">
                                        Warning: Journal is unbalanced!
                                    </td>
                                </tr>
                                @endif
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

