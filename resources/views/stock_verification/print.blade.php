<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Integrity Audit Report - Radhikas Trade International</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .header h2 {
            margin: 0 0 5px 0;
            text-transform: uppercase;
        }
        .header p {
            margin: 2px 0;
            color: #666;
        }
        .kpi-box {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding: 8px 12px;
            background: #f4f6f9;
            border: 1px solid #ddd;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .badge-success { color: #198754; font-weight: bold; }
        .badge-danger { color: #dc3545; font-weight: bold; }
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 15px;">
        <button onclick="window.print()" style="padding: 6px 12px; font-weight: bold; cursor: pointer;">
            🖨️ Print Report
        </button>
    </div>

    <div class="header">
        <h2>Radhikas Trade International</h2>
        <p><strong>Stock Integrity & Multi-Layer Verification Audit Report</strong></p>
        <p>Generated At: {{ now()->format('d M Y, h:i A') }} | User: {{ auth()->user()->name ?? 'Administrator' }}</p>
    </div>

    <div class="kpi-box">
        <div><strong>Integrity Score:</strong> {{ $systemIntegrityScore }}% Verified</div>
        <div><strong>Total Finished Units:</strong> {{ number_format($totalFinishedUnits, 0) }}</div>
        <div><strong>Raw Materials Stock:</strong> {{ number_format($totalRawStock, 1) }} kg</div>
        <div><strong>Discrepancies:</strong> {{ count($discrepancies) }} detected</div>
    </div>

    <h3>1. Commercial Product Variants</h3>
    <table>
        <thead>
            <tr>
                <th>Item / Variant</th>
                <th>SKU</th>
                @foreach($warehouses as $wh)
                    <th class="text-center">{{ $wh->name }} (L / B / C)</th>
                @endforeach
                <th class="text-end">Total WH Sum</th>
                <th class="text-end">Global Stock</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($variantRows as $row)
                @php
                    $sumWs = 0;
                    foreach($row['warehouses'] as $whData) {
                        $sumWs += $whData['ws'];
                    }
                @endphp
                <tr>
                    <td><strong>{{ $row['product_name'] }}</strong> - {{ $row['variant_name'] }}</td>
                    <td>{{ $row['sku'] }}</td>
                    @foreach($warehouses as $wh)
                        @php $whData = $row['warehouses'][$wh->id] ?? null; @endphp
                        <td class="text-center">
                            @if($whData)
                                {{ (float)$whData['ledger'] }} / {{ (float)$whData['batch'] }} / {{ (float)$whData['ws'] }}
                            @else
                                0
                            @endif
                        </td>
                    @endforeach
                    <td class="text-end fw-bold">{{ (float)$sumWs }} {{ $row['unit'] }}</td>
                    <td class="text-end fw-bold">{{ (float)$row['current_stock'] }} {{ $row['unit'] }}</td>
                    <td class="text-center">
                        @if($row['is_perfect'])
                            <span class="badge-success">✓ Matched</span>
                        @else
                            <span class="badge-danger">✗ Mismatch</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h3>2. Bulk Raw Materials</h3>
    <table>
        <thead>
            <tr>
                <th>Raw Material</th>
                <th>SKU</th>
                <th>Base Unit</th>
                @foreach($warehouses as $wh)
                    <th class="text-center">{{ $wh->name }} (L / B / C)</th>
                @endforeach
                <th class="text-end">Total Stock</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rawRows as $raw)
                <tr>
                    <td><strong>{{ $raw['name'] }}</strong></td>
                    <td>{{ $raw['sku'] }}</td>
                    <td>{{ $raw['unit'] }}</td>
                    @foreach($warehouses as $wh)
                        @php $whData = $raw['warehouses'][$wh->id] ?? null; @endphp
                        <td class="text-center">
                            @if($whData)
                                {{ (float)$whData['ledger'] }} / {{ (float)$whData['batch'] }} / {{ (float)$whData['ws'] }}
                            @else
                                0
                            @endif
                        </td>
                    @endforeach
                    <td class="text-end fw-bold">{{ (float)$raw['total_stock'] }} {{ $raw['unit'] }}</td>
                    <td class="text-center">
                        @if($raw['is_perfect'])
                            <span class="badge-success">✓ Matched</span>
                        @else
                            <span class="badge-danger">✗ Mismatch</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 30px; font-size: 11px; color: #666; border-top: 1px solid #ddd; padding-top: 5px;">
        <p><em>Legend: L = Ledger Net (Inventory Transaction SUM), B = Active Batch Remaining SUM, C = Instantaneous Warehouse Stock Cache.</em></p>
    </div>
</body>
</html>

