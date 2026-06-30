@extends('layouts.vertical', ['page_title' => 'Cashbook (T-Format)', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex justify-content-between align-items-center">
                <h4 class="page-title">Cashbook (T-Format Ledger)</h4>
                <a href="{{ route('reports.cashbook.print', request()->all()) }}" target="_blank" class="btn btn-primary">
                    <i class="ri-printer-line me-1"></i> Print Report
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="card bg-light m-3 shadow-none border">
                        <div class="card-body py-2 px-3">
                            <form action="{{ route('reports.cashbook') }}" method="GET">
                                <div class="row gy-2 gx-2 align-items-end">
                                    <div class="col-md-3">
                                        <label class="form-label mb-1">Date</label>
                                        <input type="text" class="form-control flatpickr-date" name="date" value="{{ request('date') }}" placeholder="YYYY-MM-DD">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line me-1"></i> Filter</button>
                                    </div>
                                    <div class="col-md-7 text-end">
                                        @if(request()->filled('date'))
                                            <a href="{{ route('reports.cashbook') }}" class="btn btn-danger"><i class="ri-refresh-line me-1"></i> Clear</a>
                                        @endif
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive">
                        @php
                            // Separate debits and credits
                            $debits = $entries->where('type', 'debit')->sortBy('journal.date')->values();
                            $credits = $entries->where('type', 'credit')->sortBy('journal.date')->values();
                            $maxRows = max($debits->count(), $credits->count());
                            $totalDebit = $debits->sum('amount');
                            $totalCredit = $credits->sum('amount');
                            $closingBalance = $totalDebit - $totalCredit;
                        @endphp
                        
                        <table class="table table-bordered table-sm align-middle mb-0" style="table-layout: fixed;">
                            <thead class="table-light text-center">
                                <tr>
                                    <th colspan="3" class="border-end fs-4 py-2"><strong>Dr. (Receipts)</strong></th>
                                    <th colspan="3" class="fs-4 py-2"><strong>Cr. (Payments)</strong></th>
                                </tr>
                                <tr>
                                    <th style="width: 15%;">Date</th>
                                    <th style="width: 20%;">Particulars</th>
                                    <th style="width: 15%;" class="border-end text-end">Amount ($)</th>
                                    <th style="width: 15%;">Date</th>
                                    <th style="width: 20%;">Particulars</th>
                                    <th style="width: 15%;" class="text-end">Amount ($)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @for($i = 0; $i < $maxRows; $i++)
                                    <tr>
                                        {{-- Debit Side --}}
                                        @if(isset($debits[$i]))
                                            <td>{{ \Carbon\Carbon::parse($debits[$i]->journal->date)->format('d M, Y') }}</td>
                                            <td>{{ $debits[$i]->journal->notes }} <br><small class="text-muted">{{ $debits[$i]->journal->journal_no }}</small></td>
                                            <td class="border-end text-end text-success fw-semibold">{{ number_format($debits[$i]->amount, 0) }}</td>
                                        @else
                                            <td></td><td></td><td class="border-end"></td>
                                        @endif
                                        
                                        {{-- Credit Side --}}
                                        @if(isset($credits[$i]))
                                            <td>{{ \Carbon\Carbon::parse($credits[$i]->journal->date)->format('d M, Y') }}</td>
                                            <td>{{ $credits[$i]->journal->notes }} <br><small class="text-muted">{{ $credits[$i]->journal->journal_no }}</small></td>
                                            <td class="text-end text-danger fw-semibold">{{ number_format($credits[$i]->amount, 0) }}</td>
                                        @else
                                            <td></td><td></td><td></td>
                                        @endif
                                    </tr>
                                @endfor
                                
                                {{-- Closing Balance Row --}}
                                @if($closingBalance > 0)
                                    <tr class="bg-light">
                                        <td></td><td></td><td class="border-end"></td>
                                        <td class="fw-bold">{{ date('d M, Y') }}</td>
                                        <td class="fw-bold text-end">By Balance c/d</td>
                                        <td class="text-end text-danger fw-bold">{{ number_format($closingBalance, 0) }}</td>
                                    </tr>
                                @elseif($closingBalance < 0)
                                    <tr class="bg-light">
                                        <td class="fw-bold">{{ date('d M, Y') }}</td>
                                        <td class="fw-bold text-end">To Balance c/d</td>
                                        <td class="border-end text-end text-success fw-bold">{{ number_format(abs($closingBalance), 0) }}</td>
                                        <td></td><td></td><td></td>
                                    </tr>
                                @endif
                            </tbody>
                            <tfoot>
                                @php
                                    $grandTotal = max($totalDebit, $totalCredit);
                                @endphp
                                <tr class="table-active fw-bolder fs-4">
                                    <td colspan="2" class="text-end border-bottom-0">Total:</td>
                                    <td class="border-end text-end border-bottom-0 text-success">${{ number_format($grandTotal, 0) }}</td>
                                    <td colspan="2" class="text-end border-bottom-0">Total:</td>
                                    <td class="text-end border-bottom-0 text-danger">${{ number_format($grandTotal, 0) }}</td>
                                </tr>
                            </tfoot>
                        </table>
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

