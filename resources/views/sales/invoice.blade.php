<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Invoice #{{ $sale->invoice_no }}</title>
    <style>
        @page { margin: 20px; }
        body {
            font-family: sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.4;
        }
        .invoice-box { width: 100%; padding: 10px; }
        .header-table { width: 100%; border-bottom: 2px solid #333; margin-bottom: 10px; }
        .header-table td { vertical-align: top; }
        .company-info h2 { margin: 0; font-size: 18px; text-transform: uppercase; }
        .company-info p { margin: 2px 0; font-size: 11px; }
        h3 { margin: 5px 0; font-size: 14px; color: #000; }
        table { border-collapse: collapse; width: 100%; }
        .items-table th, .items-table td { border: 1px solid #ddd; padding: 8px; }
        .items-table th { background-color: #333; color: #fff; font-weight: bold; text-transform: uppercase; font-size: 10px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totals { margin-top: 10px; width: 100%; border-collapse: collapse; }
        .totals td { padding: 4px; border: none; }
        .totals tr.grand-total td { border-top: 1px solid #000; font-weight: bold; font-size: 14px; color: #0d6efd; }
        .notes { margin-top: 20px; padding: 10px; border: 1px dashed #ccc; background: #fdfdfd; font-size: 11px; }
        .footer { margin-top: 50px; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 10px; }
        .bank-info { font-size: 10px; }
        .bank-info td { border: 1px solid #eee; padding: 4px; }
    </style>
</head>
<body>
<div class="invoice-box">

    <!-- Header -->
    <table class="header-table">
        <tr>
            <td style="width: 15%;">
                @if(file_exists(public_path('build/images/radhikas-logo.png')))
                    <img src="{{ public_path('build/images/radhikas-logo.png') }}" height="70">
                @endif
            </td>
            <td class="company-info" style="width: 85%; text-align: right;">
                <h2>Radhikas Trade International</h2>
                <p>88/89, Sadarghat Road, Chattogram, Bangladesh 4000</p>
                <p><b>Phone</b>: 018 9770 1188, 019 9984 8389, 017 3222 6604</p>
                <p><b>Email</b>: sales.radhikastradeintl@gmail.com</p>
            </td>
        </tr>
    </table>

    <!-- Billing Info -->
    <table style="margin-bottom: 20px;">
        <tr>
            <td style="width: 60%; vertical-align: top;">
                <h3 style="border-bottom: 1px solid #eee; width: 50%;">Bill To:</h3>
                <p style="margin-bottom: 2px;"><strong style="font-size: 14px;">{{ $sale->customer->name ?? 'Walk-in Customer' }}</strong></p>
                <p style="margin: 0; color: #555;">{{ $sale->customer->address ?? '' }}</p>
                <p style="margin: 0; color: #555;">Phone: {{ $sale->customer->phone ?? 'N/A' }}</p>
            </td>
            <td style="width: 40%; vertical-align: top; text-align: right;">
                <h2 style="color: #0d6efd; margin: 0;">INVOICE</h2>
                <p style="margin: 2px 0;"><strong>No:</strong> {{ $sale->invoice_no }}</p>
                <p style="margin: 2px 0;"><strong>Date:</strong> {{ $sale->date->format('d M, Y') }}</p>
                <p style="margin: 2px 0;"><strong>Status:</strong> {{ strtoupper($sale->payment_status) }}</p>
                @if($sale->delivery_method)
                    <p style="margin: 2px 0;"><strong>Delivery:</strong> {{ ucfirst(str_replace('_', ' ', $sale->delivery_method)) }}</p>
                @endif
            </td>
        </tr>
    </table>

    <!-- Products Table -->
    @php $totalWeight = 0; @endphp
    <table class="items-table">
        <thead>
        <tr>
            <th class="text-center" style="width: 30px;">#</th>
            <th>Product Description</th>
            <th class="text-center">Variant / Package</th>
            <th class="text-center">Unit</th>
            <th class="text-right">Qty</th>
            <th class="text-right">Unit Price</th>
            <th class="text-right">Weight (KG)</th>
            <th class="text-right">Subtotal</th>
        </tr>
        </thead>
        <tbody>
        @foreach($sale->items as $index => $item)
            @php
                $rowWeight = (float) ($item->total_weight ?? 0);
                $totalWeight += $rowWeight;
                $variantName = $item->productVariant->name ?? 'N/A';
                $unitShort   = $item->productVariant->unit->short_name ?? '';
                $productName = $item->productVariant->product->name ?? 'N/A';
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td><b>{{ $productName }}</b></td>
                <td class="text-center">{{ $variantName }}</td>
                <td class="text-center">{{ $unitShort }}</td>
                <td class="text-right">{{ number_format($item->qty, 0) }}</td>
                <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                <td class="text-right">{{ number_format($rowWeight, 2) }}</td>
                <td class="text-right">{{ number_format($item->total_price, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <!-- Totals & Payment Info Wrapper -->
    <table style="width: 100%; margin-top: 15px;">
        <tr>
            <!-- Left: Bank / Payment Instructions -->
            <td style="width: 55%; vertical-align: top;">
                <p style="margin: 0 0 5px 0; font-weight: bold; font-size: 11px;">Payment Instructions:</p>
                <table class="bank-info">
                    <tr>
                        <td style="background: #f9f9f9; width: 30%;">Bkash (Personal)</td>
                        <td>01897-701188</td>
                    </tr>
                    <tr>
                        <td style="background: #f9f9f9;">Bank Name</td>
                        <td>Southeast Bank PLC</td>
                    </tr>
                    <tr>
                        <td style="background: #f9f9f9;">Account Name</td>
                        <td>Radhikas Trade International</td>
                    </tr>
                    <tr>
                        <td style="background: #f9f9f9;">Account No</td>
                        <td>000311100027215</td>
                    </tr>
                    <tr>
                        <td style="background: #f9f9f9;">Branch</td>
                        <td>Agrabad, Chattogram</td>
                    </tr>
                </table>
            </td>

            <!-- Right: Totals -->
            <td style="width: 45%; vertical-align: top; padding-left: 20px;">
                <table class="totals">
                    <tr>
                        <td style="color: #666;">Items Subtotal:</td>
                        <td class="text-right">{{ number_format($sale->subtotal, 2) }} Tk</td>
                    </tr>
                    <tr>
                        <td style="color: #666;">Discount:</td>
                        <td class="text-right" style="color: #dc3545;">-{{ number_format($sale->discount, 2) }} Tk</td>
                    </tr>
                    <tr>
                        <td style="color: #666;">Delivery Charge:</td>
                        <td class="text-right">+{{ number_format($sale->delivery_charge ?? 0, 2) }} Tk</td>
                    </tr>
                    <tr>
                        <td style="color: #666; padding-bottom: 8px;">Total Weight:</td>
                        <td class="text-right" style="padding-bottom: 8px;">{{ number_format($totalWeight, 2) }} KG</td>
                    </tr>
                    <tr class="grand-total">
                        <td style="padding-top: 8px;">Grand Total:</td>
                        <td class="text-right" style="padding-top: 8px;">{{ number_format($sale->total, 2) }} Tk</td>
                    </tr>
                    <tr>
                        <td style="color: #198754; font-weight: bold;">Paid Amount:</td>
                        <td class="text-right" style="color: #198754; font-weight: bold;">{{ number_format($sale->paid_amount, 2) }} Tk</td>
                    </tr>
                    <tr style="border-top: 1px solid #ddd;">
                        <td style="color: #dc3545; font-weight: bold;">Net Due:</td>
                        <td class="text-right" style="color: #dc3545; font-weight: bold;">{{ number_format($sale->due_amount, 2) }} Tk</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Notes -->
    @if($sale->notes)
        <div class="notes">
            <strong>Notes:</strong> {{ $sale->notes }}
        </div>
    @endif

    <!-- Sold By / Signatures -->
    <table style="width: 100%; margin-top: 40px;">
        <tr>
            <td style="width: 50%; text-align: left;">
                <div style="width: 150px; border-top: 1px solid #000; padding-top: 5px;">Customer Signature</div>
            </td>
            <td style="width: 50%; text-align: right;">
                <p style="margin: 0;">Sold By: <strong>{{ $sale->creator->name ?? 'System' }}</strong></p>
                <div style="width: 150px; border-top: 1px solid #000; padding-top: 5px; margin-left: auto;">Authorized Signature</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        This is a computer-generated document. No signature required for validation. <br>
        <strong>Thank you for your business!</strong>
    </div>

</div>
</body>
</html>
