@extends('layouts.vertical', ['page_title' => 'Employee Details', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                <h4 class="page-title">Employee Salary Details</h4>
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('salary.index') }}">Salaries</a></li>
                    <li class="breadcrumb-item active">{{ $user->name }}</li>
                </ol>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <!-- Profile Column -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Salary Profile</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <h4 class="mb-0">{{ $user->name }}</h4>
                        <p class="text-muted">{{ $user->email }}</p>
                    </div>

                    <form action="{{ route('users.update', $user->id) }}" method="POST">
                        @csrf @method('PUT')
                        <input type="hidden" name="name" value="{{ $user->name }}">
                        <input type="hidden" name="email" value="{{ $user->email }}">
                        <input type="hidden" name="role" value="{{ $user->roles->first()->name ?? '' }}">
                        
                        <div class="mb-3">
                            <label class="form-label">Designation</label>
                            <input type="text" name="designation" class="form-control" value="{{ $user->designation }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Basic Salary</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="1" name="basic_salary" class="form-control" value="{{ $user->basic_salary }}">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Join Date</label>
                            <input type="date" name="join_date" class="form-control" value="{{ $user->join_date?->format('Y-m-d') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2">{{ $user->notes }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Update Profile</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Payments Column -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Payment History</h5>
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#newPaymentModal">
                        <i class="ri-add-line me-1"></i> Make Payment
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-centered mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Month</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Ref Journal</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($payments as $payment)
                                <tr>
                                    <td>{{ $payment->payment_date->format('M d, Y') }}</td>
                                    <td><strong>{{ \Carbon\Carbon::createFromFormat('Y-m', $payment->payment_month)->format('M Y') }}</strong></td>
                                    <td>
                                        <span class="badge bg-{{ $payment->payment_type_badge }}">
                                            {{ $payment->payment_type_label }}
                                        </span>
                                    </td>
                                    <td class="fw-bold">${{ number_format($payment->amount, 0) }}</td>
                                    <td>
                                        @if($payment->journal)
                                            <a href="{{ route('journals.show', $payment->journal_id) }}">
                                                {{ $payment->journal->journal_no }}
                                            </a>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editPaymentModal{{ $payment->id }}">
                                            <i class="ri-edit-line"></i>
                                        </button>
                                        <form action="{{ route('salary.payments.destroy', $payment->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Delete this payment and its journal entry?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="ri-delete-bin-line"></i></button>
                                        </form>
                                    </td>
                                </tr>

                                <!-- Edit Payment Modal -->
                                <div class="modal fade" id="editPaymentModal{{ $payment->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="{{ route('salary.payments.update', $payment->id) }}" method="POST">
                                                @csrf @method('PUT')
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Payment</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">Payment Type</label>
                                                            <select name="payment_type" class="form-select" required>
                                                                <option value="full" {{ $payment->payment_type=='full'?'selected':'' }}>Full Salary</option>
                                                                <option value="partial" {{ $payment->payment_type=='partial'?'selected':'' }}>Partial Payment</option>
                                                                <option value="advance" {{ $payment->payment_type=='advance'?'selected':'' }}>Salary Advance</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">For Month (YYYY-MM)</label>
                                                            <input type="month" name="payment_month" class="form-control" value="{{ $payment->payment_month }}" required>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">Amount</label>
                                                            <div class="input-group">
                                                                <span class="input-group-text">$</span>
                                                                <input type="number" step="1" name="amount" class="form-control" value="{{ $payment->amount }}" required>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">Payment Date</label>
                                                            <input type="date" name="payment_date" class="form-control" value="{{ $payment->payment_date->format('Y-m-d') }}" required>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Paid From Account</label>
                                                        <select name="payment_method_id" class="form-select" required>
                                                            @foreach($paymentMethods as $acc)
                                                                <option value="{{ $acc->id }}" {{ $payment->payment_method_id == $acc->id ? 'selected' : '' }}>
                                                                    {{ $acc->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-primary">Update Payment</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @empty
                                <tr><td colspan="6" class="text-center py-4">No payments recorded yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-light">
                    {{ $payments->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Payment Modal -->
<div class="modal fade" id="newPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('salary.payments.store', $user->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Record Salary Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Type</label>
                            <select name="payment_type" class="form-select" required>
                                <option value="full">Full Salary</option>
                                <option value="partial">Partial Payment</option>
                                <option value="advance">Salary Advance</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">For Month (YYYY-MM)</label>
                            <input type="month" name="payment_month" class="form-control" value="{{ date('Y-m') }}" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="1" name="amount" class="form-control" value="{{ $user->basic_salary }}" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Date</label>
                            <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Paid From Account</label>
                        <select name="payment_method_id" class="form-select" required>
                            @foreach($paymentMethods as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="alert alert-info py-2 mb-0">
                        <i class="ri-information-line me-1"></i> A journal entry will be created automatically.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

