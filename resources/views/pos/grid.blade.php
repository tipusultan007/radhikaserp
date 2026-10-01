@extends('layouts.vertical', ['page_title' => 'POS Grid Terminal', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('css')
<style>
    .pos-product-card {
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        border: 1px solid #eef2f7;
    }
    .pos-product-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08) !important;
        border-color: #727cf5;
    }
    .pos-product-img {
        height: 120px;
        object-fit: cover;
        width: 100%;
        background-color: #f8f9fa;
    }
    .pos-product-placeholder {
        height: 120px;
        background: linear-gradient(135deg, #eef2f7 0%, #e0e6ed 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        color: #98a6ad;
    }
    .cart-table-wrapper {
        max-height: 380px;
        overflow-y: auto;
    }
    .variant-row:hover {
        background-color: #f8f9fa;
    }
</style>
@endsection

@section('content')
    <div class="container-fluid">
         <div class="row">
            <div class="col-12">
                <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                    <h4 class="page-title">Point of Sale (POS) Grid</h4>
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('pos.index') }}">POS</a></li>
                        <li class="breadcrumb-item active">Grid View</li>
                    </ol>
                </div>
            </div>
         </div>

         <form action="{{ route('sales.store') }}" method="POST" id="posForm">
             @csrf
             <div class="row">
                 <!-- LEFT COLUMN: Top Settings + Products Grid -->
                 <div class="col-lg-7 col-xl-7">
                     <div class="card mb-3">
                         <div class="card-body p-3">
                             <div class="row g-2 align-items-center">
                                 <div class="col-md-3">
                                     <label class="form-label mb-1 fs-13">Date <span class="text-danger">*</span></label>
                                     <input type="text" name="date" class="form-control form-control-sm flatpickr-date" value="{{ old('date', date('Y-m-d')) }}" required>
                                 </div>
                                 <div class="col-md-4">
                                     <label class="form-label mb-1 fs-13">Warehouse <span class="text-danger">*</span></label>
                                     <select name="warehouse_id" id="warehouse_id" class="form-select form-select-sm" required>
                                         @foreach($warehouses as $index => $wh)
                                             <option value="{{ $wh->id }}" {{ $index === 0 ? 'selected' : '' }}>{{ $wh->name }}</option>
                                         @endforeach
                                     </select>
                                 </div>
                                 <div class="col-md-5">
                                     <label class="form-label mb-1 fs-13">Customer <span class="text-danger">*</span></label>
                                     <div class="d-flex">
                                         <select name="customer_id" id="customer_id" class="form-control form-control-sm select2" data-toggle="select2" required style="width: 100%;">
                                             <option value="">--Select Customer--</option>
                                             @foreach($customers as $customer)
                                                 <option value="{{ $customer->id }}" data-customer-type="{{ $customer->customer_type }}">{{ $customer->name }} (Wallet: {{ $customer->wallet_balance }}, Due: {{ $customer->total_due }}{{ $customer->credit_limit > 0 ? ', Limit: ' . number_format($customer->credit_limit, 0) : '' }})</option>
                                             @endforeach
                                         </select>
                                         <button type="button" class="btn btn-sm btn-primary ms-1" data-bs-toggle="modal" data-bs-target="#addCustomerModal"><i class="ri-add-line"></i></button>
                                     </div>
                                 </div>
                             </div>
                         </div>
                     </div>

                     <!-- Product Search & Grid Header -->
                     <div class="card">
                         <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-2">
                             <div class="input-group input-group-sm w-50">
                                 <span class="input-group-text bg-light"><i class="ri-search-line"></i></span>
                                 <input type="text" id="productSearchInput" class="form-control" placeholder="Search product name or SKU...">
                             </div>
                             <div>
                                 <a href="{{ route('pos.index') }}" class="btn btn-xs btn-outline-secondary">
                                     <i class="ri-list-check font-14 me-1"></i> Standard POS
                                 </a>
                             </div>
                         </div>
                         <div class="card-body p-3">
                             <div id="gridLoading" class="text-center py-5">
                                 <div class="spinner-border text-primary" role="status"></div>
                                 <p class="text-muted mt-2">Loading products...</p>
                             </div>

                             <div id="productGrid" class="row row-cols-2 row-cols-sm-3 row-cols-md-4 g-3" style="display: none;">
                                 <!-- Dynamic product cards will be inserted here -->
                             </div>

                             <div id="noProductsFound" class="text-center py-5 text-muted" style="display: none;">
                                 <i class="ri-inbox-line display-4 d-block"></i>
                                 <p class="mt-2 mb-0">No products found matching your search or warehouse filter.</p>
                             </div>
                         </div>
                     </div>
                 </div>

                 <!-- RIGHT COLUMN: Cart & Billing -->
                 <div class="col-lg-5 col-xl-5">
                     <div class="card">
                         <div class="card-body p-3">
                             <h5 class="header-title mb-2 d-flex justify-content-between align-items-center">
                                 <span><i class="ri-shopping-cart-fill text-primary me-1"></i> Current Cart</span>
                                 <span class="badge bg-soft-primary text-primary" id="cartItemCountBadge">0 Items</span>
                             </h5>

                             @if ($errors->any())
                                 <div class="alert alert-danger p-2 mb-2 fs-13">
                                     <ul class="mb-0 ps-3">
                                         @foreach ($errors->all() as $error)
                                             <li>{{ $error }}</li>
                                         @endforeach
                                     </ul>
                                 </div>
                             @endif

                             <!-- Cart Table -->
                             <div class="cart-table-wrapper border rounded mb-3">
                                 <table class="table table-sm table-centered table-nowrap mb-0">
                                     <thead class="table-light">
                                         <tr>
                                             <th>Product Variant</th>
                                             <th width="20%">Qty</th>
                                             <th width="22%">Price (৳)</th>
                                             <th width="22%">Subtotal</th>
                                             <th width="8%"></th>
                                         </tr>
                                     </thead>
                                     <tbody id="cart-items">
                                         <!-- Dynamic Cart Rows -->
                                     </tbody>
                                 </table>
                                 <div id="emptyCartMessage" class="text-center py-4 text-muted fs-13">
                                     <i class="ri-shopping-basket-2-line font-24 d-block mb-1"></i>
                                     Cart is empty. Click a product on the left to add items.
                                 </div>
                             </div>

                             <!-- Order Details -->
                             <div class="mb-3 form-check form-switch bg-light p-2 rounded ps-5 border">
                                 <input type="checkbox" name="is_promotional" class="form-check-input" id="isPromotional" value="1">
                                 <label class="form-check-label fw-medium" for="isPromotional">Is Promotional Sale?</label>
                             </div>

                             <!-- Shipping & Delivery -->
                             <h6 class="header-title mb-2"><i class="ri-truck-fill text-primary me-1"></i> Shipping & Delivery</h6>
                             <div class="row g-2 mb-2">
                                 <div class="col-md-6">
                                     <label class="form-label mb-1 fs-12">Delivery Method</label>
                                     <select name="delivery_method" class="form-select form-select-sm" id="deliveryMethodSelect">
                                         <option value="">None / Walk-in</option>
                                         <option value="pickup">Pickup</option>
                                         <option value="own_delivery">Own Delivery</option>
                                         <option value="steadfast">Steadfast Courier</option>
                                     </select>
                                 </div>
                                 <div class="col-md-6" id="deliveryTypeContainer" style="display: none;">
                                     <label class="form-label mb-1 fs-12">Delivery Type (Steadfast)</label>
                                     <select name="delivery_type" class="form-select form-select-sm" id="deliveryTypeSelect">
                                         <option value="1" selected>Point Delivery</option>
                                         <option value="0">Home Delivery</option>
                                     </select>
                                 </div>
                             </div>

                             <div class="mb-3">
                                 <label class="form-label mb-1 fs-12">Shipping Address (Optional)</label>
                                 <textarea name="shipping_address" class="form-control form-control-sm" rows="1" placeholder="Leave blank to use customer's default address"></textarea>
                             </div>

                             <div class="mb-3 form-check form-switch bg-light p-2 rounded ps-5 border">
                                 <input type="checkbox" name="delivered_now" class="form-check-input" id="deliveredNowGrid" value="1" checked>
                                 <label class="form-check-label fw-bold text-success fs-12" for="deliveredNowGrid">
                                     <i class="ri-checkbox-circle-line me-1"></i> Delivered Now (Deduct Stock)
                                 </label>
                                 <small class="d-block text-muted fs-11">Uncheck for Advance Invoice (Pending stock).</small>
                             </div>

                             <!-- Billing & Payment -->
                             <h6 class="header-title mb-2"><i class="ri-money-dollar-circle-fill text-success me-1"></i> Billing & Payment</h6>
                             <div class="row g-2 mb-2">
                                 <div class="col-6">
                                     <label class="form-label mb-1 fs-12">Delivery Charge (৳)</label>
                                     <input type="number" step="1" name="delivery_charge" class="form-control form-control-sm" value="0">
                                 </div>
                                 <div class="col-6">
                                     <label class="form-label mb-1 fs-12">Discount (৳)</label>
                                     <input type="number" step="1" name="discount" class="form-control form-control-sm" value="0">
                                 </div>
                             </div>

                             <div class="mb-3 text-end bg-light p-2 rounded border">
                                 <h4 class="text-danger m-0 fw-bold">Grand Total: <span id="grandTotalDisplay">0.00</span></h4>
                             </div>

                             <div class="mb-2">
                                 <label class="form-label mb-1 fs-12">Payment Method</label>
                                 <select name="payment_method" class="form-select form-select-sm">
                                     <option value="">Default (Cash)</option>
                                     @if(isset($paymentMethods))
                                         @foreach($paymentMethods as $method)
                                             <option value="{{ $method->id }}">{{ $method->name }}</option>
                                         @endforeach
                                     @endif
                                 </select>
                             </div>

                             <div class="mb-2 form-check form-switch">
                                 <input type="checkbox" class="form-check-input" id="fullPaymentToggle">
                                 <label class="form-check-label text-primary fw-bold fs-13" for="fullPaymentToggle">Pay Full Amount</label>
                             </div>

                             <div class="mb-3">
                                 <label class="form-label text-success mb-1 fs-12"><strong>Amount Paid Now (৳)</strong></label>
                                 <input type="number" step="1" name="paid_amount" id="paidAmount" class="form-control" value="0" style="font-size: 1.1rem; font-weight: bold;">
                                 <small class="text-muted d-block mt-1 fs-11">If customer has a <strong>Wallet Balance</strong>, it will automatically apply to remaining due.</small>
                             </div>

                             <div class="d-grid">
                                 <button type="submit" class="btn btn-primary btn-lg shadow-sm" id="completeSaleBtn"><i class="ri-checkbox-circle-fill me-1"></i> Complete Sale</button>
                             </div>
                         </div>
                     </div>
                 </div>
             </div>
         </form>

         <!-- Modal for Product Variants Selection -->
         <div class="modal fade" id="variantModal" tabindex="-1" aria-labelledby="variantModalLabel" aria-hidden="true">
             <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                 <div class="modal-content">
                     <div class="modal-header py-2">
                         <h5 class="modal-title" id="variantModalLabel">Select Variant</h5>
                         <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                     </div>
                     <div class="modal-body p-0">
                         <div class="p-3 bg-light border-bottom d-flex align-items-center">
                             <div id="modalProductImgContainer" class="me-3"></div>
                             <div>
                                 <h5 id="modalProductName" class="mb-0 fw-bold"></h5>
                                 <small id="modalProductSku" class="text-muted"></small>
                             </div>
                         </div>
                         <div class="table-responsive">
                             <table class="table table-hover table-centered mb-0">
                                 <thead class="table-light fs-12">
                                     <tr>
                                         <th>Variant</th>
                                         <th>Stock</th>
                                         <th>Price</th>
                                         <th class="text-end">Action</th>
                                     </tr>
                                 </thead>
                                 <tbody id="modalVariantsList">
                                     <!-- Variants rows rendered dynamically -->
                                 </tbody>
                             </table>
                         </div>
                     </div>
                 </div>
             </div>
         </div>

         <!-- Add Customer Modal -->
         <div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerModalLabel" aria-hidden="true">
             <div class="modal-dialog">
                 <div class="modal-content">
                     <div class="modal-header py-2">
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
                                 <label class="form-label">Customer Type</label>
                                 <select class="form-control" id="new_customer_type">
                                     <option value="customer">Customer</option>
                                     <option value="dealer">Dealer</option>
                                     <option value="special_dealer">Special Dealer</option>
                                 </select>
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
        let allProducts = [];
        let cart = []; // array of { variant_id, product_name, variant_name, price, stock, unit_qty, qty }
        
        const warehouseSelect = document.getElementById('warehouse_id');
        const customerSelect = document.getElementById('customer_id');
        const searchInput = document.getElementById('productSearchInput');
        
        const deliveryChargeInput = document.querySelector('input[name="delivery_charge"]');
        const discountInput = document.querySelector('input[name="discount"]');
        const paidAmountInput = document.getElementById('paidAmount');
        const fullPaymentToggle = document.getElementById('fullPaymentToggle');
        const isPromotionalCheckbox = document.getElementById('isPromotional');
        const paymentMethodSelect = document.querySelector('select[name="payment_method"]');
        const deliveryMethodSel = document.getElementById('deliveryMethodSelect');
        const deliveryTypeSel = document.getElementById('deliveryTypeSelect');

        let variantModal;
        if(document.getElementById('variantModal')) {
            variantModal = new bootstrap.Modal(document.getElementById('variantModal'));
        }

        // Fetch Products Data for selected Warehouse
        function loadProductsGrid() {
            const warehouseId = warehouseSelect.value;
            if(!warehouseId) return;

            document.getElementById('gridLoading').style.display = 'block';
            document.getElementById('productGrid').style.display = 'none';
            document.getElementById('noProductsFound').style.display = 'none';

            fetch(`{{ route('pos.grid-data') }}?warehouse_id=${warehouseId}`)
                .then(res => res.json())
                .then(data => {
                    allProducts = data.products || [];
                    document.getElementById('gridLoading').style.display = 'none';
                    renderProductGrid();
                })
                .catch(err => {
                    console.error('Error fetching grid data:', err);
                    document.getElementById('gridLoading').style.display = 'none';
                });
        }

        // Render Product Cards Grid
        function renderProductGrid() {
            const gridContainer = document.getElementById('productGrid');
            const noFound = document.getElementById('noProductsFound');
            gridContainer.innerHTML = '';

            const searchTerm = (searchInput.value || '').toLowerCase().trim();

            const filtered = allProducts.filter(p => {
                const matchName = p.name.toLowerCase().includes(searchTerm);
                const matchSku = p.sku ? p.sku.toLowerCase().includes(searchTerm) : false;
                const matchVariantSku = p.variants.some(v => v.sku && v.sku.toLowerCase().includes(searchTerm));
                return matchName || matchSku || matchVariantSku;
            });

            if (filtered.length === 0) {
                gridContainer.style.display = 'none';
                noFound.style.display = 'block';
                return;
            }

            noFound.style.display = 'none';
            gridContainer.style.display = 'flex';

            filtered.forEach(product => {
                const col = document.createElement('div');
                col.className = 'col';

                let imgHtml = '';
                if (product.image_url) {
                    imgHtml = `<img src="${product.image_url}" alt="${product.name}" class="pos-product-img rounded-top">`;
                } else {
                    imgHtml = `<div class="pos-product-placeholder rounded-top"><i class="ri-image-line"></i></div>`;
                }

                let stockBadgeHtml = '';
                if (product.variants.length === 1) {
                    const v = product.variants[0];
                    const isAvail = v.stock > 0;
                    const stockClass = isAvail ? 'bg-success' : (v.total_stock > 0 ? 'bg-warning text-dark' : 'bg-danger');
                    const text = v.stock === v.total_stock
                        ? `Stock: ${v.stock}`
                        : `WH: ${v.stock} | Total: ${v.total_stock}`;
                    stockBadgeHtml = `<span class="badge ${stockClass} fs-11" title="Warehouse Stock vs Total Stock">${text}</span>`;
                } else {
                    const hasWhStock = product.variants.some(v => v.stock > 0);
                    const hasTotalStock = product.variants.some(v => v.total_stock > 0);
                    const stockClass = hasWhStock ? 'bg-success' : (hasTotalStock ? 'bg-warning text-dark' : 'bg-danger');
                    const text = hasWhStock ? 'In Stock' : (hasTotalStock ? 'Other WH Only' : 'Out of stock');
                    stockBadgeHtml = `<span class="badge ${stockClass} fs-11">${text}</span>`;
                }

                col.innerHTML = `
                    <div class="card h-100 pos-product-card rounded shadow-sm overflow-hidden mb-0" data-product-id="${product.id}">
                        ${imgHtml}
                        <div class="card-body p-2 d-flex flex-column justify-content-between">
                            <div>
                                <h6 class="fs-13 fw-semibold text-dark mb-1 text-truncate" title="${product.name}">${product.name}</h6>
                                <small class="text-muted d-block fs-11">${product.variants.length} Variant(s)</small>
                            </div>
                            <div class="mt-2 d-flex justify-content-between align-items-center">
                                ${stockBadgeHtml}
                                <i class="ri-add-circle-fill text-primary font-18"></i>
                            </div>
                        </div>
                    </div>
                `;

                col.querySelector('.pos-product-card').addEventListener('click', () => {
                    openVariantModal(product);
                });

                gridContainer.appendChild(col);
            });
        }

        // Open Variant Selection Modal
        function openVariantModal(product) {
            document.getElementById('modalProductName').innerText = product.name;
            document.getElementById('modalProductSku').innerText = product.sku ? `SKU: ${product.sku}` : '';

            const imgContainer = document.getElementById('modalProductImgContainer');
            if (product.image_url) {
                imgContainer.innerHTML = `<img src="${product.image_url}" class="rounded" style="width:50px; height:50px; object-fit:cover;">`;
            } else {
                imgContainer.innerHTML = `<div class="bg-light rounded d-flex align-items-center justify-content-center" style="width:50px; height:50px;"><i class="ri-image-line text-muted font-20"></i></div>`;
            }

            const tbody = document.getElementById('modalVariantsList');
            tbody.innerHTML = '';

            const customerType = getSelectedCustomerType();

            product.variants.forEach(variant => {
                const tr = document.createElement('tr');
                tr.className = 'variant-row';

                let price = variant.price;
                if (customerType === 'dealer' && variant.dealer_price > 0) {
                    price = variant.dealer_price;
                } else if (customerType === 'special_dealer' && variant.special_dealer_price > 0) {
                    price = variant.special_dealer_price;
                }

                const isAvailable = variant.stock > 0;
                let stockBadge = '';
                if (variant.stock === variant.total_stock) {
                    stockBadge = isAvailable 
                        ? `<span class="badge bg-soft-success text-success">${variant.stock} ${variant.unit_name}</span>`
                        : `<span class="badge bg-soft-danger text-danger">0 ${variant.unit_name}</span>`;
                } else {
                    stockBadge = `
                        <div class="d-flex flex-column align-items-start gap-1">
                            <span class="badge ${isAvailable ? 'bg-soft-success text-success' : 'bg-soft-danger text-danger'}">
                                WH: ${variant.stock} ${variant.unit_name}
                            </span>
                            <span class="badge bg-light text-muted border" style="font-size: 10px;">
                                Total: ${variant.total_stock} ${variant.unit_name}
                            </span>
                        </div>
                    `;
                }

                tr.innerHTML = `
                    <td class="py-2">
                        <strong class="d-block text-dark fs-14">${variant.name}</strong>
                        <small class="text-muted fs-12">${variant.unit_qty} ${variant.unit_name}</small>
                    </td>
                    <td class="py-2">${stockBadge}</td>
                    <td class="py-2"><strong class="text-primary fs-14">৳${price.toFixed(0)}</strong></td>
                    <td class="text-end py-2">
                        <button type="button" class="btn btn-sm ${isAvailable ? 'btn-primary' : 'btn-outline-primary'} px-3 add-to-cart-btn">
                            <i class="ri-shopping-cart-2-line me-1"></i> Add
                        </button>
                    </td>
                `;

                tr.querySelector('.add-to-cart-btn').addEventListener('click', function(e) {
                    addToCart(product, variant, price);

                    const btn = this;
                    const originalHtml = btn.innerHTML;
                    btn.innerHTML = '<i class="ri-check-line me-1"></i> Added';
                    btn.classList.remove('btn-primary', 'btn-outline-primary');
                    btn.classList.add('btn-success');

                    setTimeout(() => {
                        btn.innerHTML = originalHtml;
                        btn.classList.remove('btn-success');
                        btn.classList.add(isAvailable ? 'btn-primary' : 'btn-outline-primary');
                    }, 800);
                });

                tbody.appendChild(tr);
            });

            variantModal.show();
        }

        // Get currently selected customer type
        function getSelectedCustomerType() {
            if (!customerSelect) return 'customer';
            const selectedOpt = customerSelect.options[customerSelect.selectedIndex];
            return selectedOpt ? (selectedOpt.dataset.customerType || 'customer') : 'customer';
        }

        // Add variant to cart
        function addToCart(product, variant, price) {
            const existing = cart.find(item => item.variant_id === variant.id);
            if (existing) {
                existing.qty += 1;
            } else {
                cart.push({
                    variant_id: variant.id,
                    product_name: product.name,
                    variant_name: variant.name,
                    price: price,
                    stock: variant.stock,
                    unit_qty: variant.unit_qty,
                    qty: 1
                });
            }
            renderCart();
        }

        // Render Cart items table
        function renderCart() {
            const cartTbody = document.getElementById('cart-items');
            const emptyMsg = document.getElementById('emptyCartMessage');
            const countBadge = document.getElementById('cartItemCountBadge');

            cartTbody.innerHTML = '';
            countBadge.innerText = `${cart.length} Item(s)`;

            if (cart.length === 0) {
                emptyMsg.style.display = 'block';
            } else {
                emptyMsg.style.display = 'none';
            }

            cart.forEach((item, index) => {
                const tr = document.createElement('tr');
                const subtotal = item.qty * item.price;
                const displayName = (item.variant_name && item.variant_name !== 'Default' && item.variant_name !== item.product_name)
                    ? `${item.product_name} - ${item.variant_name}`
                    : item.product_name;

                tr.innerHTML = `
                    <td>
                        <input type="hidden" name="items[${index}][product_variant_id]" value="${item.variant_id}">
                        <span class="fw-semibold text-dark fs-12 d-block text-truncate" style="max-width: 140px;" title="${displayName}">${displayName}</span>
                        <small class="text-muted fs-10">Max: ${item.stock}</small>
                    </td>
                    <td>
                        <input type="number" step="0.001" name="items[${index}][qty]" class="form-control form-control-sm qty-input" value="${item.qty}" min="0.001" required>
                    </td>
                    <td>
                        <input type="number" step="1" name="items[${index}][unit_price]" class="form-control form-control-sm price-input" value="${item.price.toFixed(0)}" required>
                    </td>
                    <td>
                        <input type="number" step="1" class="form-control form-control-sm row-subtotal" value="${subtotal.toFixed(0)}" readonly>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-xs btn-outline-danger remove-cart-btn"><i class="ri-delete-bin-line"></i></button>
                    </td>
                `;

                tr.querySelector('.qty-input').addEventListener('input', function() {
                    item.qty = parseFloat(this.value) || 0;
                    calculateTotal();
                    updateFullPayment();
                });

                tr.querySelector('.price-input').addEventListener('input', function() {
                    item.price = parseFloat(this.value) || 0;
                    calculateTotal();
                    updateFullPayment();
                });

                tr.querySelector('.remove-cart-btn').addEventListener('click', function() {
                    cart.splice(index, 1);
                    renderCart();
                    calculateTotal();
                    updateFullPayment();
                });

                cartTbody.appendChild(tr);
            });

            calculateTotal();
            updateFullPayment();
        }

        // Calculate Cart Totals
        function calculateTotal() {
            let subtotal = 0;
            let totalWeight = 0;

            cart.forEach((item, i) => {
                const sub = item.qty * item.price;
                subtotal += sub;
                totalWeight += item.qty * (item.unit_qty || 1);

                const rows = document.querySelectorAll('#cart-items tr');
                if (rows[i]) {
                    const rowSub = rows[i].querySelector('.row-subtotal');
                    if (rowSub) rowSub.value = sub.toFixed(0);
                }
            });

            if (deliveryMethodSel && deliveryMethodSel.value === 'steadfast') {
                document.getElementById('deliveryTypeContainer').style.display = 'block';
                if (deliveryTypeSel && deliveryTypeSel.value === '1') {
                    if (deliveryChargeInput) deliveryChargeInput.value = 0;
                } else if (deliveryTypeSel && deliveryTypeSel.value === '0') {
                    const autoCharge = Math.max(1, Math.ceil(totalWeight)) * 20;
                    if (deliveryChargeInput) deliveryChargeInput.value = autoCharge;
                }
            } else {
                const typeContainer = document.getElementById('deliveryTypeContainer');
                if (typeContainer) typeContainer.style.display = 'none';
            }

            const delivery = parseFloat(deliveryChargeInput ? deliveryChargeInput.value : 0) || 0;
            const discount = parseFloat(discountInput ? discountInput.value : 0) || 0;

            const grandTotal = Math.max(0, subtotal + delivery - discount);
            const grandTotalDisplay = document.getElementById('grandTotalDisplay');
            if (grandTotalDisplay) grandTotalDisplay.innerText = grandTotal.toFixed(0);

            return grandTotal;
        }

        function updateFullPayment() {
            if (isPromotionalCheckbox && isPromotionalCheckbox.checked) {
                if (paidAmountInput) paidAmountInput.value = 0;
                return;
            }
            if (fullPaymentToggle && fullPaymentToggle.checked) {
                paidAmountInput.value = calculateTotal().toFixed(0);
            }
        }

        function handlePromotionalState() {
            if (isPromotionalCheckbox && isPromotionalCheckbox.checked) {
                if (paidAmountInput) {
                    paidAmountInput.value = 0;
                    paidAmountInput.readOnly = true;
                }
                if (fullPaymentToggle) {
                    fullPaymentToggle.checked = false;
                    fullPaymentToggle.disabled = true;
                }
                if (paymentMethodSelect) paymentMethodSelect.disabled = true;
            } else {
                if (paidAmountInput) paidAmountInput.readOnly = false;
                if (fullPaymentToggle) fullPaymentToggle.disabled = false;
                if (paymentMethodSelect) paymentMethodSelect.disabled = false;
            }
        }

        // Event Listeners
        if (warehouseSelect) warehouseSelect.addEventListener('change', loadProductsGrid);
        if (searchInput) searchInput.addEventListener('input', renderProductGrid);

        if (deliveryMethodSel) deliveryMethodSel.addEventListener('change', calculateTotal);
        if (deliveryTypeSel) deliveryTypeSel.addEventListener('change', calculateTotal);

        if (deliveryChargeInput) deliveryChargeInput.addEventListener('input', () => { calculateTotal(); updateFullPayment(); });
        if (discountInput) discountInput.addEventListener('input', () => { calculateTotal(); updateFullPayment(); });
        if (fullPaymentToggle) fullPaymentToggle.addEventListener('change', updateFullPayment);

        if (isPromotionalCheckbox) {
            isPromotionalCheckbox.addEventListener('change', function() {
                handlePromotionalState();
                updateFullPayment();
            });
            handlePromotionalState();
        }

        // Re-evaluate variant prices in cart when customer changes
        $('#customer_id').on('change', function() {
            const customerType = getSelectedCustomerType();
            cart.forEach(cartItem => {
                let product = allProducts.find(p => p.variants.some(v => v.id === cartItem.variant_id));
                if (product) {
                    let variant = product.variants.find(v => v.id === cartItem.variant_id);
                    if (variant) {
                        let newPrice = variant.price;
                        if (customerType === 'dealer' && variant.dealer_price > 0) {
                            newPrice = variant.dealer_price;
                        } else if (customerType === 'special_dealer' && variant.special_dealer_price > 0) {
                            newPrice = variant.special_dealer_price;
                        }
                        cartItem.price = newPrice;
                    }
                }
            });
            renderCart();
        });

        // Add Customer AJAX
        document.getElementById('addCustomerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const name = document.getElementById('new_customer_name').value;
            const phone = document.getElementById('new_customer_phone').value;
            const email = document.getElementById('new_customer_email').value;
            const customerType = document.getElementById('new_customer_type').value;
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
                body: JSON.stringify({ name: name, phone: phone, email: email, customer_type: customerType })
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        if (response.status === 422 && err.errors) {
                            const messages = Object.values(err.errors).flat().join('\n');
                            throw new Error(messages);
                        }
                        throw new Error(err.message || 'An error occurred.');
                    });
                }
                return response.json();
            })
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Save Customer';

                if(data.success) {
                    const customer = data.customer;
                    const newOption = new Option(customer.name + ' (Wallet: 0, Due: 0)', customer.id, true, true);
                    newOption.dataset.customerType = customer.customer_type || 'customer';
                    $('#customer_id').append(newOption).trigger('change');

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
                alert('Error: ' + error.message);
            });
        });

        // Initial Load
        loadProductsGrid();
    });
</script>
@endsection
