@extends('layouts.vertical', ['page_title' => 'Customers', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('css')
@endsection

@section('content')
    <div class="container-fluid">
         <div class="row">
            <div class="col-12">
                <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                    <h4 class="page-title">Customers</h4>
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                        <li class="breadcrumb-item active">Customers</li>
                    </ol>
                </div>
            </div>
         </div>

         @if (session('success'))
             <div class="alert alert-success alert-dismissible fade show" role="alert">
                 {{ session('success') }}
                 <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
             </div>
         @endif

         <div class="row">
             <div class="col-12">
                 <div class="card">
                     <div class="card-body">
                         {{-- ── Filters Row ────────────────────────────────────────── --}}
                         <form action="{{ route('customers.index') }}" method="GET" id="filterForm">
                             <div class="row mb-3 align-items-end g-2">
                                 {{-- Search --}}
                                 <div class="col-sm-5">
                                     <div class="input-group dropdown" id="searchDropdown">
                                         <input type="text" class="form-control" name="search" id="searchInput"
                                             placeholder="Search by Name, Company, Email, or Phone..."
                                             value="{{ request('search') }}" autocomplete="off"
                                             data-bs-toggle="dropdown" aria-expanded="false">
                                         <button class="btn btn-primary" type="submit"><i class="ri-search-line"></i></button>
                                         @if(request('search'))
                                             <a href="{{ route('customers.index', array_merge(request()->except('search', 'page'), [])) }}" class="btn btn-light" title="Clear Search"><i class="ri-close-line"></i></a>
                                         @endif
                                         <ul class="dropdown-menu w-100 shadow p-0" id="searchResults" style="max-height: 300px; overflow-y: auto;"></ul>
                                     </div>
                                 </div>

                                 {{-- District Dropdown --}}
                                 <div class="col-sm-3">
                                     <select name="district" id="districtFilter" class="form-control" data-toggle="select2" data-allow-clear="true" data-placeholder="All Districts">
                                         <option value="">All Districts</option>
                                         @foreach($districts as $district)
                                             <option value="{{ $district }}" {{ request('district') == $district ? 'selected' : '' }}>{{ $district }}</option>
                                         @endforeach
                                     </select>
                                 </div>

                                 {{-- Buttons --}}
                                 <div class="col-sm-4 d-flex gap-2 align-items-center">
                                     <button type="submit" class="btn btn-secondary"><i class="ri-filter-3-line me-1"></i>Filter</button>
                                     @if(request('district') || request('search'))
                                         <a href="{{ route('customers.index') }}" class="btn btn-light"><i class="ri-refresh-line me-1"></i>Clear</a>
                                     @endif
                                     <a href="{{ route('settings.index') }}" class="btn btn-outline-{{ \App\Models\Setting::isCreditLimitEnabled() ? 'success' : 'secondary' }}" title="Credit Limit Enforcement is {{ \App\Models\Setting::isCreditLimitEnabled() ? 'ENABLED' : 'DISABLED' }} - Click to Configure">
                                         <i class="ri-shield-check-line me-1"></i> Limit: <strong>{{ \App\Models\Setting::isCreditLimitEnabled() ? 'ON' : 'OFF' }}</strong>
                                     </a>
                                     <a href="{{ route('customers.create') }}" class="btn btn-danger ms-auto"><i class="ri-add-line me-1"></i> Add Customer</a>
                                     <a href="{{ route('customers.export', request()->all()) }}" class="btn btn-success"><i class="ri-file-excel-2-line me-1"></i> Export</a>
                                 </div>
                             </div>
                             {{-- preserve page --}}
                             <input type="hidden" name="page" value="1">
                         </form>

                         <div class="table-responsive-sm">
                             <table class="table table-centered table-hover mb-0">
                                 <thead class="table-light">
                                     <tr>
                                         <th><i class="ri-user-line text-muted me-1"></i> Customer & Contact</th>
                                         <th style="width: 150px;"><i class="ri-price-tag-3-line text-muted me-1"></i> Customer Type</th>
                                         <th style="width: 220px;"><i class="ri-wallet-line text-muted me-1"></i> Financial Overview</th>
                                         <th style="width: 140px;" class="text-end"><i class="ri-settings-3-line text-muted me-1"></i> Action</th>
                                     </tr>
                                 </thead>
                                 <tbody>
                                     @forelse($customers as $customer)
                                         <tr>
                                             {{-- Customer Name, Company, Phone, District, Address - Each on a separate line with icon. Missing items are hidden. --}}
                                             <td>
                                                 <div class="d-flex flex-column gap-1 py-1">
                                                     {{-- 1. Name --}}
                                                     @if(!empty($customer->name))
                                                         <div>
                                                             <a href="{{ route('customers.show', $customer->id) }}" class="text-dark fw-bold font-14 text-decoration-none">
                                                                 <i class="ri-user-3-line text-primary me-1"></i>{{ $customer->name }}
                                                             </a>
                                                         </div>
                                                     @endif

                                                     {{-- 2. Company (hidden if empty) --}}
                                                     @if(!empty($customer->company))
                                                         <div class="text-muted font-12">
                                                             <i class="ri-building-line text-secondary me-1"></i>{{ $customer->company }}
                                                         </div>
                                                     @endif

                                                     {{-- 3. Phone (hidden if empty) --}}
                                                     @if(!empty($customer->phone))
                                                         <div>
                                                             <a href="tel:{{ $customer->phone }}" class="text-body font-12 text-decoration-none">
                                                                 <i class="ri-phone-line text-success me-1"></i>{{ $customer->phone }}
                                                             </a>
                                                         </div>
                                                     @endif

                                                     {{-- 4. District (hidden if empty) --}}
                                                     @if(!empty($customer->district))
                                                         <div class="text-muted font-12">
                                                             <i class="ri-map-pin-range-line text-info me-1"></i>{{ $customer->district }}
                                                         </div>
                                                     @endif

                                                     {{-- 5. Address (hidden if empty) --}}
                                                     @if(!empty($customer->address))
                                                         <div class="text-muted font-12 text-truncate" style="max-width: 420px;" title="{{ $customer->address }}">
                                                             <i class="ri-map-pin-2-line text-danger me-1"></i>{{ $customer->address }}
                                                         </div>
                                                     @endif
                                                 </div>
                                             </td>

                                             {{-- Customer Type in its own dedicated column --}}
                                             <td>
                                                 @php
                                                     $type = strtolower($customer->customer_type ?? 'customer');
                                                     $badgeConfig = match($type) {
                                                         'dealer' => ['class' => 'bg-soft-warning text-warning border border-warning border-opacity-50', 'icon' => 'ri-store-2-line', 'label' => 'Dealer'],
                                                         'special_dealer' => ['class' => 'bg-soft-danger text-danger border border-danger border-opacity-50', 'icon' => 'ri-vip-crown-line', 'label' => 'Special Dealer'],
                                                         'corporate' => ['class' => 'bg-soft-primary text-primary border border-primary border-opacity-50', 'icon' => 'ri-building-4-line', 'label' => 'Corporate'],
                                                         'wholesaler' => ['class' => 'bg-soft-success text-success border border-success border-opacity-50', 'icon' => 'ri-truck-line', 'label' => 'Wholesaler'],
                                                         default => ['class' => 'bg-soft-info text-info border border-info border-opacity-25', 'icon' => 'ri-user-follow-line', 'label' => ucwords(str_replace('_', ' ', $customer->customer_type ?: 'Customer'))],
                                                     };
                                                 @endphp
                                                 <span class="badge {{ $badgeConfig['class'] }} font-12 px-2 py-1 d-inline-flex align-items-center gap-1">
                                                     <i class="{{ $badgeConfig['icon'] }}"></i> {{ $badgeConfig['label'] }}
                                                 </span>
                                             </td>

                                             {{-- Financials: Due, Limit, Wallet - Missing/Zero items hidden --}}
                                             <td>
                                                 <div class="d-flex flex-column gap-1 py-1 font-12">
                                                     {{-- Due --}}
                                                     <div>
                                                         <span class="text-muted font-11 text-uppercase fw-semibold me-1">
                                                             <i class="ri-money-dollar-circle-line text-secondary me-1"></i>Due:
                                                         </span>
                                                         <span class="fs-13 {{ $customer->total_due > 0 ? 'text-danger fw-bold' : 'text-success fw-semibold' }}">
                                                             ৳ {{ number_format($customer->total_due, 0) }}
                                                         </span>
                                                     </div>

                                                     {{-- Credit Limit (hidden if empty or 0) --}}
                                                     @if(!empty($customer->credit_limit) && (float)$customer->credit_limit > 0)
                                                         <div class="text-muted">
                                                             <i class="ri-shield-check-line text-info me-1"></i><span class="text-secondary font-11">Limit:</span> ৳ {{ number_format($customer->credit_limit, 0) }}
                                                         </div>
                                                     @endif

                                                     {{-- Wallet (hidden if empty or 0) --}}
                                                     @if(!empty($customer->wallet_balance) && (float)$customer->wallet_balance > 0)
                                                         <div class="text-muted">
                                                             <i class="ri-wallet-3-line text-success me-1"></i><span class="text-secondary font-11">Wallet:</span> 
                                                             <span class="text-success fw-bold">
                                                                 ৳ {{ number_format($customer->wallet_balance, 0) }}
                                                             </span>
                                                         </div>
                                                     @endif
                                                 </div>
                                             </td>

                                             {{-- Action Buttons --}}
                                             <td class="text-end">
                                                 <div class="d-flex align-items-center justify-content-end gap-1">
                                                     {{-- Quick WhatsApp Share Button --}}
                                                     <button type="button" class="btn btn-soft-success btn-sm btn-whatsapp-share" 
                                                             title="Share via WhatsApp"
                                                             data-customer="{{ json_encode([
                                                                 'id' => $customer->id,
                                                                 'name' => $customer->name,
                                                                 'company' => $customer->company,
                                                                 'phone' => $customer->phone,
                                                                 'email' => $customer->email,
                                                                 'district' => $customer->district,
                                                                 'address' => $customer->address,
                                                                 'customer_type' => $customer->customer_type,
                                                                 'credit_limit' => (float)$customer->credit_limit,
                                                                 'total_due' => (float)$customer->total_due,
                                                                 'wallet_balance' => (float)$customer->wallet_balance,
                                                                 'statement_pdf_url' => \App\Models\StatementToken::createOrGetForCustomer($customer)->getShortUrl(),
                                                                 'show_url' => route('customers.show', $customer->id)
                                                             ]) }}">
                                                         <i class="ri-whatsapp-line fs-5 align-middle"></i>
                                                     </button>

                                                     <div class="dropdown">
                                                         <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                             <i class="ri-settings-3-line"></i> Actions
                                                         </button>
                                                         <ul class="dropdown-menu dropdown-menu-end">
                                                             <li><a class="dropdown-item text-info" href="{{ route('customers.show', $customer) }}"><i class="ri-eye-line me-2"></i> View</a></li>
                                                             <li><a class="dropdown-item text-primary" href="{{ route('customers.edit', $customer) }}"><i class="ri-edit-box-line me-2"></i> Edit</a></li>
                                                             <li>
                                                                 <a class="dropdown-item text-success btn-whatsapp-share" href="javascript:void(0)"
                                                                    data-customer="{{ json_encode([
                                                                        'id' => $customer->id,
                                                                        'name' => $customer->name,
                                                                        'company' => $customer->company,
                                                                        'phone' => $customer->phone,
                                                                        'email' => $customer->email,
                                                                        'district' => $customer->district,
                                                                        'address' => $customer->address,
                                                                        'customer_type' => $customer->customer_type,
                                                                        'credit_limit' => (float)$customer->credit_limit,
                                                                        'total_due' => (float)$customer->total_due,
                                                                        'wallet_balance' => (float)$customer->wallet_balance,
                                                                        'statement_pdf_url' => \App\Models\StatementToken::createOrGetForCustomer($customer)->getShortUrl(),
                                                                        'show_url' => route('customers.show', $customer->id)
                                                                    ]) }}">
                                                                     <i class="ri-whatsapp-line me-2"></i> Share via WhatsApp
                                                                 </a>
                                                             </li>
                                                             <li><hr class="dropdown-divider"></li>
                                                             <li>
                                                                 <form id="delete-form-{{ $customer->id }}" action="{{ route('customers.destroy', $customer) }}" method="POST" class="d-inline">
                                                                     @csrf
                                                                     @method('DELETE')
                                                                     <button type="button" class="dropdown-item text-danger" onclick="confirmDelete('{{ $customer->id }}')"><i class="ri-delete-bin-line me-2"></i> Delete</button>
                                                                 </form>
                                                             </li>
                                                         </ul>
                                                     </div>
                                                 </div>
                                             </td>
                                         </tr>
                                     @empty
                                         <tr>
                                             <td colspan="4" class="text-center py-4 text-muted">No customers found.</td>
                                         </tr>
                                     @endforelse
                                 </tbody>
                             </table>
                         </div>
                         
                         <div class="mt-3">
                             {{ $customers->links('pagination::bootstrap-5') }}
                         </div>
                     </div>
                 </div>
             </div>
         </div>
    </div>

    {{-- WhatsApp Share Modal Partial --}}
    @include('customers.whatsapp-modal')
@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-submit when district filter is selected or cleared (Select2 events require jQuery)
        var jq = window.jQuery;
        if (jq) {
            jq('#districtFilter').on('select2:select select2:clear', function() {
                document.getElementById('filterForm').submit();
            });
        }

        // Search AJAX autocomplete
        let timer;
        const searchInput = document.getElementById('searchInput');
        const searchResults = document.getElementById('searchResults');
        const bsDropdown = new bootstrap.Dropdown(searchInput);

        searchInput.addEventListener('input', function() {
            clearTimeout(timer);
            let val = this.value.trim();

            if (val.length === 0) {
                searchResults.innerHTML = '';
                bsDropdown.hide();
                return;
            }

            timer = setTimeout(() => {
                fetch(`{{ route('customers.ajax.search') }}?q=${encodeURIComponent(val)}`)
                    .then(response => response.json())
                    .then(data => {
                        searchResults.innerHTML = '';
                        if (data.length > 0) {
                            data.forEach(customer => {
                                const li = document.createElement('li');
                                const dueText = parseFloat(customer.total_due) > 0 ? `<span class="text-danger float-end">Due: ${parseFloat(customer.total_due).toLocaleString()} TK</span>` : '';
                                const companyText = customer.company ? `<span class="badge bg-light text-secondary border ms-1 font-11">${customer.company}</span>` : '';
                                li.innerHTML = `<a class="dropdown-item py-2 border-bottom" href="/customers/${customer.id}" target="_blank">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="fw-bold text-dark">${customer.name} ${companyText}</div>
                                        ${dueText}
                                    </div>
                                    <small class="text-muted"><i class="ri-phone-line"></i> ${customer.phone}</small>
                                </a>`;
                                searchResults.appendChild(li);
                            });
                            bsDropdown.show();
                        } else {
                            searchResults.innerHTML = '<li class="dropdown-item py-2 text-muted text-center">No customers found</li>';
                            bsDropdown.show();
                        }
                    })
                    .catch(error => console.error('Error fetching customers:', error));
            }, 400);
        });

        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                bsDropdown.hide();
            }
        });
    });

    function confirmDelete(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this! This will delete the customer and all related data.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('delete-form-' + id).submit();
            }
        })
    }
</script>
@endsection
