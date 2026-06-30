@extends('layouts.vertical', ['page_title' => 'POS Terminal', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('css')
@endsection

@section('content')
    <div class="container-fluid">
         <div class="row">
            <div class="col-12">
                <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                    <h4 class="page-title">Point of Sale (POS)</h4>
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                        <li class="breadcrumb-item active">POS</li>
                    </ol>
                </div>
            </div>
         </div>

         <form action="{{ route('sales.store') }}" method="POST">
             @csrf
             <div class="row">
                 <div class="col-lg-8">
                     <div class="card">
                         <div class="card-body">
                             <div class="row mb-3">
                                 <div class="col-md-4">
                                     <label class="form-label">Date <span class="text-danger">*</span></label>
                                     <input type="text" name="date" class="form-control flatpickr-date" value="{{ old('date', date('Y-m-d')) }}" required>
                                 </div>
                                 <div class="col-md-4">
                                     <label class="form-label">Warehouse (Stock Source) <span class="text-danger">*</span></label>
                                     <select name="warehouse_id" id="warehouse_id" class="form-select" required>
                                         @foreach($warehouses as $index => $wh)
                                             <option value="{{ $wh->id }}" {{ $index === 0 ? 'selected' : '' }}>{{ $wh->name }}</option>
                                         @endforeach
                                     </select>
                                 </div>
                                 <div class="col-md-4">
                                     <label class="form-label">Customer <span class="text-danger">*</span></label>
                                     <div class="d-flex">
                                         <select name="customer_id" id="customer_id" class="form-control select2" data-toggle="select2" required style="width: 100%;">
                                             <option value="">Walk-in / Select Customer</option>
                                             @foreach($customers as $customer)
                                                 <option value="{{ $customer->id }}">{{ $customer->name }} (Wallet: ${{ $customer->wallet_balance }}, Due: ${{ $customer->total_due }})</option>
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
                                         <tr>
                                             <td>
                                                 <select name="items[0][product_variant_id]" class="form-control select2 variant-select" data-toggle="select2" required style="width: 100%;">
                                                     <option value="">Select Variant</option>
                                                 </select>
                                             </td>
                                             <td>
                                                 <input type="number" step="0.001" name="items[0][qty]" class="form-control qty-input" placeholder="Qty" required>
                                             </td>
                                             <td>
                                                 <input type="number" step="1" name="items[0][unit_price]" class="form-control price-input" placeholder="Price" required>
                                             </td>
                                             <td>
                                                 <input type="number" step="1" class="form-control row-subtotal" placeholder="0.00" readonly>
                                             </td>
                                         </tr>
                                     </tbody>
                                 </table>
                             </div>
                             
                             <div class="mt-3">
                                 <button type="button" id="add-item-btn" class="btn btn-sm btn-outline-primary"><i class="ri-add-circle-fill"></i> Add Item</button>
                             </div>
                         </div>
                     </div>
                 </div>

                 <div class="col-lg-4">
                     <div class="card">
                         <div class="card-body">
                             <h4 class="header-title mb-3"><i class="ri-file-list-3-fill text-success"></i> Payment Details</h4>
                             
                             <div class="mb-3 form-check form-switch">
                                 <input type="checkbox" name="is_promotional" class="form-check-input" id="isPromotional" value="1">
                                 <label class="form-check-label" for="isPromotional">Is Promotional Sale?</label>
                             </div>

                             <hr>
                             
                             <div class="mb-3">
                                 <label class="form-label">Delivery Charge ($)</label>
                                 <input type="number" step="1" name="delivery_charge" class="form-control" value="0">
                             </div>

                             <div class="mb-3">
                                 <label class="form-label">Discount ($)</label>
                                 <input type="number" step="1" name="discount" class="form-control" value="0">
                             </div>
                             
                             <div class="mb-3">
                                 <label class="form-label">Payment Method</label>
                                 <select name="payment_method" class="form-select">
                                     <option value="">Default (Cash)</option>
                                     @if(isset($paymentMethods))
                                         @foreach($paymentMethods as $method)
                                             <option value="{{ $method->id }}">{{ $method->name }}</option>
                                         @endforeach
                                     @endif
                                 </select>
                             </div>

                             <div class="mb-3">
                                 <label class="form-label">Delivery Method</label>
                                 <select name="delivery_method" class="form-select">
                                     <option value="">None / Walk-in</option>
                                     <option value="pickup">Pickup</option>
                                     <option value="own_delivery">Own Delivery</option>
                                     <option value="steadfast">Steadfast Courier</option>
                                 </select>
                             </div>

                             <div class="mb-3">
                                 <label class="form-label">Shipping Address (Optional)</label>
                                 <textarea name="shipping_address" class="form-control" rows="2" placeholder="Leave blank to use customer's default address"></textarea>
                             </div>

                             <hr>
                             <div class="mb-3 text-end">
                                 <h3 class="text-danger m-0">Grand Total: $<span id="grandTotalDisplay">0.00</span></h3>
                             </div>
                             <hr>

                             <div class="mb-3 form-check form-switch">
                                 <input type="checkbox" class="form-check-input" id="fullPaymentToggle">
                                 <label class="form-check-label text-primary fw-bold" for="fullPaymentToggle">Pay Full Amount</label>
                             </div>

                             <div class="mb-3">
                                 <label class="form-label text-success"><strong>Amount Paid Now ($)</strong></label>
                                 <input type="number" step="1" name="paid_amount" id="paidAmount" class="form-control" value="0">
                                 <small class="text-muted d-block mt-1">If the customer has a <strong>Wallet Balance</strong>, it will be automatically applied to any remaining due. Overpayments will be added to the Wallet.</small>
                             </div>

                             <div class="mt-4 d-grid">
                                 <button type="submit" class="btn btn-primary btn-lg"><i class="ri-checkbox-circle-fill"></i> Complete Sale</button>
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
                             <div class="mb-3">
                                 <label class="form-label">Email (Optional)</label>
                                 <input type="email" class="form-control" id="new_customer_email">
                             </div>
                             <div class="mb-3">
                                 <label class="form-label">Password (Optional)</label>
                                 <input type="password" class="form-control" id="new_customer_password">
                             </div>
                             <div class="mb-3">
                                 <label class="form-label">Address (Optional)</label>
                                 <textarea class="form-control" id="new_customer_address" rows="2"></textarea>
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

@section('script-bottom')
    @vite(['resources/js/pages/demo.form-advanced.js'])
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const deliveryChargeInput = document.querySelector('input[name="delivery_charge"]');
        const discountInput = document.querySelector('input[name="discount"]');
        const paidAmountInput = document.getElementById('paidAmount');
        const fullPaymentToggle = document.getElementById('fullPaymentToggle');

        function calculateTotal() {
            let subtotal = 0;
            const liveQtyInputs = document.querySelectorAll('.qty-input');
            const livePriceInputs = document.querySelectorAll('.price-input');
            const liveRowSubtotals = document.querySelectorAll('.row-subtotal');

            for (let i = 0; i < liveQtyInputs.length; i++) {
                const qty = parseFloat(liveQtyInputs[i].value) || 0;
                const price = parseFloat(livePriceInputs[i].value) || 0;
                const rowTotal = qty * price;
                if (liveRowSubtotals[i]) {
                    liveRowSubtotals[i].value = rowTotal.toFixed(0);
                }
                subtotal += rowTotal;
            }

            const delivery = parseFloat(deliveryChargeInput.value) || 0;
            const discount = parseFloat(discountInput.value) || 0;
            
            const grandTotal = Math.max(0, subtotal + delivery - discount);
            
            const grandTotalDisplay = document.getElementById('grandTotalDisplay');
            if (grandTotalDisplay) {
                grandTotalDisplay.innerText = grandTotal.toFixed(0);
            }
            
            return grandTotal;
        }

        function updateFullPayment() {
            if (fullPaymentToggle.checked) {
                paidAmountInput.value = calculateTotal().toFixed(0);
            }
        }

        document.addEventListener('input', function(e) {
            if (e.target.classList.contains('qty-input') || e.target.classList.contains('price-input')) {
                calculateTotal();
                updateFullPayment();
            }
        });

        [deliveryChargeInput, discountInput].forEach(input => {
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
                        const selectedVal = $(select).val();
                        
                        // Clear options
                        $(select).empty().append('<option value="">Select Variant</option>');
                        
                        // Add new options
                        data.forEach(item => {
                            const option = new Option(item.text, item.id, false, false);
                            option.dataset.stock = item.stock;
                            option.dataset.price = item.price;
                            option.dataset.unit_qty = item.unit_qty;
                            $(select).append(option);
                        });
                        
                        // Restore selection if it still exists in the new list
                        if (selectedVal && data.some(d => d.id == selectedVal)) {
                            $(select).val(selectedVal);
                        }

                        // Refresh select2 UI
                        $(select).trigger('change.select2');
                    });
                })
                .catch(err => console.error('Error fetching variants:', err));
        }

        if(warehouseSelect) {
            warehouseSelect.addEventListener('change', loadVariants);
            // Initial load
            loadVariants();
        }

        // Update price when variant changes
        $('#cart-items').on('change', '.variant-select', function(e) {
            const selectedOption = this.options[this.selectedIndex];
            const tr = $(this).closest('tr');
            const priceInput = tr.find('.price-input')[0];
            const qtyInput = tr.find('.qty-input')[0];
            
            if (selectedOption && selectedOption.dataset.price && priceInput) {
                priceInput.value = parseFloat(selectedOption.dataset.price).toFixed(0);
                
                // Automatically set qty if it's empty
                if (!qtyInput.value || parseFloat(qtyInput.value) === 0) {
                    qtyInput.value = 1;
                }
                
                // Set the step amount so the up/down arrows increment properly by 1 packet
                qtyInput.step = 1;
                
                // trigger calculateTotal since input changed manually
                calculateTotal();
                updateFullPayment();
            }
        });

        // Add Item Row
        let itemIndex = 0;
        document.getElementById('add-item-btn').addEventListener('click', function() {
            itemIndex++;
            const warehouseId = document.getElementById('warehouse_id').value;
            
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <select name="items[${itemIndex}][product_variant_id]" class="form-control select2 variant-select" required style="width: 100%;">
                        <option value="">Select Variant</option>
                    </select>
                </td>
                <td>
                    <input type="number" step="0.001" name="items[${itemIndex}][qty]" class="form-control qty-input" placeholder="Qty" required>
                </td>
                <td>
                    <input type="number" step="1" name="items[${itemIndex}][unit_price]" class="form-control price-input" placeholder="Price" required>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <input type="number" step="1" class="form-control row-subtotal me-2" placeholder="0.00" readonly>
                        <button type="button" class="btn btn-sm btn-danger remove-item-btn"><i class="ri-delete-bin-line"></i></button>
                    </div>
                </td>
            `;
            
            document.getElementById('cart-items').appendChild(tr);
            
            // Handle remove
            tr.querySelector('.remove-item-btn').addEventListener('click', function() {
                tr.remove();
                calculateTotal();
                updateFullPayment();
            });
            
            // Reload variants into this new select
            const newSelect = $(tr).find('.variant-select');
            
            if (warehouseId) {
                fetch(`{{ route('pos.variants') }}?warehouse_id=${warehouseId}`)
                    .then(res => res.json())
                    .then(data => {
                        data.forEach(item => {
                            const option = new Option(item.text, item.id, false, false);
                            option.dataset.stock = item.stock;
                            option.dataset.price = item.price;
                            option.dataset.unit_qty = item.unit_qty;
                            newSelect.append(option);
                        });
                        newSelect.select2({ width: '100%' });
                    });
            } else {
                newSelect.select2({ width: '100%' });
            }
        });

        // Add Customer AJAX
        document.getElementById('addCustomerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const name = document.getElementById('new_customer_name').value;
            const phone = document.getElementById('new_customer_phone').value;
            const email = document.getElementById('new_customer_email').value;
            const password = document.getElementById('new_customer_password').value;
            const address = document.getElementById('new_customer_address').value;
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
                body: JSON.stringify({ name: name, phone: phone, email: email, password: password, address: address })
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


