@extends('layouts.vertical', ['page_title' => 'Create Chart of Account', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                <h4 class="page-title">Create Account</h4>
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('coa.index') }}">COA</a></li>
                    <li class="breadcrumb-item active">Create</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('coa.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="name" class="form-label">Account Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="type" class="form-label">Account Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="asset">Asset</option>
                                <option value="liability">Liability</option>
                                <option value="equity">Equity</option>
                                <option value="income">Income</option>
                                <option value="expense">Expense</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="opening_balance" class="form-label">Opening Balance</label>
                            <input type="number" step="1" class="form-control" id="opening_balance" name="opening_balance" value="0.00" required>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_payment_method" name="is_payment_method" value="1">
                            <label class="form-check-label" for="is_payment_method">Is Payment Method (e.g. Cash, Bank)?</label>
                            <div class="form-text">Check this if you want to be able to select this account when making or receiving payments.</div>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Account</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

