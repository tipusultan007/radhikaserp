<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Statement - {{ $customer->name }}</title>
    <style>
        @page {
            margin: 22pt 26pt 24pt 26pt;
            size: a4 portrait;
        }
        * {
            box-sizing: border-box;
            font-family: Helvetica, Arial, sans-serif !important;
        }
        body, table, th, td, div, span, p, h1, h2, h3, h4, h5, h6, strong, b, em, i {
            font-family: Helvetica, Arial, sans-serif !important;
        }
        body {
            font-size: 8.5px;
            color: #1e293b;
            line-height: 1.35;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }

        /* Top Header Line */
        .top-rule {
            height: 3px;
            background-color: #1e293b;
            width: 100%;
            margin-bottom: 14px;
        }

        /* Header Table */
        .header-table {
            width: 100%;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }
        .company-name {
            font-size: 15px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .company-meta {
            font-size: 8px;
            color: #64748b;
            line-height: 1.45;
        }
        .doc-title {
            font-size: 17px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 6px;
        }
        .meta-table {
            float: right;
            border-collapse: collapse;
        }
        .meta-table td {
            font-size: 8px;
            padding: 1.5px 0;
        }
        .meta-label {
            color: #64748b;
            text-align: right;
            padding-right: 8px;
        }
        .meta-val {
            font-weight: bold;
            color: #0f172a;
            text-align: right;
        }

        /* Summary Section (Integrated 2-Column Banner) */
        .summary-wrapper {
            width: 100%;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            margin-bottom: 14px;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-col-left {
            width: 54%;
            vertical-align: top;
            padding: 10px 14px;
            border-right: 1px solid #e2e8f0;
        }
        .summary-col-right {
            width: 46%;
            vertical-align: top;
            padding: 10px 14px;
        }
        .section-label {
            font-size: 7.5px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 4px;
        }
        .customer-name {
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 2px;
        }
        .customer-company {
            font-size: 9px;
            font-weight: normal;
            color: #64748b;
        }
        .customer-badge {
            display: inline-block;
            background-color: #e2e8f0;
            color: #334155;
            font-size: 7.5px;
            font-weight: bold;
            padding: 1.5px 5px;
            border-radius: 2px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }
        .cust-details-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        .cust-details-table td {
            font-size: 8px;
            padding: 2px 0;
            vertical-align: top;
        }
        .cust-lbl {
            width: 65px;
            color: #64748b;
        }
        .cust-val {
            color: #0f172a;
            font-weight: 500;
        }

        /* Financial Figures on Right */
        .fin-table {
            width: 100%;
            border-collapse: collapse;
        }
        .fin-table td {
            font-size: 8px;
            padding: 2.5px 0;
        }
        .fin-lbl {
            color: #64748b;
        }
        .fin-val {
            text-align: right;
            font-weight: 600;
            color: #0f172a;
        }
        .closing-row-divider {
            border-top: 1.5px solid #0f172a;
            padding-top: 5px !important;
            margin-top: 3px;
        }
        .closing-lbl {
            font-size: 9px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
        }
        .closing-val {
            font-size: 13px;
            font-weight: bold;
            text-align: right;
        }

        /* Ledger Table */
        .ledger-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
            margin-bottom: 14px;
        }
        .ledger-table th {
            background-color: #1e293b;
            color: #ffffff;
            font-weight: bold;
            padding: 6px 7px;
            text-align: left;
            border: 1px solid #1e293b;
            font-size: 7.5px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .ledger-table td {
            padding: 5px 7px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .ledger-table tbody tr:nth-child(even) {
            background-color: #fbfcfd;
        }
        .ledger-table tfoot td {
            padding: 6px 7px;
            font-weight: bold;
            border-top: 2px solid #1e293b;
            border-bottom: 2px solid #1e293b;
            background-color: #f8fafc;
        }

        /* Helpers */
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-danger { color: #dc2626; }
        .text-success { color: #16a34a; }
        .fw-bold { font-weight: bold; }
        .ref-code { font-weight: bold; color: #0f172a; }
        .ref-notes { font-size: 7px; color: #64748b; margin-top: 1px; }

        /* Bottom Section & Signatures */
        .bottom-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .notes-box {
            font-size: 7.5px;
            color: #64748b;
            line-height: 1.4;
        }
        .sign-table {
            width: 100%;
            margin-top: 36px;
            border-collapse: collapse;
            page-break-inside: avoid;
        }
        .sign-line {
            width: 170px;
            border-top: 1px solid #94a3b8;
            text-align: center;
            padding-top: 4px;
            font-size: 8px;
            color: #475569;
        }
        .disclaimer-text {
            font-size: 7px;
            color: #94a3b8;
            text-align: center;
            margin-top: 20px;
            border-top: 1px solid #f1f5f9;
            padding-top: 6px;
        }
    </style>
</head>
<body>

    <div class="top-rule"></div>

    <!-- Header Section -->
    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 58%; vertical-align: top;">
                @if(!empty($logoBase64))
                    <img src="{{ $logoBase64 }}" alt="Logo" style="max-height: 42px; margin-bottom: 5px;">
                @endif
                <div class="company-name">Radhikas Trade International</div>
                <div class="company-meta">
                    88/89, Sadarghat Road, Chattogram, Bangladesh 4000<br>
                    Hotline: 018 9770 1188, 019 9984 8389, 017 3222 6604<br>
                    Email: sales.radhikastradeintl@gmail.com | Web: radhikastradeintl.com
                </div>
            </td>
            <td style="width: 42%; vertical-align: top; text-align: right;">
                <div class="doc-title">Account Statement</div>
                <table class="meta-table" cellpadding="0" cellspacing="0">
                    <tr>
                        <td class="meta-label">Statement Ref:</td>
                        <td class="meta-val">STM-{{ date('ym') }}-{{ str_pad($customer->id, 4, '0', STR_PAD_LEFT) }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Statement Date:</td>
                        <td class="meta-val">{{ date('d M, Y') }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Statement Period:</td>
                        <td class="meta-val">
                            @if(!empty($start_date) && !empty($end_date))
                                {{ \Carbon\Carbon::parse($start_date)->format('d M, Y') }} - {{ \Carbon\Carbon::parse($end_date)->format('d M, Y') }}
                            @elseif(!empty($start_date))
                                From {{ \Carbon\Carbon::parse($start_date)->format('d M, Y') }}
                            @elseif(!empty($end_date))
                                Up to {{ \Carbon\Carbon::parse($end_date)->format('d M, Y') }}
                            @else
                                All Time History
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="meta-label">Account No:</td>
                        <td class="meta-val">CUST-{{ str_pad($customer->id, 4, '0', STR_PAD_LEFT) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Integrated Statement Summary (2 Columns) -->
    <div class="summary-wrapper">
        <table class="summary-table" cellpadding="0" cellspacing="0">
            <tr>
                <!-- Left: Customer Information -->
                <td class="summary-col-left">
                    <div class="section-label">Statement Issued To</div>
                    <div class="customer-name">
                        {{ $customer->name }}
                        @if($customer->company)
                            <span class="customer-company">({{ $customer->company }})</span>
                        @endif
                    </div>
                    @if($customer->customer_type)
                        <span class="customer-badge">
                            {{ str_replace('_', ' ', $customer->customer_type) }}
                        </span>
                    @endif

                    <table class="cust-details-table" cellpadding="0" cellspacing="0">
                        <tr>
                            <td class="cust-lbl">Phone:</td>
                            <td class="cust-val">{{ $customer->phone ?: '—' }}</td>
                        </tr>
                        @if($customer->district)
                        <tr>
                            <td class="cust-lbl">District:</td>
                            <td class="cust-val">{{ $customer->district }}</td>
                        </tr>
                        @endif
                        @if($customer->address)
                        <tr>
                            <td class="cust-lbl">Address:</td>
                            <td class="cust-val">{{ $customer->address }}</td>
                        </tr>
                        @endif
                    </table>
                </td>

                <!-- Right: Financial Standing Summary -->
                <td class="summary-col-right">
                    <div class="section-label" style="text-align: right;">Financial Summary (BDT)</div>
                    <table class="fin-table" cellpadding="0" cellspacing="0">
                        <tr>
                            <td class="fin-lbl">Opening Balance B/F:</td>
                            <td class="fin-val">{{ number_format($opening_balance, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="fin-lbl">Period Purchases / Invoiced (+):</td>
                            <td class="fin-val">{{ number_format($period_debit, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="fin-lbl">Period Payments / Received (-):</td>
                            <td class="fin-val text-success">{{ number_format($period_credit, 2) }}</td>
                        </tr>
                        @if($customer->credit_limit > 0)
                        <tr>
                            <td class="fin-lbl">Approved Credit Limit:</td>
                            <td class="fin-val">{{ number_format($customer->credit_limit, 2) }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td class="fin-lbl closing-row-divider closing-lbl">
                                Closing Due / Balance:
                            </td>
                            <td class="fin-val closing-row-divider closing-val {{ $closing_balance > 0 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($closing_balance, 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="text-align: right; padding-top: 2px;">
                                @if($closing_balance > 0)
                                    <span style="font-size: 7.5px; color: #dc2626; font-weight: bold;">(Net Outstanding Due)</span>
                                @elseif($closing_balance < 0)
                                    <span style="font-size: 7.5px; color: #16a34a; font-weight: bold;">(Advance Credit Balance)</span>
                                @else
                                    <span style="font-size: 7.5px; color: #16a34a; font-weight: bold;">(Account Settled / Zero Balance)</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <!-- Transactions Ledger Table -->
    <table class="ledger-table" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th style="width: 13%;">Date</th>
                <th style="width: 35%;">Description</th>
                <th style="width: 14%;">Method</th>
                <th style="width: 12%;" class="text-right">Debit (BDT)</th>
                <th style="width: 12%;" class="text-right">Credit (BDT)</th>
                <th style="width: 14%;" class="text-right">Balance (BDT)</th>
            </tr>
        </thead>
        <tbody>
            @if(!empty($start_date) || $opening_balance != 0)
                <tr style="background-color: #f1f5f9; font-weight: bold;">
                    <td>{{ !empty($start_date) ? \Carbon\Carbon::parse($start_date)->format('d M, Y') : '—' }}</td>
                    <td class="fw-bold">Opening Balance B/F</td>
                    <td class="text-center" style="color: #64748b;">—</td>
                    <td class="text-right">{{ $opening_balance > 0 ? number_format($opening_balance, 2) : '-' }}</td>
                    <td class="text-right">{{ $opening_balance < 0 ? number_format(abs($opening_balance), 2) : '-' }}</td>
                    <td class="text-right fw-bold">{{ number_format($opening_balance, 2) }}</td>
                </tr>
            @endif

            @forelse($entries as $entry)
                @php
                    $desc = '';
                    if ($entry->debit > 0 && $entry->credit == 0) {
                        $desc = 'Sale Invoice (' . $entry->ref_no . ')';
                    } elseif ($entry->credit > 0 && $entry->debit == 0) {
                        $desc = 'Payment Received (' . $entry->ref_no . ')';
                    } elseif (!empty($entry->notes)) {
                        $desc = $entry->notes;
                    } else {
                        $desc = $entry->ref_no ?: 'Transaction';
                    }
                @endphp
                <tr>
                    <td>{{ \Carbon\Carbon::parse($entry->date)->format('d M, Y') }}</td>
                    <td>{{ $desc }}</td>
                    <td>{{ $entry->payment_method ?: '—' }}</td>
                    <td class="text-right text-danger">
                        {{ $entry->debit > 0 ? number_format($entry->debit, 2) : '-' }}
                    </td>
                    <td class="text-right text-success">
                        {{ $entry->credit > 0 ? number_format($entry->credit, 2) : '-' }}
                    </td>
                    <td class="text-right fw-bold">
                        {{ number_format($entry->running_balance, 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 16px; color: #64748b;">
                        No transactions recorded for the selected period.
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-right" style="letter-spacing: 0.5px;">PERIOD ACTIVITY TOTALS:</td>
                <td class="text-right text-danger">{{ number_format($period_debit, 2) }}</td>
                <td class="text-right text-success">{{ number_format($period_credit, 2) }}</td>
                <td class="text-right {{ $closing_balance > 0 ? 'text-danger' : 'text-success' }}" style="font-size: 9px;">
                    {{ number_format($closing_balance, 2) }}
                </td>
            </tr>
        </tfoot>
    </table>

    <!-- Payment Note & Signatures -->
    <table class="bottom-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 60%; vertical-align: top; padding-right: 15px;">
                <div class="notes-box">
                    <strong>Payment Terms & Remittance:</strong><br>
                    Please remit payments via crossed cheque or account bank transfer payable to <strong>Radhikas Trade International</strong>.<br>
                    For billing support or account reconciliation, please call <strong>018 9770 1188</strong> or email <strong>sales.radhikastradeintl@gmail.com</strong>.
                </div>
            </td>
            <td style="width: 40%; vertical-align: top;">
                <!-- Clean spacer -->
            </td>
        </tr>
    </table>

    <table class="sign-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 50%; vertical-align: bottom;">
                <div class="sign-line">Customer Signature & Seal</div>
            </td>
            <td style="width: 50%; vertical-align: bottom; text-align: right;">
                <div class="sign-line" style="margin-left: auto;">
                    Authorized Signatory<br>
                    <strong>Radhikas Trade International</strong>
                </div>
            </td>
        </tr>
    </table>

    <div class="disclaimer-text">
        This is an official computer-generated statement of account issued by Radhikas Trade International ERP. If you notice any discrepancy, please notify accounts within 7 calendar days.
    </div>

</body>
</html>
