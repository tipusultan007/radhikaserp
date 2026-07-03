<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $sale->invoice_no }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            margin: 0;
            padding: 20px;
            font-size: 14px;
        }
        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 30px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
            background: #fff;
        }
        .header {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .header-left, .header-right {
            display: table-cell;
            vertical-align: top;
        }
        .header-left {
            width: 50%;
        }
        .header-right {
            width: 50%;
            text-align: right;
        }
        .invoice-title {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .info-table {
            width: 100%;
            margin-bottom: 30px;
        }
        .info-table td {
            vertical-align: top;
            width: 50%;
        }
        .section-title {
            font-weight: bold;
            text-transform: uppercase;
            color: #7f8c8d;
            margin-bottom: 5px;
            font-size: 12px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .items-table th {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            text-align: left;
            padding: 10px;
            font-weight: bold;
        }
        .items-table td {
            border-bottom: 1px solid #eee;
            padding: 10px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 5px 10px;
        }
        .grand-total {
            font-size: 18px;
            font-weight: bold;
            border-top: 2px solid #333;
            border-bottom: 2px solid #333;
        }
        .text-danger {
            color: #e74c3c;
        }
        .text-success {
            color: #2ecc71;
        }
        .notes {
            margin-top: 50px;
            font-size: 12px;
            color: #7f8c8d;
        }
        @media print {
            .invoice-box {
                box-shadow: none;
                border: none;
                margin: 0;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-box">
        
        <table style="width: 100%; margin-bottom: 30px;">
            <tr>
                <td style="width: 65%; vertical-align: top;">
                    <table style="width: 100%;">
                        <tr>
                            <td style="width: 70px; vertical-align: top;">
                                <?php
                                    $logoPath = public_path('logo.png');
                                    $logoData = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : '';
                                ?>
                                @if($logoData)
                                    <img src="data:image/png;base64,{{ $logoData }}" alt="Logo" style="max-height: 60px; max-width: 70px;">
                                @endif
                            </td>
                            <td style="vertical-align: top; padding-left: 10px;">
                                <h2 style="margin: 0 0 5px 0; font-size: 18px; text-transform: uppercase;">Radhikas Trade International</h2>
                                <div style="font-size: 12px; margin-bottom: 2px; color: #555;">88/89, Sadarghat Road, Chattogram, Bangladesh 4000</div>
                                <div style="font-size: 12px; color: #555;">018 9770 1188, 019 9984 8389 | sales.radhikastradeintl@gmail.com</div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="width: 35%; text-align: right; vertical-align: top;">
                    <div class="invoice-title" style="margin-top: 0; margin-bottom: 5px; font-size: 28px;">INVOICE</div>
                    <div style="font-size: 13px;"><strong>Invoice No:</strong> #{{ $sale->invoice_no }}</div>
                    <div style="font-size: 13px;"><strong>Date:</strong> {{ $sale->date->format('M d, Y') }}</div>
                </td>
            </tr>
        </table>

        <table class="info-table">
            <tr>
                <td>
                    <div class="section-title">Bill To:</div>
                    <strong>{{ $sale->customer->name ?? 'Walk-in Customer' }}</strong><br>
                    @if(isset($sale->customer) && $sale->customer->address)
                        {{ $sale->customer->address }}<br>
                    @endif
                    @if(isset($sale->customer) && $sale->customer->phone)
                        Phone: {{ $sale->customer->phone }}
                    @endif
                </td>
                <td class="text-right">
                    <div class="section-title">Payment Details:</div>
                    <strong>Status:</strong> {{ ucfirst($sale->payment_status) }}<br>
                    <strong>Delivery:</strong> {{ ucfirst(str_replace('_', ' ', $sale->delivery_method ?? 'None')) }}
                </td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%">#</th>
                    <th style="width: 50%">Description</th>
                    <th style="width: 15%; text-align: right;">Qty</th>
                    <th style="width: 15%; text-align: right;">Unit Price</th>
                    <th style="width: 15%; text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $item->productVariant->product->name ?? 'Unknown' }}</strong><br>
                        <span style="color: #7f8c8d; font-size: 12px;">{{ $item->productVariant->name ?? 'Unknown' }}</span>
                    </td>
                    <td class="text-right">{{ number_format($item->qty, 3) }}</td>
                    <td class="text-right">${{ number_format($item->unit_price, 0) }}</td>
                    <td class="text-right">${{ number_format($item->total_price, 0) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <table class="info-table" style="margin-bottom: 0;">
            <tr>
                <td style="width: 60%">
                    <div class="notes">
                        <strong>Notes:</strong><br>
                        Thank you for your business. Please make payments within 7 days from the receipt of this invoice.
                    </div>
                </td>
                <td style="width: 40%">
                    <table class="totals-table">
                        <tr>
                            <td class="text-right"><strong>Sub-total:</strong></td>
                            <td class="text-right">${{ number_format($sale->subtotal, 0) }}</td>
                        </tr>
                        <tr>
                            <td class="text-right"><strong>Discount:</strong></td>
                            <td class="text-right text-danger">-${{ number_format($sale->discount, 0) }}</td>
                        </tr>
                        <tr>
                            <td class="text-right"><strong>Delivery Charge:</strong></td>
                            <td class="text-right">${{ number_format($sale->delivery_charge, 0) }}</td>
                        </tr>
                        <tr class="grand-total">
                            <td class="text-right">Grand Total:</td>
                            <td class="text-right">${{ number_format($sale->total, 0) }}</td>
                        </tr>
                        <tr>
                            <td class="text-right">Amount Paid:</td>
                            <td class="text-right text-success">${{ number_format($sale->paid_amount, 0) }}</td>
                        </tr>
                        <tr>
                            <td class="text-right"><strong>Amount Due:</strong></td>
                            <td class="text-right text-danger"><strong>${{ number_format($sale->due_amount, 0) }}</strong></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        
    </div>
    
    <script>
        window.onload = function() {
            // Automatically trigger print if this isn't a PDF download
            if(window.location.href.includes('/print')) {
                window.print();
            }
        };
    </script>
</body>
</html>

