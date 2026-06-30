<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Balance Sheet</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            margin: 0;
            padding: 20px;
            font-size: 13px;
            line-height: 1.5;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
        }
        .company-name {
            font-size: 28px;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
        }
        .company-address {
            font-size: 14px;
            margin: 5px 0;
            color: #555;
        }
        .report-title {
            font-size: 20px;
            font-weight: bold;
            margin: 20px 0 5px 0;
            text-transform: uppercase;
        }
        .report-meta {
            font-size: 12px;
            color: #777;
            text-align: center;
            margin-bottom: 30px;
        }
        .layout-table {
            width: 100%;
            border-collapse: collapse;
        }
        .layout-col {
            width: 50%;
            vertical-align: top;
            padding: 0 15px;
        }
        .border-right {
            border-right: 1px solid #ccc;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin: 15px 0 10px 0;
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
            text-transform: uppercase;
        }
        .account-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .account-table td {
            padding: 6px 4px;
        }
        .account-name {
            width: 70%;
        }
        .amount {
            text-align: right;
            width: 30%;
        }
        .total-row {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            font-weight: bold;
            background-color: #f9f9f9;
        }
        .grand-total-row {
            border-top: 2px solid #000;
            border-bottom: 2px double #000;
            font-weight: bold;
            font-size: 15px;
            background-color: #f0f0f0;
        }
        .text-primary { color: #0d6efd; }
        .text-danger { color: #dc3545; }
        .text-success { color: #198754; }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 11px;
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
        <h2 class="report-title">Balance Sheet</h2>
        <p class="report-meta">As of {{ \Carbon\Carbon::now()->format('F d, Y') }}</p>
    </div>

    <table class="layout-table">
        <tr>
            <!-- ASSETS COLUMN -->
            <td class="layout-col border-right">
                <div class="section-title text-primary">Assets</div>
                <table class="account-table">
                    <tbody>
                        @forelse($assetAccounts as $acc)
                        <tr>
                            <td class="account-name">{{ $acc->name }}</td>
                            <td class="amount">${{ number_format($acc->balance, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td class="account-name text-muted" colspan="2">No assets recorded.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>

                <table class="account-table" style="margin-top: 40px;">
                    <tbody>
                        <tr class="grand-total-row">
                            <td class="account-name py-2 text-uppercase">Total Assets</td>
                            <td class="amount py-2">${{ number_format($totalAssets, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </td>

            <!-- LIABILITIES & EQUITY COLUMN -->
            <td class="layout-col">
                <div class="section-title text-danger">Liabilities</div>
                <table class="account-table">
                    <tbody>
                        @forelse($liabilityAccounts as $acc)
                        <tr>
                            <td class="account-name">{{ $acc->name }}</td>
                            <td class="amount">${{ number_format($acc->balance, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td class="account-name text-muted" colspan="2">No liabilities recorded.</td>
                        </tr>
                        @endforelse
                        <tr class="total-row">
                            <td class="account-name py-2 text-uppercase">Total Liabilities</td>
                            <td class="amount py-2">${{ number_format($totalLiabilities, 2) }}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="section-title text-success" style="margin-top: 30px;">Equity</div>
                <table class="account-table">
                    <tbody>
                        @forelse($equityAccounts as $acc)
                        <tr>
                            <td class="account-name">{{ $acc->name }}</td>
                            <td class="amount">${{ number_format($acc->balance, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td class="account-name text-muted" colspan="2">No equity recorded.</td>
                        </tr>
                        @endforelse
                        <tr class="total-row">
                            <td class="account-name py-2 text-uppercase">Total Equity</td>
                            <td class="amount py-2">${{ number_format($totalEquity, 2) }}</td>
                        </tr>
                    </tbody>
                </table>

                <table class="account-table" style="margin-top: 40px;">
                    <tbody>
                        <tr class="grand-total-row">
                            <td class="account-name py-2 text-uppercase">Total Liab. & Equity</td>
                            <td class="amount py-2">${{ number_format($totalLiabilities + $totalEquity, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <div class="footer">
        <p>This is a system generated financial statement. Please verify with your accountant before filing.</p>
        <p>Generated on: {{ \Carbon\Carbon::now()->format('F d, Y h:i A') }}</p>
    </div>

</body>
</html>
