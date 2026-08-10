<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Summary Report</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            margin: 0;
            padding: 20px;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .company-name {
            font-size: 24px;
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
            font-size: 18px;
            font-weight: bold;
            margin: 15px 0 5px 0;
            text-transform: uppercase;
        }
        .report-meta {
            font-size: 12px;
            color: #777;
            text-align: center;
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin: 20px 0 10px 0;
            background-color: #f4f4f4;
            padding: 5px 10px;
            border: 1px solid #ddd;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px 10px;
            text-align: left;
        }
        th {
            background-color: #f9f9f9;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .footer {
            margin-top: 50px;
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
        <h2 class="report-title">Stock Summary Report</h2>
        <p class="report-meta">Generated on: {{ \Carbon\Carbon::now()->format('F d, Y h:i A') }}</p>
    </div>

    @if($rawBatches->isNotEmpty())
        <div class="section-title">Raw Materials Stock</div>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    
                    <th>Warehouse</th>
                    <th class="text-right">Weight</th>
                    <th class="text-right"> Cost (৳)</th>
                    <th class="text-right"> Value (৳)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rawBatches->groupBy(fn($b) => $b->product->name) as $productName => $batches)
                    <tr style="background-color: #f4f4f4;">
                        <td colspan="5" style="font-weight: bold;">{{ $productName }}</td>
                    </tr>
                    @foreach($batches as $batch)
                    <tr>
                        <td style="padding-left: 20px;"><span class="text-muted fs-10">Batch: {{ $batch->batch_no }}</span></td>
                        <td>{{ $batch->warehouse->name ?? 'N/A' }}</td>
                        <td class="text-right">{{ number_format($batch->remaining_qty, 2) }} {{ $batch->product->base_unit }}</td>
                        <td class="text-right">{{ number_format($batch->cost_per_unit, 2) }}</td>
                        <td class="text-right">{{ number_format($batch->remaining_qty * $batch->cost_per_unit, 2) }}</td>
                    </tr>
                    @endforeach
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="2" class="text-right">Total</th>
                    <th class="text-right">{{ number_format($rawBatches->sum('remaining_qty'), 2) }}</th>
                    <th></th>
                    <th class="text-right"> {{ number_format($rawBatches->sum(function($batch) { return $batch->remaining_qty * $batch->cost_per_unit; }), 2) }}</th>
                </tr>
            </tfoot>
        </table>
    @endif

    @if($standaloneBatches->isNotEmpty())
        <div class="section-title">Standalone Finished Products Stock</div>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Warehouse</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Cost (৳)</th>
                    <th class="text-right">Value (৳)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($standaloneBatches->groupBy(fn($b) => $b->product->name) as $productName => $batches)
                    <tr style="background-color: #f4f4f4;">
                        <td colspan="5" style="font-weight: bold;">{{ $productName }}</td>
                    </tr>
                    @foreach($batches as $batch)
                    <tr>
                        <td style="padding-left: 20px;"><span class="text-muted fs-10">Batch: {{ $batch->batch_no }}</span></td>
                        <td>{{ $batch->warehouse->name ?? 'N/A' }}</td>
                        <td class="text-right">{{ number_format($batch->remaining_qty, 2) }} {{ $batch->product->base_unit }}</td>
                        <td class="text-right">{{ number_format($batch->cost_per_unit, 2) }}</td>
                        <td class="text-right">{{ number_format($batch->remaining_qty * $batch->cost_per_unit, 2) }}</td>
                    </tr>
                    @endforeach
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="2" class="text-right">Total</th>
                    <th class="text-right">{{ number_format($standaloneBatches->sum('remaining_qty'), 2) }}</th>
                    <th></th>
                    <th class="text-right">{{ number_format($standaloneBatches->sum(function($batch) { return $batch->remaining_qty * $batch->cost_per_unit; }), 2) }}</th>
                </tr>
            </tfoot>
        </table>
    @endif

    @if($packagedBatches->isNotEmpty())
        <div class="section-title">Packaged Variants Stock</div>
        <table>
            <thead>
                <tr>
                    <th>Product - Variant</th>
                
                    <th>Warehouse</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Weight</th>
                    <th class="text-right"> Cost (৳)</th>
                    <th class="text-right"> Value (৳)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($packagedBatches->groupBy(fn($b) => $b->productVariant->product->name) as $productName => $batches)
                    <tr style="background-color: #f4f4f4;">
                        <td colspan="6" style="font-weight: bold;">{{ $productName }}</td>
                    </tr>
                    @foreach($batches as $batch)
                    <tr>
                        <td style="padding-left: 20px;">{{ $batch->productVariant->name }}
                            <br> <span class="text-muted fs-10">Batch: {{ $batch->batch_no }}</span>
                        </td>
                        <td>{{ $batch->warehouse->name ?? 'N/A' }}</td>
                        <td class="text-right">{{ number_format($batch->remaining_qty, 2) }} {{ $batch->productVariant->unit_type }}</td>
                        <td class="text-right">{{ number_format($batch->remaining_qty * $batch->productVariant->unit_qty, 2) }} {{ $batch->productVariant->product->base_unit }}</td>
                        <td class="text-right">{{ number_format($batch->cost_per_unit, 2) }}</td>
                        <td class="text-right">{{ number_format($batch->remaining_qty * $batch->cost_per_unit, 2) }}</td>
                    </tr>
                    @endforeach
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="2" class="text-right">Total</th>
                    <th class="text-right">{{ number_format($packagedBatches->sum('remaining_qty'), 2) }}</th>
                    <th class="text-right">{{ number_format($packagedBatches->sum(function($batch) { return $batch->remaining_qty * $batch->productVariant->unit_qty; }), 2) }}</th>
                    <th></th>
                    <th class="text-right">{{ number_format($packagedBatches->sum(function($batch) { return $batch->remaining_qty * $batch->cost_per_unit; }), 2) }}</th>
                </tr>
            </tfoot>
        </table>
    @endif

    <div class="footer">
        <p>This is a system generated report. For any discrepancies, please contact the inventory department.</p>
        <p>Powered by Radhika's ERP</p>
    </div>

</body>
</html>
