@extends('layouts.vertical', ['page_title' => 'Import Shipment Details', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('content')
    <div class="container-fluid">
         <div class="row">
            <div class="col-12">
                <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                    <h4 class="page-title">Import Details</h4>
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('imports.index') }}">Imports</a></li>
                        <li class="breadcrumb-item active">{{ $import->import_no }}</li>
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
                                 <h2 class="mb-1 fw-bold text-dark">Shipment {{ $import->import_no }}</h2>
                                 <p class="text-muted mb-0"><i class="ri-calendar-event-line"></i> Imported on {{ $import->date->format('F d, Y') }}</p>
                             </div>
                             <div class="text-end">
                                 <div class="d-flex gap-2 justify-content-end">
                                    <a href="{{ route('imports.edit', $import->id) }}" class="btn btn-soft-primary"><i class="ri-edit-line"></i> Edit</a>
                                    
                                    @can('delete imports')
                                    <form action="{{ route('imports.destroy', $import->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-soft-danger" onclick="return confirm('Are you sure you want to delete this Import? This will reverse the supplier payable and remove stock from the warehouse. Action cannot be undone if stock is already consumed.')">
                                            <i class="ri-delete-bin-line"></i> Delete
                                        </button>
                                    </form>
                                    @endcan
                                    
                                    <button class="btn btn-soft-dark" onclick="window.print()"><i class="ri-printer-line"></i> Print</button>
                                 </div>
                             </div>
                         </div>

                         <!-- Details Section -->
                         <div class="row mb-5">
                             <div class="col-sm-6">
                                 <h6 class="text-uppercase text-muted fs-12 fw-bold mb-2">Supplier Details</h6>
                                 <div class="p-3 bg-light rounded">
                                     <h5 class="mb-1 text-dark fw-semibold"><i class="ri-building-line text-primary me-1"></i> {{ $import->supplier->name ?? 'N/A' }}</h5>
                                     @if($import->supplier)
                                        <p class="mb-0 text-muted fs-13"><i class="ri-phone-line me-1"></i> {{ $import->supplier->phone ?? 'No phone' }}</p>
                                        <p class="mb-0 text-muted fs-13"><i class="ri-map-pin-line me-1"></i> {{ $import->supplier->address ?? 'No address provided' }}</p>
                                     @endif
                                 </div>
                             </div>
                             <div class="col-sm-6">
                                 <h6 class="text-uppercase text-muted fs-12 fw-bold mb-2 mt-3 mt-sm-0">Destination Warehouse</h6>
                                 <div class="p-3 bg-light rounded h-100">
                                     <h5 class="mb-1 text-dark fw-semibold"><i class="ri-store-2-line text-success me-1"></i> {{ $import->warehouse->name ?? 'N/A' }}</h5>
                                     @if($import->warehouse)
                                        <p class="mb-0 text-muted fs-13"><i class="ri-map-pin-line me-1"></i> {{ $import->warehouse->location ?? 'No location provided' }}</p>
                                     @endif
                                 </div>
                             </div>
                         </div>

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
                                     @foreach ($import->items as $index => $item)
                                         <tr>
                                             <td class="fw-semibold text-muted">{{ $index + 1 }}</td>
                                             <td>
                                                 <span class="fw-semibold text-dark">{{ $item->product->name }}</span>
                                             </td>
                                             <td class="text-end">
                                                 <span class="badge bg-primary-subtle text-primary px-2 py-1 fs-13">{{ number_format($item->qty, 3) }} {{ $item->product->base_unit }}</span>
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
                                             // Find the debit entry for Accounts Payable to get the amount paid to the supplier
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
                                             <td colspan="4" class="text-center text-muted py-3">No payments explicitly linked to this import yet. <br> <small>(To link a payment, include the import number <b>{{ $import->import_no }}</b> in the reference or notes when making a supplier payment.)</small></td>
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
                                     <p class="mb-2 fs-15">Subtotal: <span class="fw-semibold ms-2">${{ number_format($import->total_cost, 0) }}</span></p>
                                     <h3 class="fw-bold mt-3 mb-0 text-success">Grand Total: ${{ number_format($import->total_cost, 0) }}</h3>
                                 </div>
                             </div>
                         </div>
                         
                         <div class="d-print-none mt-5 text-center">
                             <a href="{{ route('imports.index') }}" class="btn btn-light"><i class="ri-arrow-left-line me-1"></i> Back to Imports List</a>
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

