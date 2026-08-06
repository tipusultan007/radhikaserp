@extends('layouts.vertical', ['page_title' => 'Balance Transfers', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                <h4 class="page-title">Balance Transfers</h4>
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                    <li class="breadcrumb-item">Accounting</li>
                    <li class="breadcrumb-item active">Balance Transfers</li>
                </ol>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <!-- New Transfer Form -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">New Transfer</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('balance-transfers.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="date" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">From Account <span class="text-danger">*</span></label>
                            <select class="form-select select2" name="from_account_id" required>
                                <option value="">Select Account</option>
                                @foreach($paymentMethods as $account)
                                    <option value="{{ $account->id }}" {{ old('from_account_id') == $account->id ? 'selected' : '' }}>{{ $account->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">To Account <span class="text-danger">*</span></label>
                            <select class="form-select select2" name="to_account_id" required>
                                <option value="">Select Account</option>
                                @foreach($paymentMethods as $account)
                                    <option value="{{ $account->id }}" {{ old('to_account_id') == $account->id ? 'selected' : '' }}>{{ $account->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" name="amount" value="{{ old('amount') }}" required min="0.01">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reference</label>
                            <input type="text" class="form-control" name="reference" value="{{ old('reference') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="2">{{ old('notes') }}</textarea>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i> Submit Transfer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Transfer List -->
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-body">
                    <form action="{{ route('balance-transfers.index') }}" method="GET">
                        <div class="row gy-2 gx-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label mb-1">Start Date</label>
                                <input type="date" class="form-control" name="start_date" value="{{ request('start_date') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label mb-1">End Date</label>
                                <input type="date" class="form-control" name="end_date" value="{{ request('end_date') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label mb-1">From Account</label>
                                <select class="form-select select2" name="from_account_id">
                                    <option value="">All</option>
                                    @foreach($paymentMethods as $account)
                                        <option value="{{ $account->id }}" {{ request('from_account_id') == $account->id ? 'selected' : '' }}>{{ $account->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label mb-1">To Account</label>
                                <select class="form-select select2" name="to_account_id">
                                    <option value="">All</option>
                                    @foreach($paymentMethods as $account)
                                        <option value="{{ $account->id }}" {{ request('to_account_id') == $account->id ? 'selected' : '' }}>{{ $account->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12 mt-2 text-end">
                                <button type="submit" class="btn btn-info"><i class="ri-search-line me-1"></i> Filter</button>
                                <a href="{{ route('balance-transfers.index') }}" class="btn btn-light ms-1">Clear</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Transfer No</th>
                                    <th>From Account</th>
                                    <th>To Account</th>
                                    <th class="text-end">Amount</th>
                                    <th>Ref / Notes</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transfers as $transfer)
                                    <tr>
                                        <td>{{ $transfer->date->format('d M, Y') }}</td>
                                        <td>{{ $transfer->transfer_no }}</td>
                                        <td><span class="badge bg-danger">{{ $transfer->fromAccount->name ?? 'N/A' }}</span></td>
                                        <td><span class="badge bg-success">{{ $transfer->toAccount->name ?? 'N/A' }}</span></td>
                                        <td class="text-end fw-bold">{{ number_format($transfer->amount, 2) }}</td>
                                        <td>
                                            <small>
                                                @if($transfer->reference) <strong>Ref:</strong> {{ $transfer->reference }} <br> @endif
                                                @if($transfer->notes) <span class="text-muted">{{ Str::limit($transfer->notes, 30) }}</span> @endif
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <a href="javascript:void(0);" class="btn btn-sm btn-soft-primary" onclick="editTransfer({{ $transfer }})"><i class="ri-edit-line"></i></a>
                                            <button type="button" class="btn btn-sm btn-soft-danger"
                                                onclick="confirmDelete('{{ route('balance-transfers.destroy', $transfer->id) }}')"
                                                title="Delete">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">No transfers found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($transfers->hasPages())
                    <div class="card-footer">
                        {{ $transfers->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Global Delete Form -->
<form id="_globalDeleteForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<!-- Edit Transfer Modal -->
<div class="modal fade" id="editTransferModal" tabindex="-1" aria-labelledby="editTransferModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editTransferModalLabel">Edit Balance Transfer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editTransferForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="date" id="edit_date" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">From Account <span class="text-danger">*</span></label>
                        <select class="form-select" name="from_account_id" id="edit_from_account_id" required>
                            <option value="">Select Account</option>
                            @foreach($paymentMethods as $account)
                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">To Account <span class="text-danger">*</span></label>
                        <select class="form-select" name="to_account_id" id="edit_to_account_id" required>
                            <option value="">Select Account</option>
                            @foreach($paymentMethods as $account)
                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control" name="amount" id="edit_amount" required min="0.01">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reference</label>
                        <input type="text" class="form-control" name="reference" id="edit_reference">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" id="edit_notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Transfer</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // ── SweetAlert Delete ─────────────────────────────────────────────────────
    function confirmDelete(actionUrl) {
        Swal.fire({
            title: 'Delete Transfer?',
            text: 'This will reverse the accounting entries. This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then(function(result) {
            if (result.isConfirmed) {
                var form = document.getElementById('_globalDeleteForm');
                form.action = actionUrl;
                form.submit();
            }
        });
    }

    // ── Edit Transfer ─────────────────────────────────────────────────────────
    function editTransfer(transfer) {
        let url = "{{ route('balance-transfers.update', ':id') }}";
        url = url.replace(':id', transfer.id);
        $('#editTransferForm').attr('action', url);
        
        let dateObj = new Date(transfer.date);
        let dateString = dateObj.toISOString().split('T')[0];
        $('#edit_date').val(dateString);
        
        $('#edit_from_account_id').val(transfer.from_account_id);
        $('#edit_to_account_id').val(transfer.to_account_id);
        $('#edit_amount').val(transfer.amount);
        $('#edit_reference').val(transfer.reference);
        $('#edit_notes').val(transfer.notes);
        
        var editModal = new bootstrap.Modal(document.getElementById('editTransferModal'));
        editModal.show();
    }
</script>
@endsection
