@extends('layouts.vertical', ['page_title' => 'Chart of Accounts', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="page-title">Chart of Accounts (COA)</h4>
                    <a href="{{ route('coa.create') }}" class="btn btn-primary">Create Account</a>
                </div>
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                    <li class="breadcrumb-item active">COA</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger">{{ $errors->first() }}</div>
                    @endif
                    
                    <div class="row">
                        @foreach(['asset', 'liability', 'equity', 'income', 'expense'] as $type)
                        <div class="col-md-6 col-xl-4 mb-4">
                            <h4 class="header-title text-uppercase bg-light p-2">{{ $type }}</h4>
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless table-hover">
                                    <tbody>
                                        @if(isset($accounts[$type]))
                                            @foreach($accounts[$type] as $acc)
                                                <tr>
                                                    <td>
                                                        <i class="ri-arrow-right-s-line text-muted"></i> 
                                                        <a href="{{ route('coa.show', $acc->id) }}" class="text-body fw-semibold">{{ $acc->name }}</a>
                                                        @if($acc->is_payment_method)
                                                            <span class="badge bg-success ms-1" style="font-size: 0.65em;">Payment Method</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-end">
                                                        <a href="{{ route('coa.edit', $acc->id) }}" class="text-warning mx-1"><i class="ri-pencil-line"></i></a>
                                                        <form action="{{ route('coa.destroy', $acc->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this account?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-link p-0 text-danger m-0"><i class="ri-delete-bin-line"></i></button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr><td class="text-muted">No accounts found.</td></tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
