@extends('layouts.vertical', ['page_title' => 'Employee Salaries', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                <h4 class="page-title">Employee Salaries</h4>
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                    <li class="breadcrumb-item active">Salaries</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-centered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Employee</th>
                                    <th>Role</th>
                                    <th>Designation</th>
                                    <th>Basic Salary</th>
                                    <th>Last Payment Date</th>
                                    <th>Total Paid (All Time)</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $user)
                                <tr>
                                    <td>
                                        <strong>{{ $user->name }}</strong>
                                        <br><small class="text-muted">{{ $user->email }}</small>
                                    </td>
                                    <td>
                                        @foreach($user->roles as $role)
                                            <span class="badge bg-primary">{{ $role->name }}</span>
                                        @endforeach
                                    </td>
                                    <td>{{ $user->designation ?? 'N/A' }}</td>
                                    <td>${{ number_format($user->basic_salary ?? 0, 0) }}</td>
                                    <td>
                                        @if($user->salaryPayments->isNotEmpty())
                                            {{ $user->salaryPayments->first()->payment_date->format('M d, Y') }}
                                        @else
                                            <span class="text-muted">Never paid</span>
                                        @endif
                                    </td>
                                    <td class="text-success fw-bold">
                                        ${{ number_format($user->salaryPayments->sum('amount'), 0) }}
                                    </td>
                                    <td>
                                        <a href="{{ route('salary.show', $user->id) }}" class="btn btn-sm btn-info">
                                            <i class="ri-eye-line me-1"></i> Manage Salary
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="7" class="text-center py-4">No employees found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

