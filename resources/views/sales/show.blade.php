@extends('layouts.vertical', ['page_title' => 'Invoice Details', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('css')
<style>
    .invoice-title {
        font-size: 24px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .invoice-logo {
        max-height: 60px;
    }
</style>
@endsection

@section('content')
    <div class="container-fluid">
         <div class="row">
            <div class="col-12">
                <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                    <h4 class="page-title">Invoice</h4>
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('sales.index') }}">Sales</a></li>
                        <li class="breadcrumb-item active">Invoice</li>
                    </ol>
                </div>
            </div>
         </div>

         <div class="row">
             <div class="col-12">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
             </div>
         </div>

         <div class="row">
             <div class="col-lg-8">
                 <div class="card">
                     <div class="card-body p-4">
                         
                         <!-- Invoice Header -->
                         <div class="row mb-4 pb-3 border-bottom">
                             <div class="col-md-8">
                                 <div class="d-flex align-items-center">
                                     <img src="{{ asset('logo.webp') }}" alt="Logo" style="max-height: 80px; margin-right: 15px;">
                                     <div>
                                         <h3 class="mt-0 mb-1 text-uppercase" style="font-weight: 800; font-size: 26px;">Radhikas Trade International</h3>
                                         <p class="text-muted mb-0" style="font-size: 15px;">88/89, Sadarghat Road, Chattogram, Bangladesh 4000</p>
                                         <p class="text-muted mb-0" style="font-size: 15px;">018 9770 1188, 019 9984 8389, 017 3222 6604 | sales.radhikastradeintl@gmail.com</p>
                                     </div>
                                 </div>
                             </div>
                             <div class="col-md-4 text-md-end">
                                 <h2 class="invoice-title text-primary mb-2 mt-2 mt-md-0" style="font-weight: bold;">INVOICE</h2>
                                 <h5 class="mb-1">Invoice No: #{{ $sale->invoice_no }}</h5>
                                 <p class="text-muted">Date: {{ $sale->date->format('M d, Y') }}</p>
                             </div>
                         </div>

                         <!-- Billing Details -->
                         <div class="row mb-4">
                             <div class="col-md-4">
                                 <h5 class="text-muted text-uppercase mb-3">Bill To</h5>
                                 <h4 class="font-size-16 mb-1">{{ $sale->customer->name ?? 'Walk-in Customer' }}</h4>
                                 @if(isset($sale->customer) && $sale->customer->company)
                                    <p class="mb-0"><b>{{ $sale->customer->company }}</b></p>
                                 @endif
                                 @if(isset($sale->customer) && $sale->customer->address)
                                    <p class="mb-0">{{ $sale->customer->address }}</p>
                                 @endif
                                 @if(isset($sale->customer) && $sale->customer->phone)
                                    <p class="mb-0">Phone: {{ $sale->customer->phone }}</p>
                                 @endif
                             </div>
                             <div class="col-md-4">
                                 <h5 class="text-muted text-uppercase mb-3">Ship To</h5>
                                 @if($sale->shipping_address)
                                     <p class="mb-0">{{ $sale->shipping_address }}</p>
                                 @else
                                     <p class="mb-0 text-muted">Same as billing address</p>
                                 @endif
                             </div>
                             <div class="col-md-4 text-md-end">
                                 <h5 class="text-muted text-uppercase mb-3">Payment Details</h5>
                                 <p class="mb-1"><strong>Status: </strong> 
                                     @if($sale->payment_status == 'paid')
                                         <span class="badge bg-success">Paid</span>
                                     @elseif($sale->payment_status == 'partial')
                                         <span class="badge bg-warning">Partial</span>
                                     @else
                                         <span class="badge bg-danger">Due</span>
                                     @endif
                                 </p>
                                 <p class="mb-1"><strong>Delivery Method:</strong> {{ ucfirst(str_replace('_', ' ', $sale->delivery_method ?? 'None')) }}</p>
                                 @if($sale->delivery_method == 'steadfast' && !empty($sale->consignment_id))
                                     <p class="mb-1"><strong>Consignment ID:</strong> {{ $sale->consignment_id }}</p>
                                 @endif
                                 <p class="mb-0"><strong>Order Status:</strong> 
                                     @php
                                         $statusClass = 'bg-info';
                                         if($sale->delivery_status == 'delivered') $statusClass = 'bg-success';
                                         elseif($sale->delivery_status == 'cancelled') $statusClass = 'bg-danger';
                                         elseif($sale->delivery_status == 'pending' || empty($sale->delivery_status)) $statusClass = 'bg-secondary';
                                     @endphp
                                     <span class="badge {{ $statusClass }}">{{ ucfirst($sale->delivery_status ?? 'Pending') }}</span>
                                 </p>
                             </div>
                         </div>

                         <!-- Items Table -->
                         <div class="row">
                             <div class="col-12">
                                 <div class="table-responsive">
                                     <table class="table mt-2 table-centered table-bordered">
                                         <thead class="table-light">
                                             <tr>
                                                 <th style="width: 5%">#</th>
                                                 <th style="width: 45%">Item</th>
                                                 <th style="width: 10%">Qty</th>
                                                 <th style="width: 15%">Weight</th>
                                                 <th style="width: 10%">Unit Price</th>
                                                 <th style="width: 15%" class="text-end">Total</th>
                                             </tr>
                                         </thead>
                                         <tbody>
                                             @foreach($sale->items as $index => $item)
                                             <tr>
                                                 <td>{{ $index + 1 }}</td>
                                                 <td>
                                                     <b>{{ $item->productVariant->product->name ?? 'Unknown' }}</b> <br/>
                                                     <small class="text-muted">{{ $item->productVariant->name ?? 'Unknown' }}</small>
                                                 </td>
                                                 <td>{{ number_format($item->qty, 0) }}</td>
                                                 <td>{{ number_format($item->total_weight, 2) }} kg</td>
                                                 <td>{{ number_format($item->unit_price, 0) }}</td>
                                                 <td class="text-end">{{ number_format($item->total_price, 0) }}</td>
                                             </tr>
                                             @endforeach
                                         </tbody>
                                     </table>
                                 </div>
                             </div>
                         </div>
                         
                         <!-- Totals -->
                         <div class="row mt-4">
                             <div class="col-sm-6">
                                 <div class="clearfix pt-3">
                                     <h6 class="text-muted">Notes:</h6>
                                     <small class="text-muted">
                                         Thank you for your business. Please make payments within 7 days from the receipt of this invoice.
                                     </small>
                                 </div>
                             </div>
                             <div class="col-sm-6">
                                 <div class="float-end">
                                     <table class="table table-borderless table-sm mb-0">
                                         <tbody>
                                             <tr>
                                                 <td class="text-end text-muted"><strong>Sub-total:</strong></td>
                                                 <td class="text-end">{{ number_format($sale->subtotal, 0) }}</td>
                                             </tr>
                                             <tr>
                                                 <td class="text-end text-muted"><strong>Discount:</strong></td>
                                                 <td class="text-end text-danger">-{{ number_format($sale->discount, 0) }}</td>
                                             </tr>
                                             <tr>
                                                 <td class="text-end text-muted"><strong>Delivery Charge:</strong></td>
                                                 <td class="text-end">{{ number_format($sale->delivery_charge, 0) }}</td>
                                             </tr>
                                             <tr class="border-top border-bottom">
                                                 <td class="text-end"><h4><strong>Grand Total:</strong></h4></td>
                                                 <td class="text-end"><h4><strong>{{ number_format($sale->total, 0) }}</strong></h4></td>
                                             </tr>
                                             <tr>
                                                 <td class="text-end text-muted"><strong>Total Physical Weight:</strong></td>
                                                 <td class="text-end"><strong>{{ number_format($sale->total_weight, 3) }} kg</strong></td>
                                             </tr>
                                             <tr>
                                                 <td class="text-end text-muted">Amount Paid:</td>
                                                 <td class="text-end text-success">{{ number_format($sale->paid_amount, 0) }}</td>
                                             </tr>
                                             <tr>
                                                 <td class="text-end text-muted"><strong>Amount Due:</strong></td>
                                                 <td class="text-end text-danger"><strong>{{ number_format($sale->due_amount, 0) }}</strong></td>
                                             </tr>
                                         </tbody>
                                     </table>
                                 </div>
                                 <div class="clearfix"></div>
                             </div>
                         </div>

                         <!-- Action Buttons -->
                         <div class="d-print-none mt-5">
                             <div class="text-end">
                                 <a href="{{ route('sales.edit', $sale->id) }}" class="btn btn-warning"><i class="ri-edit-line me-1"></i> Edit Sale</a>
                                 <a href="{{ route('sales.print', $sale->id) }}" target="_blank" class="btn btn-primary ms-1"><i class="ri-printer-line me-1"></i> Print Invoice</a>
                                 <a href="{{ route('sales.pdf', $sale->id) }}" class="btn btn-danger ms-1"><i class="ri-file-download-line me-1"></i> Download PDF</a>
                                 <a href="{{ route('sales.index') }}" class="btn btn-light ms-1">Back</a>
                             </div>
                         </div>

                     </div>
                 </div>
             </div>
             <!-- Sidebar -->
             <div class="col-lg-4">
                 <!-- Stock & Delivery Status Card -->
                 @php
                     $isStockDeducted = in_array($sale->delivery_status, ['dispatched', 'delivered']);
                 @endphp
                 @if($isStockDeducted)
                     <div class="alert alert-success d-flex align-items-center mb-3 py-2 px-3">
                         <i class="ri-checkbox-circle-fill fs-18 me-2 text-success"></i>
                         <div>
                             <strong class="d-block text-success">Stock Deducted</strong>
                             <span class="fs-12 text-muted">Items consumed from warehouse stock ({{ ucfirst($sale->delivery_status) }}).</span>
                         </div>
                     </div>
                 @else
                     <div class="alert alert-warning d-flex align-items-center mb-3 py-2 px-3">
                         <i class="ri-time-line fs-18 me-2 text-warning"></i>
                         <div>
                             <strong class="d-block text-warning">Stock Pending Fulfillment</strong>
                             <span class="fs-12 text-muted">Stock will be deducted once status is updated to <strong>Dispatched</strong> or <strong>Delivered</strong>.</span>
                         </div>
                     </div>
                 @endif

                  @if($sale->consignment_id)
                      <div class="card mb-3 border-info border">
                          <div class="card-body p-3">
                              <div class="d-flex align-items-center justify-content-between mb-2">
                                  <div>
                                      <span class="badge bg-info-subtle text-info fs-12 fw-semibold"><i class="ri-truck-line me-1"></i> Steadfast Courier</span>
                                  </div>
                                  <form action="{{ route('sales.syncSingleSteadfast', $sale->id) }}" method="POST" class="d-inline">
                                      @csrf
                                      <button type="submit" class="btn btn-sm btn-outline-info rounded-pill">
                                          <i class="ri-refresh-line me-1"></i> Sync Status
                                      </button>
                                  </form>
                              </div>
                              <div class="fs-13 text-muted">
                                  <div><strong>Consignment ID:</strong> <code>{{ $sale->consignment_id }}</code></div>
                                  <div><strong>Current Status:</strong> <span class="badge bg-primary">{{ ucfirst($sale->delivery_status ?? 'Pending') }}</span></div>
                              </div>
                          </div>
                      </div>
                  @endif

                 <!-- Status and Notes Update Form -->
                 <div class="card mb-4">
                     <div class="card-header bg-light">
                         <h4 class="card-title mb-0">Update Status & Notes</h4>
                     </div>
                     <div class="card-body">
                         <form action="{{ route('sales.updateDetails', $sale->id) }}" method="POST">
                             @csrf
                             <div class="mb-3">
                                 <label class="form-label">Payment Status</label>
                                 <select name="payment_status" class="form-select">
                                     <option value="paid" {{ $sale->payment_status == 'paid' ? 'selected' : '' }}>Paid</option>
                                     <option value="partial" {{ $sale->payment_status == 'partial' ? 'selected' : '' }}>Partial</option>
                                     <option value="due" {{ $sale->payment_status == 'due' ? 'selected' : '' }}>Due</option>
                                 </select>
                             </div>
                             <div class="mb-3">
                                 <label class="form-label">Delivery Status</label>
                                 <select name="delivery_status" class="form-select">
                                     <option value="pending" {{ empty($sale->delivery_status) || $sale->delivery_status == 'pending' ? 'selected' : '' }}>Pending (No stock deduction)</option>
                                     <option value="accepted" {{ $sale->delivery_status == 'accepted' ? 'selected' : '' }}>Accepted</option>
                                     <option value="processing" {{ $sale->delivery_status == 'processing' ? 'selected' : '' }}>Processing</option>
                                     <option value="dispatched" {{ $sale->delivery_status == 'dispatched' ? 'selected' : '' }}>Dispatched (Deducts stock)</option>
                                     <option value="delivered" {{ $sale->delivery_status == 'delivered' ? 'selected' : '' }}>Delivered (Deducts stock)</option>
                                     <option value="cancelled" {{ $sale->delivery_status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                 </select>
                                 <small class="text-muted d-block mt-1 fs-11">
                                     <i class="ri-information-line"></i> Marking <strong>Dispatched</strong> or <strong>Delivered</strong> consumes stock. Reverting restores stock.
                                 </small>
                             </div>
                             <div class="mb-3">
                                 <label class="form-label">Order Notes</label>
                                 <textarea name="notes" class="form-control" rows="3" placeholder="Add notes here...">{{ $sale->notes }}</textarea>
                             </div>
                             <div class="text-end">
                                 <button type="submit" class="btn btn-primary btn-sm">Update Details</button>
                             </div>
                         </form>
                     </div>
                 </div>

                 <!-- Add Payment Form -->
                 <div class="card">
                     <div class="card-header bg-light d-flex justify-content-between align-items-center">
                         <h4 class="card-title mb-0">Add Payment</h4>
                         <span class="badge bg-danger rounded-pill">Due: {{ number_format($sale->due_amount, 0) }}</span>
                     </div>
                     <div class="card-body">
                         @if($sale->due_amount > 0)
                         <form action="{{ route('sale-payments.store', $sale->id) }}" method="POST">
                             @csrf
                             <div class="mb-3">
                                 <label class="form-label">Amount</label>
                                 <input type="number" step="1" name="amount" class="form-control" max="{{ $sale->due_amount }}" required>
                             </div>
                             <div class="mb-3">
                                 <label class="form-label">Payment Method</label>
                                 <select name="method" class="form-select" required>
                                     @foreach($paymentMethods as $method)
                                         <option value="{{ $method->id }}">{{ $method->name }}</option>
                                     @endforeach
                                 </select>
                             </div>
                             <div class="text-end">
                                 <button type="submit" class="btn btn-success btn-sm">Record Payment</button>
                             </div>
                         </form>
                         @else
                             <div class="alert alert-success mb-0 text-center">
                                 <i class="ri-check-line align-middle me-1"></i> Invoice is fully paid.
                             </div>
                         @endif
                    </div>
                 </div>

                 <!-- Tracking Timeline -->
                 @if(!empty($sale->tracking_updates))
                 <div class="card mt-4">
                     <div class="card-header bg-light">
                         <h4 class="card-title mb-0"><i class="ri-flight-takeoff-line me-1"></i> Tracking Updates</h4>
                     </div>
                     <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                         <ul class="list-group list-group-flush">
                             @foreach(collect($sale->tracking_updates)->sortByDesc('date') as $update)
                             <li class="list-group-item">
                                 <div class="d-flex w-100 justify-content-between">
                                     <h6 class="mb-1 text-primary">{{ ucfirst(str_replace('_', ' ', $update['status'] ?? 'update')) }}</h6>
                                     <small class="text-muted" style="font-size: 0.75rem;">{{ \Carbon\Carbon::parse($update['date'])->format('d M, h:i A') }}</small>
                                 </div>
                                 <p class="mb-1 small">{{ $update['message'] ?? '' }}</p>
                             </li>
                             @endforeach
                         </ul>
                     </div>
                 </div>
                 @endif

                 <!-- Order Updates History -->
                 <div class="card mt-4">
                     <div class="card-header bg-light">
                         <h4 class="card-title mb-0"><i class="ri-history-line me-1"></i> Order Updates History</h4>
                     </div>
                     <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                         <ul class="list-group list-group-flush">
                             @forelse($sale->activities as $activity)
                             <li class="list-group-item">
                                 <div class="d-flex w-100 justify-content-between">
                                     <h6 class="mb-1 text-primary">{{ ucfirst(str_replace('_', ' ', $activity->action)) }}</h6>
                                     <small class="text-muted" style="font-size: 0.75rem;">{{ $activity->created_at->format('d M, h:i A') }}</small>
                                 </div>
                                 <p class="mb-1 small">{{ $activity->description }}</p>
                                 <small class="text-muted" style="font-size: 0.70rem;"><i class="ri-user-line"></i> {{ $activity->user->name ?? 'System' }}</small>
                             </li>
                             @empty
                             <li class="list-group-item text-center text-muted">
                                 <small>No history found for this order.</small>
                             </li>
                             @endforelse
                         </ul>
                     </div>
                 </div>

             </div> <!-- end col-lg-4 -->
         </div>
    </div>
@endsection

