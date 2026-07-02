@extends('layouts.vertical', ['page_title' => 'Variant Details', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('css')
@endsection

@section('content')
    <div class="container-fluid">
         <div class="row">
            <div class="col-12">
                <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                    <h4 class="page-title">Variant Details</h4>
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('product-variants.index') }}">Product Variants</a></li>
                        <li class="breadcrumb-item active">Details</li>
                    </ol>
                </div>
            </div>
         </div>

         <div class="row">
             <div class="col-lg-4">
                 <div class="card">
                     <div class="card-body">
                         <h4 class="header-title mb-3">Variant Information</h4>
                         <table class="table table-borderless mb-0">
                             <tbody>
                                 <tr>
                                     <th class="ps-0" scope="row">Master Product:</th>
                                     <td class="text-muted">{{ $productVariant->product->name }}</td>
                                 </tr>
                                 <tr>
                                     <th class="ps-0" scope="row">Variant Name:</th>
                                     <td class="text-muted">{{ $productVariant->name }}</td>
                                 </tr>
                                 <tr>
                                     <th class="ps-0" scope="row">SKU:</th>
                                     <td class="text-muted">{{ $productVariant->sku }}</td>
                                 </tr>
                                 <tr>
                                     <th class="ps-0" scope="row">Barcode:</th>
                                     <td class="text-muted">{{ $productVariant->barcode ?? 'N/A' }}</td>
                                 </tr>
                                 <tr>
                                     <th class="ps-0" scope="row">Unit:</th>
                                     <td class="text-muted">{{ (float)$productVariant->unit_qty }} {{ $productVariant->unit_type }}</td>
                                 </tr>
                                 <tr>
                                     <th class="ps-0" scope="row">Selling Price:</th>
                                     <td class="text-muted">${{ number_format($productVariant->price, 2) }}</td>
                                 </tr>
                                 <tr>
                                     <th class="ps-0" scope="row">Current Stock:</th>
                                     <td class="text-muted">
                                         @if($productVariant->current_stock > 0)
                                             <span class="badge bg-success">{{ (float)$productVariant->current_stock }}</span>
                                         @else
                                             <span class="badge bg-danger">{{ (float)$productVariant->current_stock }}</span>
                                         @endif
                                     </td>
                                 </tr>
                             </tbody>
                         </table>
                     </div>
                 </div>
             </div>

             <div class="col-lg-8">
                 <div class="card">
                     <div class="card-body">
                         <h4 class="header-title mb-3">Recent Transactions</h4>
                         <div class="table-responsive-sm">
                             <table class="table table-centered table-hover mb-0">
                                 <thead class="table-light">
                                     <tr>
                                         <th>Date</th>
                                         <th>Type</th>
                                         <th>Warehouse</th>
                                         <th>Batch</th>
                                         <th>Qty In</th>
                                         <th>Qty Out</th>
                                         <th>User</th>
                                     </tr>
                                 </thead>
                                 <tbody>
                                     @forelse ($transactions as $transaction)
                                         <tr>
                                             <td>{{ $transaction->date->format('d M Y') }}</td>
                                             <td>
                                                 @if(in_array($transaction->type, ['import', 'opening_balance', 'production', 'transfer_in', 'repackaging_in', 'stock_in', 'adjustment_add']))
                                                     <span class="badge bg-success">{{ ucfirst(str_replace('_', ' ', $transaction->type)) }}</span>
                                                 @else
                                                     <span class="badge bg-danger">{{ ucfirst(str_replace('_', ' ', $transaction->type)) }}</span>
                                                 @endif
                                             </td>
                                             <td>{{ $transaction->warehouse->name ?? 'N/A' }}</td>
                                             <td>{{ $transaction->batch->batch_no ?? 'N/A' }}</td>
                                             <td class="text-success">{{ (float)$transaction->qty_in > 0 ? '+'.(float)$transaction->qty_in : '-' }}</td>
                                             <td class="text-danger">{{ (float)$transaction->qty_out > 0 ? '-'.(float)$transaction->qty_out : '-' }}</td>
                                             <td>{{ $transaction->creator->name ?? 'System' }}</td>
                                         </tr>
                                     @empty
                                         <tr>
                                             <td colspan="7" class="text-center">No transactions found for this variant.</td>
                                         </tr>
                                     @endforelse
                                 </tbody>
                             </table>
                         </div>
                         
                         <div class="mt-3">
                             {{ $transactions->links('pagination::bootstrap-5') }}
                         </div>
                     </div>
                 </div>
             </div>
         </div>
    </div>
@endsection
