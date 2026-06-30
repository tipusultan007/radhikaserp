@extends('layouts.vertical', ['page_title' => 'User Details', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                <h4 class="page-title">User Details</h4>
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
                    <li class="breadcrumb-item active">{{ $user->name }}</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-4 col-lg-5">
            <div class="card text-center">
                <div class="card-body">
                    @if($user->hasMedia('photo'))
                        <img src="{{ $user->getFirstMediaUrl('photo') }}" class="rounded-circle avatar-lg img-thumbnail" alt="profile-image">
                    @else
                        <img src="{{ asset('assets/images/users/avatar-1.jpg') }}" class="rounded-circle avatar-lg img-thumbnail" alt="profile-image">
                    @endif

                    <h4 class="mb-0 mt-2">{{ $user->name }}</h4>
                    <p class="text-muted font-14">{{ $user->roles->first()->name ?? 'No Role' }}</p>

                    <div class="text-start mt-3">
                        <h4 class="font-13 text-uppercase">About Me :</h4>
                        <p class="text-muted font-13 mb-3">
                            {{ $user->notes ?? 'No additional notes provided.' }}
                        </p>
                        <p class="text-muted mb-2 font-13"><strong>Full Name :</strong> <span class="ms-2">{{ $user->name }}</span></p>
                        <p class="text-muted mb-2 font-13"><strong>Mobile :</strong><span class="ms-2">{{ $user->phone ?? 'N/A' }}</span></p>
                        <p class="text-muted mb-2 font-13"><strong>Email :</strong> <span class="ms-2">{{ $user->email }}</span></p>
                        <p class="text-muted mb-2 font-13"><strong>Location :</strong> <span class="ms-2">{{ $user->address ?? 'N/A' }}</span></p>
                        <p class="text-muted mb-1 font-13"><strong>NID :</strong> <span class="ms-2">{{ $user->nid ?? 'N/A' }}</span></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-8 col-lg-7">
            <div class="card">
                <div class="card-body">
                    <ul class="nav nav-pills bg-nav-pills nav-justified mb-3">
                        <li class="nav-item">
                            <a href="#salary-info" data-bs-toggle="tab" aria-expanded="true" class="nav-link rounded-0 active">
                                Salary & Employment
                            </a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane show active" id="salary-info">
                            <h5 class="mb-3 mt-4 text-uppercase"><i class="ri-briefcase-line me-1"></i> Employment Info</h5>
                            <div class="table-responsive">
                                <table class="table table-borderless table-sm mb-0">
                                    <tbody>
                                        <tr>
                                            <th scope="row" style="width: 25%;">Designation:</th>
                                            <td>{{ $user->designation ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Join Date:</th>
                                            <td>{{ $user->join_date ? $user->join_date->format('M d, Y') : 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Basic Salary:</th>
                                            <td class="fw-bold">${{ number_format($user->basic_salary, 0) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <h5 class="mb-3 mt-4 text-uppercase d-flex justify-content-between align-items-center">
                                <span><i class="ri-money-dollar-circle-line me-1"></i> Payment History</span>
                                <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#newPaymentModal">
                                    <i class="ri-add-line me-1"></i> Make Payment
                                </button>
                            </h5>
                            <div class="table-responsive">
                                <table class="table table-hover table-centered table-sm mb-0">
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
                                        <tr><td colspan="6" class="text-center py-4 text-muted">No payments recorded yet.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3">
                                {{ $payments->links('pagination::bootstrap-5') }}
                            </div>
                        </div>
                    </div>
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
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

