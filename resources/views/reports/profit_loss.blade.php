@extends('layouts.vertical', ['page_title' => 'Profit & Loss', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex justify-content-between align-items-center">
                <h4 class="page-title">Profit & Loss Statement</h4>
                <a href="{{ route('reports.pl.print') }}" target="_blank" class="btn btn-primary">
                    <i class="ri-printer-line me-1"></i> Print Report
                </a>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    
                    <div class="text-center mb-4 border-bottom pb-3">
                        <h2 class="fw-bold mb-1">Company Profit & Loss</h2>
                        <p class="text-muted mb-0">For the period ending {{ \Carbon\Carbon::now()->format('F d, Y') }}</p>
                    </div>
                    
                    <!-- INCOME SECTION -->
                    <h4 class="text-primary border-bottom pb-2 mb-3">Operating Income</h4>
                    <table class="table table-borderless table-sm mb-4">
                        <tbody>
                            @forelse($incomeAccounts as $acc)
                            <tr>
                                <td class="ps-4" style="width: 70%">{{ $acc->name }}</td>
                                <td class="text-end text-success">${{ number_format($acc->balance, 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td class="ps-4 text-muted" colspan="2">No income recorded.</td>
                            </tr>
                            @endforelse
                            <tr class="border-top fw-bold bg-light">
                                <td class="ps-4 py-2 text-uppercase">Total Income</td>
                                <td class="text-end py-2 text-success">${{ number_format($incomeTotal, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <!-- EXPENSE SECTION -->
                    <h4 class="text-danger border-bottom pb-2 mb-3 mt-4">Operating Expenses</h4>
                    <table class="table table-borderless table-sm mb-4">
                        <tbody>
                            @forelse($expenseAccounts as $acc)
                            <tr>
                                <td class="ps-4" style="width: 70%">{{ $acc->name }}</td>
                                <td class="text-end text-danger">${{ number_format($acc->balance, 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td class="ps-4 text-muted" colspan="2">No expenses recorded.</td>
                            </tr>
                            @endforelse
                            <tr class="border-top fw-bold bg-light">
                                <td class="ps-4 py-2 text-uppercase">Total Expenses</td>
                                <td class="text-end py-2 text-danger">${{ number_format($expenseTotal, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- NET PROFIT -->
                    <div class="d-flex justify-content-between mt-5 bg-dark text-white p-3 rounded shadow-sm">
                        <span class="fs-3 fw-bold text-uppercase">Net {{ $netProfit >= 0 ? 'Profit' : 'Loss' }}</span>
                        <span class="fs-3 fw-bold">
                            ${{ number_format(abs($netProfit), 2) }}
                        </span>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

