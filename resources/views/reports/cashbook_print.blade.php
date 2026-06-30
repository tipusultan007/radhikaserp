<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashbook Report</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            margin: 0;
            padding: 20px;
            font-size: 11px;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
        }
        .company-address {
            font-size: 12px;
            margin: 5px 0;
            color: #555;
        }
        .report-title {
            font-size: 18px;
            font-weight: bold;
            margin: 15px 0 5px 0;
            text-transform: uppercase;
        }
        .report-meta {
            font-size: 11px;
            color: #777;
            text-align: center;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            table-layout: fixed;
        }
        th, td {
            border: 1px solid #000;
            padding: 6px 8px;
            vertical-align: top;
        }
        th {
            background-color: #f4f4f4;
            font-weight: bold;
            text-align: center;
        }
        .border-end {
            border-right: 2px solid #000 !important;
        }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .bg-light { background-color: #f9f9f9; }
        .text-muted { color: #666; font-size: 9px; }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background-color: #0d6efd; color: #fff; border: none; cursor: pointer; border-radius: 4px; font-weight: bold;">
            Print Report
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; background-color: #6c757d; color: #fff; border: none; cursor: pointer; border-radius: 4px; font-weight: bold; margin-left: 10px;">
            Close
        </button>
    </div>

    <div class="header">
        <h1 class="company-name">Radhika's ERP</h1>
        <p class="company-address">123 Business Avenue, Enterprise City, Country</p>
        <h2 class="report-title">Cashbook (T-Format Ledger)</h2>
        <p class="report-meta">
            @if(request('date'))
                For Date: {{ \Carbon\Carbon::parse(request('date'))->format('M d, Y') }}
            @endif
            | Generated on: {{ \Carbon\Carbon::now()->format('F d, Y h:i A') }}
        </p>
    </div>

    @php
        $debits = $entries->where('type', 'debit')->sortBy('journal.date')->values();
        $credits = $entries->where('type', 'credit')->sortBy('journal.date')->values();
        $maxRows = max($debits->count(), $credits->count());
        $totalDebit = $debits->sum('amount');
        $totalCredit = $credits->sum('amount');
        $closingBalance = $totalDebit - $totalCredit;
    @endphp

    <table>
        <thead>
            <tr>
                <th colspan="3" class="border-end fs-4 py-2" style="font-size: 14px;"><strong>Dr. (Receipts)</strong></th>
                <th colspan="3" class="fs-4 py-2" style="font-size: 14px;"><strong>Cr. (Payments)</strong></th>
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
                        <td>{{ $debits[$i]->journal->notes }} <br><span class="text-muted">{{ $debits[$i]->journal->journal_no }}</span></td>
                        <td class="border-end text-end">{{ number_format($debits[$i]->amount, 2) }}</td>
                    @else
                        <td></td><td></td><td class="border-end"></td>
                    @endif
                    
                    {{-- Credit Side --}}
                    @if(isset($credits[$i]))
                        <td>{{ \Carbon\Carbon::parse($credits[$i]->journal->date)->format('d M, Y') }}</td>
                        <td>{{ $credits[$i]->journal->notes }} <br><span class="text-muted">{{ $credits[$i]->journal->journal_no }}</span></td>
                        <td class="text-end">{{ number_format($credits[$i]->amount, 2) }}</td>
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
                    <td class="text-end fw-bold">{{ number_format($closingBalance, 2) }}</td>
                </tr>
            @elseif($closingBalance < 0)
                <tr class="bg-light">
                    <td class="fw-bold">{{ date('d M, Y') }}</td>
                    <td class="fw-bold text-end">To Balance c/d</td>
                    <td class="border-end text-end fw-bold">{{ number_format(abs($closingBalance), 2) }}</td>
                    <td></td><td></td><td></td>
                </tr>
            @endif
        </tbody>
        <tfoot>
            @php
                $grandTotal = max($totalDebit, $totalCredit);
            @endphp
            <tr style="border-top: 2px solid #000; border-bottom: 2px double #000;">
                <td colspan="2" class="text-end fw-bold">Total:</td>
                <td class="border-end text-end fw-bold">${{ number_format($grandTotal, 2) }}</td>
                <td colspan="2" class="text-end fw-bold">Total:</td>
                <td class="text-end fw-bold">${{ number_format($grandTotal, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p>This is a system generated cashbook report. Contains sensitive financial data.</p>
        <p>Powered by Radhika's ERP</p>
    </div>

</body>
</html>
