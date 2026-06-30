@extends('layouts.vertical', ['page_title' => 'Sales Invoices', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('css')
@endsection

@section('content')
    <div class="container-fluid">
         <div class="row">
            <div class="col-12">
                <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                    <h4 class="page-title">Sales Invoices</h4>
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                        <li class="breadcrumb-item active">Sales</li>
                    </ol>
                </div>
            </div>
         </div>

         <div class="row mb-2">
             <div class="col-12 text-end">
                 <a href="{{ route('pos.index') }}" class="btn btn-primary rounded-pill mb-2"><i class="ri-add-line me-1"></i> Add New Sale</a>
                 <a href="{{ route('sales.export', request()->all()) }}" class="btn btn-success rounded-pill mb-2"><i class="ri-file-excel-2-line me-1"></i> Export Excel</a>
             </div>
         </div>

         <div class="row">
             <div class="col-md mb-3">
                 <a href="{{ route('sales.index') }}" class="text-decoration-none">
                     @php $isActive = !request()->filled('delivery_status'); @endphp
                     <div class="card text-center h-100 {{ $isActive ? 'bg-primary text-white' : 'border-primary border shadow-none' }}">
                         <div class="card-body">
                             <i class="ri-bar-chart-box-line fs-24 {{ $isActive ? '' : 'text-primary' }}"></i>
                             <h6 class="mt-1 mb-2 text-uppercase fw-semibold {{ $isActive ? 'text-white' : 'text-primary' }}">TOTAL SALES</h6>
                             <h3 class="mb-0 {{ $isActive ? 'text-white' : 'text-dark' }}">{{ $totalSalesCount }}</h3>
                         </div>
                     </div>
                 </a>
             </div>
             <div class="col-md mb-3">
                 <a href="{{ route('sales.index', ['delivery_status' => 'pending']) }}" class="text-decoration-none">
                     @php $isActive = request('delivery_status') == 'pending'; @endphp
                     <div class="card text-center h-100 {{ $isActive ? 'bg-warning text-white' : 'border-warning border shadow-none' }}">
                         <div class="card-body">
                             <i class="ri-time-line fs-24 {{ $isActive ? '' : 'text-warning' }}"></i>
                             <h6 class="mt-1 mb-2 text-uppercase fw-semibold {{ $isActive ? 'text-white' : 'text-warning' }}">PENDING</h6>
                             <h3 class="mb-0 {{ $isActive ? 'text-white' : 'text-dark' }}">{{ $pendingSalesCount }}</h3>
                         </div>
                     </div>
                 </a>
             </div>
             <div class="col-md mb-3">
                 <a href="{{ route('sales.index', ['delivery_status' => 'accepted']) }}" class="text-decoration-none">
                     @php $isActive = request('delivery_status') == 'accepted'; @endphp
                     <div class="card text-center h-100 {{ $isActive ? 'bg-secondary text-white' : 'border-secondary border shadow-none' }}">
                         <div class="card-body">
                             <i class="ri-check-double-line fs-24 {{ $isActive ? '' : 'text-secondary' }}"></i>
                             <h6 class="mt-1 mb-2 text-uppercase fw-semibold {{ $isActive ? 'text-white' : 'text-secondary' }}">ACCEPTED</h6>
                             <h3 class="mb-0 {{ $isActive ? 'text-white' : 'text-dark' }}">{{ $acceptedSalesCount }}</h3>
                         </div>
                     </div>
                 </a>
             </div>
             <div class="col-md mb-3">
                 <a href="{{ route('sales.index', ['delivery_status' => 'processing']) }}" class="text-decoration-none">
                     @php $isActive = request('delivery_status') == 'processing'; @endphp
                     <div class="card text-center h-100 {{ $isActive ? 'bg-info text-white' : 'border-info border shadow-none' }}">
                         <div class="card-body">
                             <i class="ri-loader-4-line fs-24 {{ $isActive ? '' : 'text-info' }}"></i>
                             <h6 class="mt-1 mb-2 text-uppercase fw-semibold {{ $isActive ? 'text-white' : 'text-info' }}">PROCESSING</h6>
                             <h3 class="mb-0 {{ $isActive ? 'text-white' : 'text-dark' }}">{{ $processingSalesCount }}</h3>
                         </div>
                     </div>
                 </a>
             </div>
             <div class="col-md mb-3">
                 <a href="{{ route('sales.index', ['delivery_status' => 'dispatched']) }}" class="text-decoration-none">
                     @php $isActive = request('delivery_status') == 'dispatched'; @endphp
                     <div class="card text-center h-100 {{ $isActive ? 'bg-primary text-white' : 'border-primary border shadow-none' }}" style="--bs-bg-opacity: .8;">
                         <div class="card-body">
                             <i class="ri-truck-line fs-24 {{ $isActive ? '' : 'text-primary' }}"></i>
                             <h6 class="mt-1 mb-2 text-uppercase fw-semibold {{ $isActive ? 'text-white' : 'text-primary' }}">DISPATCHED</h6>
                             <h3 class="mb-0 {{ $isActive ? 'text-white' : 'text-dark' }}">{{ $dispatchedSalesCount }}</h3>
                         </div>
                     </div>
                 </a>
             </div>
             <div class="col-md mb-3">
                 <a href="{{ route('sales.index', ['delivery_status' => 'delivered']) }}" class="text-decoration-none">
                     @php $isActive = request('delivery_status') == 'delivered'; @endphp
                     <div class="card text-center h-100 {{ $isActive ? 'bg-success text-white' : 'border-success border shadow-none' }}">
                         <div class="card-body">
                             <i class="ri-checkbox-circle-line fs-24 {{ $isActive ? '' : 'text-success' }}"></i>
                             <h6 class="mt-1 mb-2 text-uppercase fw-semibold {{ $isActive ? 'text-white' : 'text-success' }}">DELIVERED</h6>
                             <h3 class="mb-0 {{ $isActive ? 'text-white' : 'text-dark' }}">{{ $deliveredSalesCount }}</h3>
                         </div>
                     </div>
                 </a>
             </div>
             <div class="col-md mb-3">
                 <a href="{{ route('sales.index', ['delivery_status' => 'cancelled']) }}" class="text-decoration-none">
                     @php $isActive = request('delivery_status') == 'cancelled'; @endphp
                     <div class="card text-center h-100 {{ $isActive ? 'bg-danger text-white' : 'border-danger border shadow-none' }}">
                         <div class="card-body">
                             <i class="ri-close-circle-line fs-24 {{ $isActive ? '' : 'text-danger' }}"></i>
                             <h6 class="mt-1 mb-2 text-uppercase fw-semibold {{ $isActive ? 'text-white' : 'text-danger' }}">CANCELLED</h6>
                             <h3 class="mb-0 {{ $isActive ? 'text-white' : 'text-dark' }}">{{ $cancelledSalesCount }}</h3>
                         </div>
                     </div>
                 </a>
             </div>
         </div>

         <div class="row">
             <div class="col-12">
                 <div class="card">
                     <div class="card-body">
                         
                         <div class="card bg-light mb-3 shadow-none border">
                             <div class="card-body py-2 px-3">
                                 <form action="{{ route('sales.index') }}" method="GET">
                                     <div class="row gy-2 gx-2 align-items-end">
                                         <div class="col-md-2">
                                             <label for="start_date" class="form-label mb-1">Start Date</label>
                                             <input type="text" class="form-control flatpickr-date" id="start_date" name="start_date" value="{{ request('start_date') }}" placeholder="YYYY-MM-DD">
                                         </div>
                                         <div class="col-md-2">
                                             <label for="end_date" class="form-label mb-1">End Date</label>
                                             <input type="text" class="form-control flatpickr-date" id="end_date" name="end_date" value="{{ request('end_date') }}" placeholder="YYYY-MM-DD">
                                         </div>
                                         <div class="col-md-2">
                                             <label for="customer_id" class="form-label mb-1">Customer</label>
                                             <select class="form-control select2" id="customer_id" name="customer_id" data-toggle="select2">
                                                 <option value="">All Customers</option>
                                                 @foreach($customers as $cust)
                                                     <option value="{{ $cust->id }}" {{ request('customer_id') == $cust->id ? 'selected' : '' }}>
                                                         {{ $cust->name }} ({{ $cust->phone }})
                                                     </option>
                                                 @endforeach
                                             </select>
                                         </div>
                                         <div class="col-md-2">
                                             <label for="invoice_no" class="form-label mb-1">Invoice No</label>
                                             <input type="text" class="form-control" id="invoice_no" name="invoice_no" value="{{ request('invoice_no') }}" placeholder="e.g. INV-123">
                                         </div>
                                         <div class="col-md-2">
                                             <label for="status" class="form-label mb-1">Status</label>
                                             <select class="form-control select2" name="status">
                                                 <option value="">All Statuses</option>
                                                 <option value="Paid" {{ request('status') == 'Paid' ? 'selected' : '' }}>Paid</option>
                                                 <option value="Partial" {{ request('status') == 'Partial' ? 'selected' : '' }}>Partial</option>
                                                 <option value="Unpaid" {{ request('status') == 'Unpaid' ? 'selected' : '' }}>Unpaid</option>
                                             </select>
                                         </div>
                                         <div class="col-md-2">
                                             <label for="source" class="form-label mb-1">Source</label>
                                             <select class="form-control select2" name="source">
                                                 <option value="">All Sources</option>
                                                 <option value="admin" {{ request('source') == 'admin' ? 'selected' : '' }}>Admin</option>
                                                 <option value="customer" {{ request('source') == 'customer' ? 'selected' : '' }}>Customer</option>
                                             </select>
                                         </div>
                                         <div class="col-md-12 text-end mt-2">
                                             <button type="submit" class="btn btn-primary"><i class="ri-search-line me-1"></i> Filter</button>
                                             <a href="{{ route('sales.index') }}" class="btn btn-danger ms-1"><i class="ri-refresh-line me-1"></i> Reset</a>
                                         </div>
                                     </div>
                                 </form>
                             </div>
                         </div>

                         @if (session('success'))
                             <div class="alert alert-success">{{ session('success') }}</div>
                         @endif

                         <div>
                             <table class="table table-centered table-striped dt-responsive nowrap w-100" id="sales-datatable">
                                 <thead>
                                     <tr>
                                         <th>Invoice No</th>
                                         <th>Date</th>
                                         <th>Customer</th>
                                         <th>Total Amount</th>
                                         <th>Total Weight</th>
                                         <th>Paid</th>
                                         <th>Due</th>
                                         <th>Status</th>
                                         <th>Order Status</th>
                                         <th style="width: 120px;">Action</th>
                                     </tr>
                                 </thead>
                                 <tbody>
                                     @foreach ($sales as $sale)
                                         <tr>
                                             <td><b>{{ $sale->invoice_no }}</b></td>
                                             <td>{{ $sale->date->format('Y-m-d') }}</td>
                                             <td>
                                                 {{ $sale->customer->name ?? 'N/A' }}<br>
                                                 <span class="badge bg-{{ $sale->source == 'customer' ? 'info' : 'secondary' }} mt-1">{{ ucfirst($sale->source ?? 'Admin') }} Order</span>
                                             </td>
                                             <td>${{ number_format($sale->total, 0) }}</td>
                                             <td>{{ number_format($sale->total_weight, 3) }} kg</td>
                                             <td>${{ number_format($sale->paid_amount, 0) }}</td>
                                             <td class="text-danger">${{ number_format($sale->due_amount, 0) }}</td>
                                             <td>
                                                 @if($sale->payment_status == 'paid')
                                                     <span class="badge bg-success">Paid</span>
                                                 @elseif($sale->payment_status == 'partial')
                                                     <span class="badge bg-warning">Partial</span>
                                                 @else
                                                     <span class="badge bg-danger">Due</span>
                                                 @endif
                                             </td>
                                             <td>
                                                 @if($sale->delivery_status == 'pending')
                                                     <span class="badge bg-warning">Pending</span>
                                                 @elseif($sale->delivery_status == 'accepted')
                                                     <span class="badge bg-secondary">Accepted</span>
                                                 @elseif($sale->delivery_status == 'processing')
                                                     <span class="badge bg-info">Processing</span>
                                                 @elseif($sale->delivery_status == 'dispatched')
                                                     <span class="badge bg-primary">Dispatched</span>
                                                 @elseif($sale->delivery_status == 'delivered')
                                                     <span class="badge bg-success">Delivered</span>
                                                 @elseif($sale->delivery_status == 'cancelled')
                                                     <span class="badge bg-danger">Cancelled</span>
                                                 @else
                                                     <span class="badge bg-secondary">N/A</span>
                                                 @endif
                                             </td>
                                             <td>
                                                 <div class="dropdown">
                                                     <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                         <i class="ri-settings-3-line"></i> Actions
                                                     </button>
                                                     <ul class="dropdown-menu dropdown-menu-end">
                                                         <li><a class="dropdown-item text-info" href="{{ route('sales.show', $sale->id) }}"><i class="ri-eye-fill me-2"></i> View</a></li>
                                                         <li><a class="dropdown-item text-success" href="javascript:void(0);" onclick="viewPayments({{ $sale->id }}, '{{ $sale->invoice_no }}')"><i class="ri-money-dollar-box-line me-2"></i> Manage Payments</a></li>
                                                         <li><a class="dropdown-item text-primary" href="{{ route('sales.edit', $sale->id) }}"><i class="ri-edit-box-line me-2"></i> Edit</a></li>
                                                         @if($sale->source == 'customer' && ($sale->delivery_status == 'pending' || empty($sale->delivery_status)))
                                                         <li>
                                                             <form action="{{ route('sales.updateDetails', $sale->id) }}" method="POST" class="d-inline">
                                                                 @csrf
                                                                 <input type="hidden" name="delivery_status" value="accepted">
                                                                 <button type="submit" class="dropdown-item text-success"><i class="ri-check-double-line me-2"></i> Mark as Accepted</button>
                                                             </form>
                                                         </li>
                                                         @endif
                                                         <li>
                                                             <form id="delete-sale-{{ $sale->id }}" action="{{ route('sales.destroy', $sale->id) }}" method="POST" class="d-inline">
                                                                 @csrf
                                                                 @method('DELETE')
                                                                 <button type="button" class="dropdown-item text-danger" onclick="confirmDeleteSale({{ $sale->id }})"><i class="ri-delete-bin-line me-2"></i> Delete</button>
                                                             </form>
                                                         </li>
                                                     </ul>
                                                 </div>
                                             </td>
                                         </tr>
                                     @endforeach
                                 </tbody>
                             </table>
                         </div>
                         
                         <div class="mt-3">
                             {{ $sales->links('pagination::bootstrap-5') }}
                         </div>
                     </div>
                 </div>
             </div>
         </div>
    </div>

    <!-- Payments Modal -->
    <div class="modal fade" id="paymentsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Payment History - <span id="paymentInvoiceNo"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-centered">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Reference</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="paymentsTableBody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    @vite(['resources/js/pages/demo.form-advanced.js'])
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    function confirmDeleteSale(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "Are you sure you want to delete this Sale? All inventory and accounting impacts will be reversed.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('delete-sale-' + id).submit();
            }
        });
    }
    function viewPayments(saleId, invoiceNo) {
        document.getElementById('paymentInvoiceNo').innerText = invoiceNo;
        const tbody = document.getElementById('paymentsTableBody');
        tbody.innerHTML = '<tr><td colspan="5" class="text-center"><div class="spinner-border spinner-border-sm text-primary" role="status"></div> Loading...</td></tr>';
        
        var paymentsModal = new bootstrap.Modal(document.getElementById('paymentsModal'));
        paymentsModal.show();

        fetch(`/sales/${saleId}/payments`)
            .then(res => res.json())
            .then(data => {
                tbody.innerHTML = '';
                if (data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center">No payments found.</td></tr>';
                    return;
                }
                let csrf = '{{ csrf_token() }}';
                data.forEach(payment => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${payment.date ? payment.date.substring(0,10) : '-'}</td>
                        <td>
                            <form action="/sale-payments/${payment.id}" method="POST" class="d-flex align-items-center">
                                <input type="hidden" name="_token" value="${csrf}">
                                <input type="hidden" name="_method" value="PUT">
                                <input type="number" step="1" name="amount" value="${payment.amount}" class="form-control form-control-sm" style="width: 120px;" required>
                                <button type="submit" class="btn btn-sm btn-primary ms-1" title="Update"><i class="ri-save-line"></i></button>
                            </form>
                        </td>
                        <td>${payment.method || '-'}</td>
                        <td>${payment.reference || '-'}</td>
                        <td>
                            <form action="/sale-payments/${payment.id}" method="POST" class="d-inline">
                                <input type="hidden" name="_token" value="${csrf}">
                                <input type="hidden" name="_method" value="DELETE">
                                <button type="submit" class="btn btn-sm btn-danger p-1" onclick="return confirm('Are you sure you want to delete this payment? This will increase the invoice due amount and reverse the accounting entry.')" title="Delete"> <i class="ri-delete-bin-line"></i></button>
                            </form>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            });
    }
    </script>
@endsection
