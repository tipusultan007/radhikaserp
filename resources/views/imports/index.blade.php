@extends('layouts.vertical', ['page_title' => 'Import Shipments', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('css')
@endsection

@section('content')
    <div class="container-fluid">
         <div class="row">
            <div class="col-12">
                <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                    <h4 class="page-title">Import Shipments</h4>
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                        <li class="breadcrumb-item active">Imports</li>
                    </ol>
                </div>
            </div>
         </div>

         <div class="row">
             <div class="col-12">
                 <div class="card">
                     <div class="card-body">
                         <div class="row mb-2">
                             <div class="col-sm-4">
                                 <a href="{{ route('imports.create') }}" class="btn btn-danger rounded-pill mb-3"><i class="ri-add-line me-1"></i> New Import</a>
                             </div>
                         </div>

                         @if (session('success'))
                             <div class="alert alert-success">{{ session('success') }}</div>
                         @endif

                         <div class="table-responsive">
                             <table class="table table-centered table-striped dt-responsive nowrap w-100" id="imports-datatable">
                                 <thead>
                                     <tr>
                                         <th>Import No</th>
                                         <th>Date</th>
                                         <th>Supplier</th>
                                         <th>Warehouse</th>
                                         <th>Total Cost</th>
                                         <th style="width: 120px;">Action</th>
                                     </tr>
                                 </thead>
                                 <tbody>
                                     @foreach ($imports as $import)
                                         <tr>
                                             <td><b>{{ $import->import_no }}</b></td>
                                             <td>{{ $import->date->format('Y-m-d') }}</td>
                                             <td>{{ $import->supplier->name ?? 'N/A' }}</td>
                                             <td>{{ $import->warehouse->name ?? 'N/A' }}</td>
                                             <td>${{ number_format($import->total_cost, 0) }}</td>
                                             <td>
                                                 <a href="{{ route('imports.show', $import->id) }}" class="action-icon text-info" title="View"> <i class="ri-eye-fill"></i></a>
                                                 @can('manage imports')
                                                 <a href="{{ route('imports.edit', $import->id) }}" class="action-icon text-primary" title="Edit"> <i class="ri-edit-box-line"></i></a>
                                                 <form action="{{ route('imports.destroy', $import->id) }}" method="POST" class="d-inline">
                                                     @csrf
                                                     @method('DELETE')
                                                     <button type="submit" class="action-icon btn btn-link text-danger p-0" onclick="return confirm('Are you sure you want to delete this Import? This will reverse the supplier payable and remove stock from the warehouse. Action cannot be undone if stock is already consumed.')" title="Delete"> <i class="ri-delete-bin-line"></i></button>
                                                 </form>
                                                 @endcan
                                             </td>
                                         </tr>
                                     @endforeach
                                 </tbody>
                             </table>
                         </div>
                     </div>
                 </div>
             </div>
         </div>
    </div>
@endsection

