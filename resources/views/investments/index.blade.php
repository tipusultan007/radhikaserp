@extends('layouts.vertical')

@section('css')
    @vite(['node_modules/datatables.net-bs5/css/dataTables.bootstrap5.min.css', 'node_modules/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css', 'node_modules/datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css', 'node_modules/datatables.net-select-bs5/css/select.bootstrap5.min.css', 'node_modules/flatpickr/dist/flatpickr.min.css'])
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        <li class="breadcrumb-item active">Investments</li>
                    </ol>
                </div>
                <h4 class="page-title">Investments & Withdrawals</h4>
            </div>
        </div>
    </div>

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

    <div class="row">
        <!-- Add Transaction Form -->
        <div class="col-xl-4 col-lg-5">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title mb-3">Add Transaction</h4>

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('investments.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="text" name="date" class="form-control flatpickr-date" value="{{ old('date', date('Y-m-d')) }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Type <span class="text-danger">*</span></label>
                            <select name="type" class="form-select" required>
                                <option value="investment" {{ old('type') == 'investment' ? 'selected' : '' }}>Investment (Add Capital)</option>
                                <option value="withdraw" {{ old('type') == 'withdraw' ? 'selected' : '' }}>Withdrawal (Remove Capital)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Amount <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="amount" class="form-control" value="{{ old('amount') }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Payment Method / Account <span class="text-danger">*</span></label>
                            <select name="payment_method" class="form-select select2" required data-toggle="select2">
                                <option value="">Select Account</option>
                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}" {{ old('payment_method') == $account->id ? 'selected' : '' }}>{{ $account->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Investor Name</label>
                            <input type="text" name="investor_name" class="form-control" value="{{ old('investor_name') }}" placeholder="Optional">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Reference / Receipt No.</label>
                            <input type="text" name="reference" class="form-control" value="{{ old('reference') }}" placeholder="Optional">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Save Transaction</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Transactions List -->
        <div class="col-xl-8 col-lg-7">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title mb-3">Transactions List</h4>

                    <div class="card bg-light mb-3 shadow-none border">
                        <div class="card-body py-2 px-3">
                            <form action="{{ route('investments.index') }}" method="GET">
                                <div class="row gy-2 gx-2 align-items-end">
                                    <div class="col-md-3">
                                        <label for="start_date" class="form-label mb-1">Start Date</label>
                                        <input type="text" class="form-control flatpickr-date" id="start_date" name="start_date" value="{{ request('start_date') }}" placeholder="YYYY-MM-DD">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="end_date" class="form-label mb-1">End Date</label>
                                        <input type="text" class="form-control flatpickr-date" id="end_date" name="end_date" value="{{ request('end_date') }}" placeholder="YYYY-MM-DD">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="type" class="form-label mb-1">Type</label>
                                        <select class="form-select" id="type" name="type">
                                            <option value="">All Types</option>
                                            <option value="investment" {{ request('type') == 'investment' ? 'selected' : '' }}>Investment</option>
                                            <option value="withdraw" {{ request('type') == 'withdraw' ? 'selected' : '' }}>Withdrawal</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-primary w-100"><i class="mdi mdi-filter"></i> Filter</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-centered table-striped nowrap w-100">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Investor</th>
                                    <th>Account</th>
                                    <th>Amount</th>
                                    <th>Reference</th>
                                    <th style="width: 80px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($investments as $investment)
                                    <tr>
                                        <td>{{ $investment->date->format('Y-m-d') }}</td>
                                        <td>
                                            @if($investment->type === 'investment')
                                                <span class="badge bg-success">Investment</span>
                                            @else
                                                <span class="badge bg-danger">Withdraw</span>
                                            @endif
                                        </td>
                                        <td>{{ $investment->investor_name ?? 'N/A' }}</td>
                                        <td>{{ $investment->account->name ?? 'N/A' }}</td>
                                        <td>{{ number_format($investment->amount, 2) }}</td>
                                        <td>{{ $investment->reference ?? 'N/A' }}</td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="ri-settings-3-line"></i> Actions
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><a class="dropdown-item text-info" href="{{ route('investments.show', $investment->id) }}"><i class="ri-eye-line me-2"></i> View</a></li>
                                                    <li><a class="dropdown-item text-primary" href="{{ route('investments.edit', $investment->id) }}"><i class="ri-pencil-line me-2"></i> Edit</a></li>
                                                    <li>
                                                        <form id="delete-investment-{{ $investment->id }}" action="{{ route('investments.destroy', $investment->id) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="button" class="dropdown-item text-danger" onclick="confirmDeleteInvestment({{ $investment->id }})"><i class="ri-delete-bin-line me-2"></i> Delete</button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">No transactions found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        {{ $investments->links('pagination::bootstrap-5') }}
                    </div>

                </div> <!-- end card-body-->
            </div> <!-- end card-->
        </div> <!-- end col -->
    </div> <!-- end row -->
@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            flatpickr(".flatpickr-date", {
                dateFormat: "Y-m-d",
                allowInput: true
            });
        });

        function confirmDeleteInvestment(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "Are you sure you want to delete this transaction? All associated journals will be permanently deleted.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-investment-' + id).submit();
                }
            });
        }
    </script>
@endsection
