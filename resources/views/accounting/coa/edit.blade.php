@extends('layouts.vertical', ['page_title' => 'Edit Chart of Account', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                <h4 class="page-title">Edit Account</h4>
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('coa.index') }}">COA</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('coa.update', $account->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label for="name" class="form-label">Account Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ $account->name }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="type" class="form-label">Account Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="asset" {{ $account->type == 'asset' ? 'selected' : '' }}>Asset</option>
                                <option value="liability" {{ $account->type == 'liability' ? 'selected' : '' }}>Liability</option>
                                <option value="equity" {{ $account->type == 'equity' ? 'selected' : '' }}>Equity</option>
                                <option value="income" {{ $account->type == 'income' ? 'selected' : '' }}>Income</option>
                                <option value="expense" {{ $account->type == 'expense' ? 'selected' : '' }}>Expense</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="opening_balance" class="form-label">Opening Balance</label>
                            <input type="number" step="1" class="form-control" id="opening_balance" name="opening_balance" value="{{ $account->opening_balance }}" required>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_payment_method" name="is_payment_method" value="1" {{ $account->is_payment_method ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_payment_method">Is Payment Method (e.g. Cash, Bank)?</label>
                            <div class="form-text">Check this if you want to be able to select this account when making or receiving payments.</div>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Account</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

