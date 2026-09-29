@extends('layouts.vertical', ['page_title' => 'Create Target Campaign', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('css')
<style>
    .select2-container--default .select2-selection--multiple {
        min-height: 38px;
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        padding: 2px 6px;
    }
    .select2-container--default.select2-container--focus .select2-selection--multiple {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13,110,253,.25);
        outline: 0;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        font-size: 12px;
        margin-top: 4px;
    }
    /* Highlight already-selected items in the open dropdown */
    .select2-results__option[aria-selected="true"] {
        background-color: #e8f0fe !important;
        color: #1a56db !important;
        font-weight: 600;
    }
    .select2-results__option[aria-selected="true"]::before {
        content: '✓  ';
        font-weight: 700;
        color: #1a56db;
    }
    .select2-results__option[aria-selected="true"]:hover,
    .select2-container--default .select2-results__option--highlighted[aria-selected="true"] {
        background-color: #c7d9fd !important;
        color: #1a56db !important;
    }
    .select2-container { width: 100% !important; }
    .item-row:hover { background-color: #f8f9fa; }
    .bulk-panel { background: #f0f7ff; border: 1px dashed #86b7fe; border-radius: 8px; padding: 16px; }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                <h4 class="page-title">Create Monthly Target Campaign</h4>
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('customer-targets.index') }}">Customer Targets</a></li>
                    <li class="breadcrumb-item active">Create</li>
                </ol>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h6 class="alert-heading fw-bold mb-1"><i class="ri-error-warning-line me-1"></i> Please fix the following errors:</h6>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('customer-targets.store') }}" method="POST" id="campaignForm">
        @csrf
        <div class="row">
            {{-- Campaign Details --}}
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header border-bottom py-3">
                        <h5 class="card-title mb-0">1. Campaign Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label fw-bold">Campaign Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="e.g. October Incentive Bonus" value="{{ old('name') }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Target Month (Optional)</label>
                                <input type="month" id="target_month" name="target_month" class="form-control" value="{{ old('target_month') }}">
                                <small class="text-muted fs-11">Auto-fills start/end dates.</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select" required>
                                    <option value="active" {{ old('status','active')==='active'?'selected':'' }}>Active</option>
                                    <option value="completed" {{ old('status')==='completed'?'selected':'' }}>Completed</option>
                                    <option value="cancelled" {{ old('status')==='cancelled'?'selected':'' }}>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Start Date <span class="text-danger">*</span></label>
                                <input type="date" id="start_date" name="start_date" class="form-control" value="{{ old('start_date', $startDate) }}" required>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label fw-bold mb-0">End Date</label>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" id="is_ongoing" name="is_ongoing" value="1" {{ old('is_ongoing')?'checked':'' }}>
                                        <label class="form-check-label fs-12 fw-semibold text-primary" for="is_ongoing">Until Stopped</label>
                                    </div>
                                </div>
                                <input type="date" id="end_date" name="end_date" class="form-control" value="{{ old('end_date') }}">
                                <small id="end_date_help" class="text-muted fs-11">Or toggle "Until Stopped" for open-ended.</small>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Notes / Terms (Optional)</label>
                                <input type="text" name="description" class="form-control" placeholder="Any notes about this campaign." value="{{ old('description') }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bulk Add Targets --}}
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header border-bottom py-3">
                        <h5 class="card-title mb-0">2. Set Customer Targets</h5>
                    </div>
                    <div class="card-body">

                        {{-- Bulk Generator Panel --}}
                        <div class="bulk-panel mb-4">
                            <p class="fw-bold text-primary mb-3"><i class="ri-flashlight-line me-1"></i> Bulk Add — Select customers &amp; products, set qty &amp; bonus, then click "Add Rows"</p>
                            <div class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Customers <span class="text-danger">*</span></label>
                                    <select id="bulk_customers" class="form-select" multiple>
                                        @foreach($customers as $c)
                                            <option value="{{ $c->id }}">{{ $c->name }}{{ $c->phone ? ' ('.$c->phone.')' : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Products <span class="text-danger">*</span></label>
                                    <select id="bulk_products" class="form-select" multiple>
                                        @foreach($products as $p)
                                            <option value="{{ $p->id }}" data-unit="{{ $p->unit ? $p->unit->name : 'units' }}">{{ $p->name }}{{ $p->unit ? ' ('.$p->unit->name.')' : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-bold">Target Qty <span class="text-danger">*</span></label>
                                    <input type="number" id="bulk_qty" class="form-control" placeholder="e.g. 100" min="0.01" step="any">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-bold">Bonus / Unit (৳) <span class="text-danger">*</span></label>
                                    <input type="number" id="bulk_bonus" class="form-control" placeholder="e.g. 5.00" min="0.01" step="0.01">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" id="bulkAddBtn" class="btn btn-primary w-100">
                                        <i class="ri-add-circle-line me-1"></i> Add Rows
                                    </button>
                                </div>
                            </div>
                            <small class="text-muted mt-2 d-block">Tip: Selecting 3 customers × 4 products will generate 12 rows. You can adjust individual rows below.</small>
                        </div>

                        {{-- Rows Table --}}
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle" id="targetsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 28%;">Customer</th>
                                        <th style="width: 33%;">Product</th>
                                        <th style="width: 17%;">Monthly Target Qty</th>
                                        <th style="width: 16%;">Bonus / Unit (৳)</th>
                                        <th style="width: 6%;" class="text-center">Del</th>
                                    </tr>
                                </thead>
                                <tbody id="targetsTableBody">
                                    <tr id="emptyRow">
                                        <td colspan="5" class="text-center text-muted py-4">
                                            <i class="ri-inbox-line fs-24 d-block mb-1"></i>
                                            Use the bulk panel above to add target rows.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        {{-- Hidden counter for validation --}}
                        <input type="hidden" id="rowCount" name="_row_count" value="0">
                    </div>

                    <div class="card-footer bg-light py-3 text-end">
                        <a href="{{ route('customer-targets.index') }}" class="btn btn-outline-secondary me-2">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4" id="saveBtn">
                            <i class="ri-save-line me-1"></i> Save Campaign
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('script')
<script>
    const productsMap = {};
    @foreach($products as $p)
        productsMap[{{ $p->id }}] = {
            name: @json($p->name),
            unit: @json($p->unit ? $p->unit->name : 'units')
        };
    @endforeach

    const customersMap = {};
    @foreach($customers as $c)
        customersMap[{{ $c->id }}] = @json($c->name . ($c->phone ? ' ('.$c->phone.')' : ''));
    @endforeach

    let rowIndex = 0;

    function removeEmptyRow() {
        const empty = document.getElementById('emptyRow');
        if (empty) empty.remove();
    }

    function addRow(customerId, customerLabel, productId, productLabel, unitName, targetQty = '', bonusPerUnit = '') {
        removeEmptyRow();
        const tbody = document.getElementById('targetsTableBody');
        const tr = document.createElement('tr');
        tr.className = 'item-row';
        tr.id = `row_${rowIndex}`;
        tr.innerHTML = `
            <td>
                <span class="fw-semibold">${customerLabel}</span>
                <input type="hidden" name="items[${rowIndex}][customer_id]" value="${customerId}">
            </td>
            <td>
                <span>${productLabel}</span>
                <small class="text-muted d-block">${unitName}</small>
                <input type="hidden" name="items[${rowIndex}][product_id]" value="${productId}">
            </td>
            <td>
                <div class="input-group">
                    <input type="number" step="any" min="0.01" name="items[${rowIndex}][target_qty]"
                        class="form-control" placeholder="Qty" value="${targetQty}" required>
                    <span class="input-group-text">${unitName}</span>
                </div>
            </td>
            <td>
                <div class="input-group">
                    <span class="input-group-text">৳</span>
                    <input type="number" step="0.01" min="0.01" name="items[${rowIndex}][bonus_per_unit]"
                        class="form-control" placeholder="0.00" value="${bonusPerUnit}" required>
                </div>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow('row_${rowIndex}')" title="Remove">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
        rowIndex++;
        updateRowCount();
    }

    function removeRow(id) {
        const row = document.getElementById(id);
        if (row) row.remove();
        updateRowCount();
        if (document.getElementById('targetsTableBody').children.length === 0) {
            showEmptyRow();
        }
    }

    function showEmptyRow() {
        const tbody = document.getElementById('targetsTableBody');
        const tr = document.createElement('tr');
        tr.id = 'emptyRow';
        tr.innerHTML = `<td colspan="5" class="text-center text-muted py-4">
            <i class="ri-inbox-line fs-24 d-block mb-1"></i>
            Use the bulk panel above to add target rows.
        </td>`;
        tbody.appendChild(tr);
    }

    function updateRowCount() {
        const count = document.querySelectorAll('#targetsTableBody .item-row').length;
        document.getElementById('rowCount').value = count;
    }

    // Bulk Add button
    document.getElementById('bulkAddBtn').addEventListener('click', function () {
        const customerIds = $('#bulk_customers').val();
        const productIds = $('#bulk_products').val();
        const qty = document.getElementById('bulk_qty').value;
        const bonus = document.getElementById('bulk_bonus').value;

        if (!customerIds || customerIds.length === 0) {
            alert('Please select at least one customer.');
            return;
        }
        if (!productIds || productIds.length === 0) {
            alert('Please select at least one product.');
            return;
        }
        if (!qty || parseFloat(qty) <= 0) {
            alert('Please enter a valid target quantity.');
            return;
        }
        if (!bonus || parseFloat(bonus) <= 0) {
            alert('Please enter a valid bonus per unit.');
            return;
        }

        customerIds.forEach(cId => {
            productIds.forEach(pId => {
                const cLabel = customersMap[cId] || 'Customer #' + cId;
                const pData = productsMap[pId] || { name: 'Product #' + pId, unit: 'units' };
                addRow(cId, cLabel, pId, pData.name, pData.unit, qty, bonus);
            });
        });

        // Optionally clear selections after adding
        // $('#bulk_customers').val(null).trigger('change');
        // $('#bulk_products').val(null).trigger('change');
    });

    // Ongoing toggle
    const ongoingToggle = document.getElementById('is_ongoing');
    const endDateInput = document.getElementById('end_date');
    const endDateHelp = document.getElementById('end_date_help');

    function syncOngoingState() {
        if (ongoingToggle.checked) {
            endDateInput.value = '';
            endDateInput.disabled = true;
            endDateHelp.innerHTML = '<span class="text-success fw-bold"><i class="ri-checkbox-circle-line me-1"></i> Continuous until stopped manually.</span>';
        } else {
            endDateInput.disabled = false;
            endDateHelp.textContent = 'Or toggle "Until Stopped" for open-ended.';
        }
    }
    ongoingToggle.addEventListener('change', syncOngoingState);

    document.getElementById('target_month').addEventListener('change', function () {
        const [year, month] = this.value.split('-').map(Number);
        if (!year) return;
        const fmt = d => `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
        document.getElementById('start_date').value = fmt(new Date(year, month - 1, 1));
        if (!ongoingToggle.checked) endDateInput.value = fmt(new Date(year, month, 0));
    });

    // Validate before submit
    document.getElementById('campaignForm').addEventListener('submit', function (e) {
        if (ongoingToggle.checked) { endDateInput.disabled = false; endDateInput.value = ''; }
        const rowCount = document.querySelectorAll('#targetsTableBody .item-row').length;
        if (rowCount === 0) {
            e.preventDefault();
            alert('Please add at least one target row using the bulk panel above.');
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        // Init Select2 on bulk selects
        $('#bulk_customers').select2({
            placeholder: 'Select customers...',
            allowClear: true,
            width: '100%'
        });
        $('#bulk_products').select2({
            placeholder: 'Select products...',
            allowClear: true,
            width: '100%'
        });
        syncOngoingState();
    });
</script>
@endsection
