<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profit & Loss Statement</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            margin: 0;
            padding: 20px;
            font-size: 14px;
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
        .section-title {
            font-size: 18px;
            font-weight: bold;
            margin: 25px 0 10px 0;
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
            text-transform: uppercase;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        td {
            padding: 8px 10px;
        }
        .account-name {
            width: 70%;
            padding-left: 20px;
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
        .net-profit-row {
            border-top: 2px solid #000;
            border-bottom: 2px double #000;
            font-weight: bold;
            font-size: 18px;
            background-color: #f0f0f0;
        }
        .text-success { color: #28a745; }
        .text-danger { color: #dc3545; }
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
        <h2 class="report-title">Profit & Loss Statement</h2>
        <p class="report-meta">For the period ending: {{ \Carbon\Carbon::now()->format('F d, Y') }}</p>
    </div>

    <!-- INCOME -->
    <div class="section-title text-success">Operating Income</div>
    <table>
        <tbody>
            @forelse($incomeAccounts as $acc)
            <tr>
                <td class="account-name">{{ $acc->name }}</td>
                <td class="amount">${{ number_format($acc->balance, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td class="account-name text-muted" colspan="2">No income recorded.</td>
            </tr>
            @endforelse
            <tr class="total-row">
                <td class="account-name text-uppercase py-2">Total Income</td>
                <td class="amount py-2">${{ number_format($incomeTotal, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- EXPENSES -->
    <div class="section-title text-danger">Operating Expenses</div>
    <table>
        <tbody>
            @forelse($expenseAccounts as $acc)
            <tr>
                <td class="account-name">{{ $acc->name }}</td>
                <td class="amount">${{ number_format($acc->balance, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td class="account-name text-muted" colspan="2">No expenses recorded.</td>
            </tr>
            @endforelse
            <tr class="total-row">
                <td class="account-name text-uppercase py-2">Total Expenses</td>
                <td class="amount py-2">${{ number_format($expenseTotal, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- NET PROFIT -->
    <table style="margin-top: 30px;">
        <tbody>
            <tr class="net-profit-row">
                <td class="account-name text-uppercase py-3">Net {{ $netProfit >= 0 ? 'Profit' : 'Loss' }}</td>
                <td class="amount py-3">${{ number_format(abs($netProfit), 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>This is a system generated financial statement. Please verify with your accountant before filing.</p>
        <p>Generated on: {{ \Carbon\Carbon::now()->format('F d, Y h:i A') }}</p>
    </div>

</body>
</html>
