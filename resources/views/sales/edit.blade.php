@extends('layouts.vertical', ['page_title' => 'Edit Sale', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('css')
@endsection

@section('content')
    <div class="container-fluid">
         <div class="row">
            <div class="col-12">
                <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                    <h4 class="page-title">Edit Sale: {{ $sale->invoice_no }}</h4>
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('sales.index') }}">Sales</a></li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </div>
            </div>
         </div>

         <form action="{{ route('sales.update', $sale->id) }}" method="POST">
             @csrf
             @method('PUT')
             <div class="row">
                 <div class="col-lg-8">
                     <div class="card">
                         <div class="card-body">
                             <div class="row mb-3">
                                 <div class="col-md-4">
                                     <label class="form-label">Date <span class="text-danger">*</span></label>
                                     <input type="text" name="date" class="form-control flatpickr-date" value="{{ old('date', $sale->date->format('Y-m-d')) }}" required>
                                 </div>
                                 <div class="col-md-4">
                                     <label class="form-label">Warehouse (Stock Source) <span class="text-danger">*</span></label>
                                     <select name="warehouse_id" id="warehouse_id" class="form-select" required>
                                         @foreach($warehouses as $wh)
                                             <option value="{{ $wh->id }}" {{ $sale->warehouse_id == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                                         @endforeach
                                     </select>
                                 </div>
                                 <div class="col-md-4">
                                     <label class="form-label">Customer <span class="text-danger">*</span></label>
                                     <div class="d-flex">
                                         <select name="customer_id" id="customer_id" class="form-control select2" data-toggle="select2" required style="width: 100%;">
                                             <option value="">Walk-in / Select Customer</option>
                                             @foreach($customers as $customer)
                                                 <option value="{{ $customer->id }}" {{ $sale->customer_id == $customer->id ? 'selected' : '' }}>{{ $customer->name }} (Credit: ${{ $customer->credit_limit }})</option>
                                             @endforeach
                                         </select>
                                         <button type="button" class="btn btn-primary ms-1" data-bs-toggle="modal" data-bs-target="#addCustomerModal"><i class="ri-add-line"></i></button>
                                     </div>
                                 </div>
                             </div>

                             <h4 class="header-title mb-3"><i class="ri-shopping-cart-fill text-primary"></i> Current Cart</h4>

                             @if ($errors->any())
                                 <div class="alert alert-danger">
                                     <ul class="mb-0">
                                         @foreach ($errors->all() as $error)
                                             <li>{{ $error }}</li>
                                         @endforeach
                                     </ul>
                                 </div>
                             @endif

                             <div class="table-responsive">
                                 <table class="table table-bordered mb-0">
                                     <thead class="table-light">
                                         <tr>
                                             <th>Product Variant</th>
                                             <th width="15%">Qty</th>
                                             <th width="20%">Unit Price ($)</th>
                                             <th width="20%">Subtotal ($)</th>
                                         </tr>
                                     </thead>
                                     <tbody id="cart-items">
                                         @foreach($sale->items as $index => $item)
                                         <tr>
                                             <td>
                                                 <select name="items[{{ $index }}][product_variant_id]" class="form-select variant-select" required>
                                                     <option value="{{ $item->product_variant_id }}" data-stock="999">{{ $item->productVariant->product->name }} - {{ $item->productVariant->name }}</option>
                                                 </select>
                                             </td>
                                             <td>
                                                 <input type="number" step="0.001" name="items[{{ $index }}][qty]" class="form-control qty-input" placeholder="Qty" value="{{ $item->qty }}" required>
                                             </td>
                                             <td>
                                                 <input type="number" step="1" name="items[{{ $index }}][unit_price]" class="form-control price-input" placeholder="Price" value="{{ $item->unit_price }}" required>
                                             </td>
                                             <td>
                                                 <input type="number" step="1" class="form-control row-subtotal" placeholder="0.00" value="{{ number_format($item->qty * $item->unit_price, 2, '.', '') }}" readonly>
                                             </td>
                                         </tr>
                                         @endforeach
                                     </tbody>
                                 </table>
                             </div>
                             
                             <div class="mt-3">
                                 <button type="button" class="btn btn-sm btn-outline-primary"><i class="ri-add-circle-fill"></i> Add Item (UI handled dynamically in a full app)</button>
                             </div>
                         </div>
                     </div>
                 </div>

                 <div class="col-lg-4">
                     <div class="card">
                         <div class="card-body">
                             <h4 class="header-title mb-3"><i class="ri-file-list-3-fill text-success"></i> Payment Details</h4>
                             
                             <div class="mb-3 form-check form-switch">
                                 <input type="checkbox" name="is_promotional" class="form-check-input" id="isPromotional" value="1" {{ $sale->is_promotional ? 'checked' : '' }}>
                                 <label class="form-check-label" for="isPromotional">Is Promotional Sale?</label>
                             </div>

                             <hr>
                             
                             <div class="mb-3">
                                 <label class="form-label">Delivery Charge ($)</label>
                                 <input type="number" step="1" name="delivery_charge" class="form-control" value="{{ $sale->delivery_charge }}">
                             </div>

                             <div class="mb-3">
                                 <label class="form-label">Discount ($)</label>
                                 <input type="number" step="1" name="discount" class="form-control" value="{{ $sale->discount }}">
                             </div>
                             
                             <div class="mb-3">
                                 <label class="form-label">Payment Method</label>
                                 <select name="payment_method" class="form-select">
                                     <option value="">Default (Cash)</option>
                                     @if(isset($paymentMethods))
                                         @foreach($paymentMethods as $method)
                                             <option value="{{ $method->id }}" {{ $sale->payment_method == $method->id ? 'selected' : '' }}>{{ $method->name }}</option>
                                         @endforeach
                                     @endif
                                 </select>
                             </div>

                             <div class="mb-3">
                                 <label class="form-label">Delivery Method</label>
                                 <select name="delivery_method" class="form-select">
                                     <option value="" {{ $sale->delivery_method == '' ? 'selected' : '' }}>None / Walk-in</option>
                                     <option value="pickup" {{ $sale->delivery_method == 'pickup' ? 'selected' : '' }}>Pickup</option>
                                     <option value="own_delivery" {{ $sale->delivery_method == 'own_delivery' ? 'selected' : '' }}>Own Delivery</option>
                                     <option value="steadfast" {{ $sale->delivery_method == 'steadfast' ? 'selected' : '' }}>Steadfast Courier</option>
                                 </select>
                             </div>

                             <div class="mb-3 form-check form-switch">
                                 <input type="checkbox" class="form-check-input" id="fullPaymentToggle">
                                 <label class="form-check-label text-primary fw-bold" for="fullPaymentToggle">Pay Full Amount</label>
                             </div>

                             <div class="mb-3">
                                 <label class="form-label text-success"><strong>Amount Paid Now ($)</strong></label>
                                 <input type="number" step="1" name="paid_amount" id="paidAmount" class="form-control" value="{{ $sale->paid_amount }}">
                                 <small class="text-muted">Remaining will be added to Customer's Due.</small>
                             </div>

                             <div class="mt-4 d-grid">
                                 <button type="submit" class="btn btn-warning btn-lg"><i class="ri-edit-box-line"></i> Update Sale</button>
                             </div>
                         </div>
                     </div>
                 </div>
             </div>
         </form>

         <!-- Add Customer Modal -->
         <div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerModalLabel" aria-hidden="true">
             <div class="modal-dialog">
                 <div class="modal-content">
                     <div class="modal-header">
                         <h5 class="modal-title" id="addCustomerModalLabel">Add New Customer</h5>
                         <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                     </div>
                     <div class="modal-body">
                         <form id="addCustomerForm">
                             <div class="mb-3">
                                 <label class="form-label">Name <span class="text-danger">*</span></label>
                                 <input type="text" class="form-control" id="new_customer_name" required>
                             </div>
                             <div class="mb-3">
                                 <label class="form-label">Phone <span class="text-danger">*</span></label>
                                 <input type="text" class="form-control" id="new_customer_phone" required>
                             </div>
                             <div class="text-end">
                                 <button type="submit" class="btn btn-primary">Save Customer</button>
                             </div>
                         </form>
                     </div>
                 </div>
             </div>
         </div>
    </div>
@endsection

@section('script')
    @vite(['resources/js/pages/demo.form-advanced.js'])
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const qtyInputs = document.querySelectorAll('.qty-input');
        const priceInputs = document.querySelectorAll('.price-input');
        const rowSubtotals = document.querySelectorAll('.row-subtotal');
        
        const deliveryChargeInput = document.querySelector('input[name="delivery_charge"]');
        const discountInput = document.querySelector('input[name="discount"]');
        const paidAmountInput = document.getElementById('paidAmount');
        const fullPaymentToggle = document.getElementById('fullPaymentToggle');

        function calculateTotal() {
            let subtotal = 0;
            for (let i = 0; i < qtyInputs.length; i++) {
                const qty = parseFloat(qtyInputs[i].value) || 0;
                const price = parseFloat(priceInputs[i].value) || 0;
                const rowTotal = qty * price;
                rowSubtotals[i].value = rowTotal.toFixed(0);
                subtotal += rowTotal;
            }

            const delivery = parseFloat(deliveryChargeInput.value) || 0;
            const discount = parseFloat(discountInput.value) || 0;
            
            const grandTotal = Math.max(0, subtotal + delivery - discount);
            return grandTotal;
        }

        function updateFullPayment() {
            if (fullPaymentToggle.checked) {
                paidAmountInput.value = calculateTotal().toFixed(0);
            }
        }

        [...qtyInputs, ...priceInputs, deliveryChargeInput, discountInput].forEach(input => {
            if(input) {
                input.addEventListener('input', () => {
                    calculateTotal();
                    updateFullPayment();
                });
            }
        });

        if(fullPaymentToggle) {
            fullPaymentToggle.addEventListener('change', updateFullPayment);
        }

        // Warehouse -> Variants Logic
        const warehouseSelect = document.getElementById('warehouse_id');
        
        function loadVariants() {
            const warehouseId = warehouseSelect.value;
            if(!warehouseId) return;
            
            fetch(`{{ route('pos.variants') }}?warehouse_id=${warehouseId}`)
                .then(res => res.json())
                .then(data => {
                    const variantSelects = document.querySelectorAll('.variant-select');
                    variantSelects.forEach(select => {
                        // Store previously selected value
                        const selectedVal = select.value;
                        
                        // Clear options
                        select.innerHTML = '<option value="">Select Variant</option>';
                        
                        // Add new options
                        data.forEach(item => {
                            const option = document.createElement('option');
                            option.value = item.id;
                            option.text = item.text;
                            // Add stock data attribute so we can use it later
                            option.dataset.stock = item.stock;
                            select.appendChild(option);
                        });
                        
                        // Restore selection if it still exists in the new list
                        if (selectedVal && data.some(d => d.id == selectedVal)) {
                            select.value = selectedVal;
                        }
                    });
                })
                .catch(err => console.error('Error fetching variants:', err));
        }

        if(warehouseSelect) {
            warehouseSelect.addEventListener('change', loadVariants);
            // Initial load
            loadVariants();
        }

        // Add Customer AJAX
        document.getElementById('addCustomerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const name = document.getElementById('new_customer_name').value;
            const phone = document.getElementById('new_customer_phone').value;
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Saving...';
            
            fetch('{{ route("customers.ajaxStore") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ name: name, phone: phone })
            })
            .then(response => response.json())
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Save Customer';
                
                if(data.success) {
                    const customer = data.customer;
                    // Add to select2
                    const newOption = new Option(customer.name + ' (Credit: $0)', customer.id, true, true);
                    $('#customer_id').append(newOption).trigger('change');
                    
                    // Close modal
                    bootstrap.Modal.getInstance(document.getElementById('addCustomerModal')).hide();
                    document.getElementById('addCustomerForm').reset();
                } else {
                    alert('Error adding customer: ' + (data.message || 'Validation failed.'));
                }
            })
            .catch(error => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Save Customer';
                console.error('Error:', error);
                alert('An error occurred.');
            });
        });
    });
</script>
@endsection


