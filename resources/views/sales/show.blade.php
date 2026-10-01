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
    <!-- Edit Payment Modals -->
    @foreach($sale->payments as $payment)
        <div class="modal fade" id="editPaymentModal{{ $payment->id }}" tabindex="-1" aria-labelledby="editPaymentModalLabel{{ $payment->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('sale-payments.update', $payment->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title" id="editPaymentModalLabel{{ $payment->id }}">
                                <i class="ri-edit-line me-1 text-primary"></i> Edit Payment #{{ $payment->id }}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label text-muted fs-12 mb-0">Payment Date</label>
                                    <div class="fw-semibold">{{ $payment->date ? $payment->date->format('M d, Y') : '-' }}</div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label text-muted fs-12 mb-0">Payment Method</label>
                                    <div class="fw-semibold text-capitalize">{{ $payment->method ?? 'Cash' }}</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label text-muted fs-12 mb-0">Reference</label>
                                    <div class="text-dark">{{ $payment->reference ?: 'Invoice Payment' }}</div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Payment Amount <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">৳</span>
                                    <input type="number" step="0.01" min="1" max="{{ $sale->due_amount + $payment->amount }}" name="amount" class="form-control" value="{{ (float)$payment->amount }}" required>
                                </div>
                                <small class="text-muted d-block mt-1">
                                    Current invoice due: ৳{{ number_format($sale->due_amount, 2) }} | Max allowed: ৳{{ number_format($sale->due_amount + $payment->amount, 2) }}
                                </small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
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
                @if(session('info'))
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        {{ session('info') }}
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
                                                 <td class="text-end text-success">
                                                     <strong>{{ number_format($sale->paid_amount, 0) }}</strong>
                                                     @if($sale->payments->count() > 0)
                                                         <small class="d-block"><a href="#related-payments" class="text-primary text-decoration-underline fs-11">{{ $sale->payments->count() }} payment(s)</a></small>
                                                     @endif
                                                 </td>
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

                 <!-- Related Payments Section -->
                 <div class="card mt-4" id="related-payments">
                     <div class="card-header bg-light d-flex justify-content-between align-items-center">
                         <div class="d-flex align-items-center gap-2">
                             <h4 class="card-title mb-0">
                                 <i class="ri-money-dollar-circle-line me-1 text-primary"></i> Payment Transactions & History
                             </h4>
                             <span class="badge bg-primary-subtle text-primary rounded-pill">{{ $sale->payments->count() }}</span>
                         </div>
                         <div class="d-flex align-items-center gap-2">
                             <span class="badge bg-success-subtle text-success fs-12 px-2 py-1">
                                 <i class="ri-checkbox-circle-line me-1"></i>Total Paid: ৳{{ number_format($sale->paid_amount, 2) }}
                             </span>
                             @if($sale->due_amount > 0)
                                 <span class="badge bg-danger-subtle text-danger fs-12 px-2 py-1">
                                     <i class="ri-error-warning-line me-1"></i>Due: ৳{{ number_format($sale->due_amount, 2) }}
                                 </span>
                                 <a href="#add-payment-card" class="btn btn-xs btn-outline-success ms-1">
                                     <i class="ri-add-line me-1"></i>Add
                                 </a>
                             @else
                                 <span class="badge bg-success fs-12 px-2 py-1">
                                     <i class="ri-check-double-line me-1"></i>Fully Settled
                                 </span>
                             @endif
                         </div>
                     </div>
                     <div class="card-body p-0">
                         @if($sale->payments->count() > 0)
                             <div class="table-responsive">
                                 <table class="table table-hover table-centered mb-0">
                                     <thead class="table-light">
                                         <tr>
                                             <th style="width: 5%">#</th>
                                             <th>Date</th>
                                             <th>Reference / Transaction</th>
                                             <th>Method</th>
                                             <th class="text-end">Amount</th>
                                             <th class="text-center" style="width: 120px;">Actions</th>
                                         </tr>
                                     </thead>
                                     <tbody>
                                         @foreach($sale->payments as $index => $payment)
                                             @php
                                                 $methodLower = strtolower($payment->method ?? 'cash');
                                                 $badgeClass = 'bg-secondary-subtle text-secondary';
                                                 $iconClass = 'ri-secure-payment-line';
                                                 if (str_contains($methodLower, 'cash')) {
                                                     $badgeClass = 'bg-success-subtle text-success';
                                                     $iconClass = 'ri-money-dollar-circle-line';
                                                 } elseif (str_contains($methodLower, 'bank') || str_contains($methodLower, 'card')) {
                                                     $badgeClass = 'bg-primary-subtle text-primary';
                                                     $iconClass = 'ri-bank-line';
                                                 } elseif (str_contains($methodLower, 'bkash') || str_contains($methodLower, 'nagad') || str_contains($methodLower, 'rocket')) {
                                                     $badgeClass = 'bg-warning-subtle text-warning';
                                                     $iconClass = 'ri-smartphone-line';
                                                 } elseif (str_contains($methodLower, 'wallet')) {
                                                     $badgeClass = 'bg-info-subtle text-info';
                                                     $iconClass = 'ri-wallet-3-line';
                                                 }
                                             @endphp
                                             <tr>
                                                 <td class="text-muted">{{ $index + 1 }}</td>
                                                 <td>
                                                     <span class="fw-semibold text-dark">{{ $payment->date ? $payment->date->format('d M, Y') : '-' }}</span>
                                                     @if($payment->created_at)
                                                         <small class="text-muted d-block fs-11">{{ $payment->created_at->format('h:i A') }}</small>
                                                     @endif
                                                 </td>
                                                 <td>
                                                     <span class="fw-medium text-dark">{{ $payment->reference ?: 'Invoice Payment' }}</span>
                                                     <small class="text-muted d-block fs-11">TRX ID: #{{ $payment->id }}</small>
                                                 </td>
                                                 <td>
                                                     <span class="badge {{ $badgeClass }} text-capitalize fs-12 px-2 py-1">
                                                         <i class="{{ $iconClass }} me-1"></i>{{ $payment->method ?? 'Cash' }}
                                                     </span>
                                                 </td>
                                                 <td class="text-end">
                                                     <span class="fw-bold text-success fs-14">৳{{ number_format($payment->amount, 2) }}</span>
                                                 </td>
                                                 <td class="text-center">
                                                     <div class="d-flex justify-content-center gap-1">
                                                         @canany(['edit sale payments', 'edit sales'])
                                                         <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" data-bs-toggle="modal" data-bs-target="#editPaymentModal{{ $payment->id }}" title="Edit Payment">
                                                             <i class="ri-edit-line"></i>
                                                         </button>
                                                         @endcanany

                                                         @canany(['delete sale payments', 'delete sales'])
                                                         <form action="{{ route('sale-payments.destroy', $payment->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this payment of ৳{{ number_format($payment->amount, 2) }}? This will increase the invoice due amount and reverse accounting entries.')">
                                                             @csrf
                                                             @method('DELETE')
                                                             <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="Delete Payment">
                                                                 <i class="ri-delete-bin-line"></i>
                                                             </button>
                                                         </form>
                                                         @endcanany
                                                     </div>
                                                 </td>
                                             </tr>
                                         @endforeach
                                     </tbody>
                                     <tfoot class="table-light">
                                         <tr>
                                             <th colspan="4" class="text-end fw-bold">Total Recorded Payments:</th>
                                             <th class="text-end fw-bold text-success fs-14">৳{{ number_format($sale->payments->sum('amount'), 2) }}</th>
                                             <th></th>
                                         </tr>
                                     </tfoot>
                                 </table>
                             </div>
                         @else
                             <div class="p-4 text-center text-muted">
                                 <i class="ri-money-dollar-circle-line fs-36 text-muted mb-2 d-block"></i>
                                 <p class="mb-1">No payment records found for this invoice.</p>
                                 @if($sale->due_amount > 0)
                                     <a href="#add-payment-card" class="btn btn-sm btn-primary mt-2">
                                         <i class="ri-add-line me-1"></i> Add First Payment
                                     </a>
                                 @endif
                             </div>
                         @endif
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
                 <div class="card" id="add-payment-card">
                     <div class="card-header bg-light d-flex justify-content-between align-items-center">
                         <h4 class="card-title mb-0">Add Payment</h4>
                         <div class="d-flex align-items-center gap-1">
                             <span class="badge bg-success-subtle text-success rounded-pill">Paid: {{ number_format($sale->paid_amount, 0) }}</span>
                             <span class="badge bg-danger rounded-pill">Due: {{ number_format($sale->due_amount, 0) }}</span>
                         </div>
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
                     @if($sale->payments->count() > 0)
                     <div class="card-footer bg-light-subtle py-2 text-center border-top">
                         <a href="#related-payments" class="fs-12 text-primary fw-medium">
                             <i class="ri-history-line me-1"></i> View all {{ $sale->payments->count() }} payment(s) history
                         </a>
                     </div>
                     @endif
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
    <!-- Edit Payment Modals -->
    @foreach($sale->payments as $payment)
        <div class="modal fade" id="editPaymentModal{{ $payment->id }}" tabindex="-1" aria-labelledby="editPaymentModalLabel{{ $payment->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('sale-payments.update', $payment->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title" id="editPaymentModalLabel{{ $payment->id }}">
                                <i class="ri-edit-line me-1 text-primary"></i> Edit Payment #{{ $payment->id }}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label text-muted fs-12 mb-0">Payment Date</label>
                                    <div class="fw-semibold">{{ $payment->date ? $payment->date->format('M d, Y') : '-' }}</div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label text-muted fs-12 mb-0">Payment Method</label>
                                    <div class="fw-semibold text-capitalize">{{ $payment->method ?? 'Cash' }}</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label text-muted fs-12 mb-0">Reference</label>
                                    <div class="text-dark">{{ $payment->reference ?: 'Invoice Payment' }}</div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Payment Amount <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">৳</span>
                                    <input type="number" step="0.01" min="1" max="{{ $sale->due_amount + $payment->amount }}" name="amount" class="form-control" value="{{ (float)$payment->amount }}" required>
                                </div>
                                <small class="text-muted d-block mt-1">
                                    Current invoice due: ৳{{ number_format($sale->due_amount, 2) }} | Max allowed: ৳{{ number_format($sale->due_amount + $payment->amount, 2) }}
                                </small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endsection

