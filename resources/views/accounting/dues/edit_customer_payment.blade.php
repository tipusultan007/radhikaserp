@extends('layouts.vertical', ['page_title' => 'Edit Customer Payment', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                <h4 class="page-title">Edit Customer Payment</h4>
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('customer-dues.index') }}">Customer Dues</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title mb-3">Edit Payment ({{ $journal->journal_no }})</h4>
                    
                    <div class="alert alert-warning">
                        <strong>Warning:</strong> Updating a customer payment that has already settled invoices is highly complex and may cause invoice mismatch errors. It is recommended to <strong>Delete</strong> this payment entirely (which automatically un-pays the invoices) and record a new payment instead.
                    </div>

                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
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

                    <form action="{{ route('customer-dues.update', $journal->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label">Customer</label>
                            <select name="customer_id" class="form-select select2" data-toggle="select2" required>
                                <option value="">Select Customer...</option>
                                @foreach($customersList as $c)
                                    <option value="{{ $c->id }}" {{ $journal->reference_id == $c->id ? 'selected' : '' }}>
                                        {{ $c->name }} (Due: ${{ number_format($c->total_due, 0) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Total Payment Amount ($)</label>
                            <input type="number" step="1" name="amount" class="form-control" value="{{ $amount + $walletAmount }}" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <select name="payment_method" class="form-select">
                                <option value="">Default (Cash)</option>
                                @if(isset($paymentMethods))
                                    @foreach($paymentMethods as $method)
                                        <option value="{{ $method->id }}" {{ $paymentMethodId == $method->id ? 'selected' : '' }}>
                                            {{ $method->name }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date</label>
                            <input type="text" name="date" class="form-control flatpickr-date" value="{{ $journal->date->format('Y-m-d') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reference (Cheque/Txn ID)</label>
                            <input type="text" name="reference" class="form-control" value="{{ $reference }}">
                        </div>
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('customer-dues.index') }}" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary">Attempt Update</button>
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
@endsection

