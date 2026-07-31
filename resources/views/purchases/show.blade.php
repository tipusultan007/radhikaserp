@extends('layouts.vertical', ['page_title' => 'Purchase Shipment Details', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('content')
    <div class="container-fluid">
         <div class="row">
            <div class="col-12">
                <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                    <h4 class="page-title">Purchase Details</h4>
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('purchases.index') }}">Purchases</a></li>
                        <li class="breadcrumb-item active">{{ $purchase->purchase_no }}</li>
                    </ol>
                </div>
            </div>
         </div>

         <div class="row">
             <div class="col-lg-12">
                 <div class="card shadow-sm border-0">
                     <div class="card-body p-5">
                         <!-- Header Section -->
                         
                         @if ($errors->any())
                             <div class="alert alert-danger">
                                 <ul class="mb-0">
                                     @foreach ($errors->all() as $error)
                                         <li>{{ $error }}</li>
                                     @endforeach
                                 </ul>
                             </div>
                         @endif
                         
                         <div class="d-flex justify-content-between align-items-center border-bottom pb-4 mb-4">
                             <div>
                                 <div class="d-flex align-items-center gap-2">
                                     <h2 class="mb-1 fw-bold text-dark">Shipment {{ $purchase->purchase_no }}</h2>
                                     @if($purchase->purchase_type === 'local')
                                         <span class="badge bg-success-subtle text-success fs-13 px-2 py-1"><i class="ri-map-pin-user-line me-1"></i> Local Purchase</span>
                                     @else
                                         <span class="badge bg-primary-subtle text-primary fs-13 px-2 py-1"><i class="ri-global-line me-1"></i> Imported Purchase</span>
                                     @endif
                                 </div>
                                 <p class="text-muted mb-0 mt-1"><i class="ri-calendar-event-line"></i> Purchased on {{ $purchase->date->format('F d, Y') }}</p>
                             </div>
                             <div class="text-end">
                                 <div class="d-flex gap-2 justify-content-end">
                                    @canany(['edit purchases', 'manage purchases'])
                                    <a href="{{ route('purchases.edit', $purchase->id) }}" class="btn btn-soft-primary"><i class="ri-edit-line"></i> Edit</a>
                                    @endcanany
                                    
                                    @canany(['delete purchases', 'manage purchases'])
                                    <form action="{{ route('purchases.destroy', $purchase->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-soft-danger" onclick="return confirm('Are you sure you want to delete this Purchase? This will reverse the supplier payable and remove stock from the warehouse. Action cannot be undone if stock is already consumed.')">
                                            <i class="ri-delete-bin-line"></i> Delete
                                        </button>
                                    </form>
                                    @endcanany
                                    
                                    <button class="btn btn-soft-dark" onclick="window.print()"><i class="ri-printer-line"></i> Print</button>
                                 </div>
                             </div>
                         </div>

                         <!-- Details Section -->
                         <div class="row mb-5">
                             <div class="col-sm-6">
                                 <h6 class="text-uppercase text-muted fs-12 fw-bold mb-2">Supplier Details</h6>
                                 <div class="p-3 bg-light rounded">
                                     <h5 class="mb-1 text-dark fw-semibold"><i class="ri-building-line text-primary me-1"></i> {{ $purchase->supplier->name ?? 'N/A' }}</h5>
                                     @if($purchase->supplier)
                                        <p class="mb-0 text-muted fs-13"><i class="ri-phone-line me-1"></i> {{ $purchase->supplier->phone ?? 'No phone' }}</p>
                                        <p class="mb-0 text-muted fs-13"><i class="ri-map-pin-line me-1"></i> {{ $purchase->supplier->address ?? 'No address provided' }}</p>
                                     @endif
                                 </div>
                             </div>
                             <div class="col-sm-6">
                                 <h6 class="text-uppercase text-muted fs-12 fw-bold mb-2 mt-3 mt-sm-0">Destination Warehouse</h6>
                                 <div class="p-3 bg-light rounded h-100">
                                     <h5 class="mb-1 text-dark fw-semibold"><i class="ri-store-2-line text-success me-1"></i> {{ $purchase->warehouse->name ?? 'N/A' }}</h5>
                                     @if($purchase->warehouse)
                                        <p class="mb-0 text-muted fs-13"><i class="ri-map-pin-line me-1"></i> {{ $purchase->warehouse->location ?? 'No location provided' }}</p>
                                     @endif
                                 </div>
                             </div>
                         </div>

                         <!-- Landed Cost / Delivery Expenses Section -->
                         @if($purchase->purchase_type === 'local')
                             <h6 class="text-uppercase text-muted fs-12 fw-bold mb-3">Local Purchase Expenses</h6>
                             <div class="p-3 bg-light rounded mb-4 d-flex justify-content-between align-items-center">
                                 <span class="fw-semibold text-dark fs-14"><i class="ri-truck-line me-1 text-primary"></i> Delivery Cost</span>
                                 <span class="fw-bold fs-15 text-dark">${{ number_format($purchase->delivery_cost, 2) }}</span>
                             </div>
                         @elseif(!empty($purchase->cost_breakdown))
                             <h6 class="text-uppercase text-muted fs-12 fw-bold mb-3">Imported Landed Cost Breakdown</h6>
                             <div class="table-responsive mb-4">
                                 <table class="table table-striped table-hover table-bordered mb-0">
                                     <thead class="table-light">
                                         <tr>
                                             <th width="5%" class="text-center">#</th>
                                             <th>Description</th>
                                             <th class="text-end" width="25%">Amount (৳)</th>
                                         </tr>
                                     </thead>
                                     <tbody>
                                         @foreach ($purchase->cost_breakdown as $idx => $costRow)
                                             @php
                                                 $amt = $costRow['amount'] ?? ($costRow['bd_cost'] ?? 0);
                                             @endphp
                                             <tr>
                                                 <td class="text-center fw-semibold text-muted">{{ $idx + 1 }}</td>
                                                 <td class="fw-semibold text-dark">{{ $costRow['description'] ?? '' }}</td>
                                                 <td class="text-end fw-bold">{{ !empty($amt) ? '৳' . number_format($amt, 2) : '-' }}</td>
                                             </tr>
                                         @endforeach
                                     </tbody>
                                     <tfoot>
                                         <tr class="table-light">
                                             <td colspan="2" class="text-end fw-bold">Total Landed Cost:</td>
                                             <td class="text-end fw-bold text-success fs-5">৳{{ number_format($purchase->total_landed_cost, 2) }}</td>
                                         </tr>
                                     </tfoot>
                                 </table>
                             </div>
                         @endif

                         <!-- Table Section -->
                         <h6 class="text-uppercase text-muted fs-12 fw-bold mb-3">Received Items</h6>
                         <div class="table-responsive">
                             <table class="table table-striped table-hover table-bordered mb-0">
                                 <thead class="table-light">
                                     <tr>
                                         <th width="5%">#</th>
                                         <th>Product (Raw Stock)</th>
                                         <th class="text-end" width="15%">Quantity</th>
                                         <th class="text-end" width="20%">Unit Cost</th>
                                         <th class="text-end" width="20%">Total Cost</th>
                                     </tr>
                                 </thead>
                                 <tbody>
                                     @foreach ($purchase->items as $index => $item)
                                         <tr>
                                             <td class="fw-semibold text-muted">{{ $index + 1 }}</td>
                                             <td>
                                                 <span class="fw-semibold text-dark">{{ $item->product->name }}</span>
                                             </td>
                                             <td class="text-end">
                                                 <span class="badge bg-primary-subtle text-primary px-2 py-1 fs-13">{{ number_format($item->qty, 3) }} {{ $item->product && $item->product->unit ? $item->product->unit->short_name : 'Unit' }}</span>
                                             </td>
                                             <td class="text-end">${{ number_format($item->unit_cost, 0) }}</td>
                                             <td class="text-end fw-semibold">${{ number_format($item->total_cost, 0) }}</td>
                                         </tr>
                                     @endforeach
                                 </tbody>
                             </table>
                         </div>
                         
                         <!-- Related Payments Section -->
                         <h6 class="text-uppercase text-muted fs-12 fw-bold mb-3 mt-5">Related Payments</h6>
                         <div class="table-responsive mb-4">
                             <table class="table table-striped table-hover table-bordered mb-0">
                                 <thead class="table-light">
                                     <tr>
                                         <th width="15%">Date</th>
                                         <th width="20%">Journal No</th>
                                         <th>Notes / Reference</th>
                                         <th class="text-end" width="20%">Amount Paid</th>
                                     </tr>
                                 </thead>
                                 <tbody>
                                     @forelse ($relatedPayments as $payment)
                                         @php
                                             $debitEntry = $payment->entries->where('type', 'debit')->first();
                                             $amount = $debitEntry ? $debitEntry->amount : 0;
                                         @endphp
                                         <tr>
                                             <td>{{ $payment->date->format('Y-m-d') }}</td>
                                             <td><span class="fw-semibold">{{ $payment->journal_no }}</span></td>
                                             <td>{{ $payment->notes }}</td>
                                             <td class="text-end fw-semibold text-success">${{ number_format($amount, 2) }}</td>
                                         </tr>
                                     @empty
                                         <tr>
                                             <td colspan="4" class="text-center text-muted py-3">No payments explicitly linked to this purchase yet. <br> <small>(To link a payment, include the purchase number <b>{{ $purchase->purchase_no }}</b> in the reference or notes when making a supplier payment.)</small></td>
                                         </tr>
                                     @endforelse
                                 </tbody>
                             </table>
                         </div>
                         
                         <!-- Summary Footer -->
                         <div class="row mt-4">
                             <div class="col-sm-6">
                                 <div class="clearfix pt-3">
                                     <h6 class="text-muted">Notes:</h6>
                                     <small class="text-muted">
                                         This shipment has been processed and inventory levels have been adjusted accordingly. 
                                         Any changes made to this shipment will automatically recalculate the associated inventory transactions.
                                     </small>
                                 </div>
                             </div>
                             <div class="col-sm-6 text-end">
                                 <div class="mt-3 mt-sm-0">
                                     <p class="mb-2 fs-15">Items Total Cost: <span class="fw-semibold ms-2">${{ number_format($purchase->total_cost, 2) }}</span></p>
                                     @if($purchase->purchase_type === 'local' && $purchase->delivery_cost > 0)
                                         <p class="mb-2 fs-15">Delivery Cost: <span class="fw-semibold ms-2">${{ number_format($purchase->delivery_cost, 2) }}</span></p>
                                     @elseif($purchase->purchase_type === 'imported' && $purchase->total_landed_cost > 0)
                                         <p class="mb-2 fs-15">Landed Expenses: <span class="fw-semibold ms-2">৳{{ number_format($purchase->total_landed_cost, 2) }}</span></p>
                                     @endif
                                     <h3 class="fw-bold mt-3 mb-0 text-success">Grand Total: ${{ number_format($purchase->total_cost, 2) }}</h3>
                                 </div>
                             </div>
                         </div>
                         
                         <div class="d-print-none mt-5 text-center">
                             <a href="{{ route('purchases.index') }}" class="btn btn-light"><i class="ri-arrow-left-line me-1"></i> Back to Purchases List</a>
                         </div>

                     </div>
                 </div>
             </div>
          </div>
    </div>
@endsection

@section('css')
<style>
    @media print {
        .page-title-box, .breadcrumb, .btn, .d-print-none, .topbar-custom, .leftside-menu {
            display: none !important;
        }
        .content-page {
            margin-left: 0 !important;
            padding: 0 !important;
        }
        .card {
            border: none !important;
            box-shadow: none !important;
        }
        body {
            background-color: white;
        }
    }
</style>
@endsection
