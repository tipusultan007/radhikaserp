@extends('layouts.vertical', ['page_title' => 'Balance Sheet', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex justify-content-between align-items-center">
                <h4 class="page-title">Balance Sheet</h4>
                <a href="{{ route('reports.bs.print') }}" target="_blank" class="btn btn-primary">
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
                        <h2 class="fw-bold mb-1">Company Balance Sheet</h2>
                        <p class="text-muted mb-0">As of {{ \Carbon\Carbon::now()->format('F d, Y') }}</p>
                    </div>

                    <div class="row">
                        <!-- ASSETS -->
                        <div class="col-md-6 border-end pe-4">
                            <h4 class="text-primary border-bottom pb-2 mb-3">Assets</h4>
                            <table class="table table-borderless table-sm mb-4">
                                <tbody>
                                    @forelse($assetAccounts as $acc)
                                    <tr>
                                        <td class="ps-2">{{ $acc->name }}</td>
                                        <td class="text-end">${{ number_format($acc->balance, 2) }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td class="ps-2 text-muted" colspan="2">No assets recorded.</td>
                                    </tr>
                                    @endforelse
                                    <tr class="border-top fw-bold bg-light">
                                        <td class="ps-2 py-2 text-uppercase">Total Assets</td>
                                        <td class="text-end py-2">${{ number_format($totalAssets, 2) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- LIABILITIES & EQUITY -->
                        <div class="col-md-6 ps-4">
                            <h4 class="text-danger border-bottom pb-2 mb-3">Liabilities</h4>
                            <table class="table table-borderless table-sm mb-4">
                                <tbody>
                                    @forelse($liabilityAccounts as $acc)
                                    <tr>
                                        <td class="ps-2">{{ $acc->name }}</td>
                                        <td class="text-end">${{ number_format($acc->balance, 2) }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td class="ps-2 text-muted" colspan="2">No liabilities recorded.</td>
                                    </tr>
                                    @endforelse
                                    <tr class="border-top fw-bold bg-light">
                                        <td class="ps-2 py-2 text-uppercase">Total Liabilities</td>
                                        <td class="text-end py-2">${{ number_format($totalLiabilities, 2) }}</td>
                                    </tr>
                                </tbody>
                            </table>

                            <h4 class="text-success border-bottom pb-2 mb-3 mt-4">Equity</h4>
                            <table class="table table-borderless table-sm mb-4">
                                <tbody>
                                    @forelse($equityAccounts as $acc)
                                    <tr>
                                        <td class="ps-2">{{ $acc->name }}</td>
                                        <td class="text-end">${{ number_format($acc->balance, 2) }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td class="ps-2 text-muted" colspan="2">No equity recorded.</td>
                                    </tr>
                                    @endforelse
                                    <tr class="border-top fw-bold bg-light">
                                        <td class="ps-2 py-2 text-uppercase">Total Equity</td>
                                        <td class="text-end py-2">${{ number_format($totalEquity, 2) }}</td>
                                    </tr>
                                </tbody>
                            </table>

                            <div class="d-flex justify-content-between mt-4 bg-dark text-white p-2 rounded shadow-sm">
                                <span class="fs-5 fw-bold text-uppercase">Total Liab. & Equity</span>
                                <span class="fs-5 fw-bold">
                                    ${{ number_format($totalLiabilities + $totalEquity, 2) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

