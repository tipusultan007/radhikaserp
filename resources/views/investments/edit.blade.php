@extends('layouts.vertical')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('investments.index') }}">Investments</a></li>
                        <li class="breadcrumb-item active">Edit Transaction</li>
                    </ol>
                </div>
                <h4 class="page-title">Edit Investment / Withdraw</h4>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('investments.update', $investment->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="text" name="date" class="form-control flatpickr-date" value="{{ old('date', $investment->date->format('Y-m-d')) }}" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Type <span class="text-danger">*</span></label>
                                <select name="type" class="form-select" required>
                                    <option value="investment" {{ old('type', $investment->type) == 'investment' ? 'selected' : '' }}>Investment (Add Capital)</option>
                                    <option value="withdraw" {{ old('type', $investment->type) == 'withdraw' ? 'selected' : '' }}>Withdrawal (Remove Capital)</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Amount <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="amount" class="form-control" value="{{ old('amount', $investment->amount) }}" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Payment Method / Account <span class="text-danger">*</span></label>
                                <select name="payment_method" class="form-select select2" required data-toggle="select2">
                                    <option value="">Select Account</option>
                                    @foreach($accounts as $account)
                                        <option value="{{ $account->id }}" {{ old('payment_method', $investment->payment_method) == $account->id ? 'selected' : '' }}>{{ $account->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Investor Name</label>
                                <input type="text" name="investor_name" class="form-control" value="{{ old('investor_name', $investment->investor_name) }}" placeholder="Optional">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Reference / Receipt No.</label>
                                <input type="text" name="reference" class="form-control" value="{{ old('reference', $investment->reference) }}" placeholder="Optional">
                            </div>

                            <div class="col-12 mb-3">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="3">{{ old('notes', $investment->notes) }}</textarea>
                            </div>
                        </div>

                        <div class="text-end">
                            <a href="{{ route('investments.index') }}" class="btn btn-light me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Transaction</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
