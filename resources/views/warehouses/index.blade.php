@extends('layouts.vertical', ['page_title' => 'Warehouses', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('css')
@endsection

@section('content')
    <!-- Start Content-->
    <div class="container-fluid">

         <!-- start page title -->
         <div class="row">
            <div class="col-12">
                <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                    <h4 class="page-title">Warehouses</h4>
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                        <li class="breadcrumb-item active">Warehouses</li>
                    </ol>
                </div>
            </div>
        </div>
        <!-- end page title -->

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-sm-4">
                                <a href="{{ route('warehouses.create') }}" class="btn btn-danger mb-2"><i class="ri-add-line me-1"></i> Add Warehouse</a>
                            </div>
                        </div>

                        <div class="table-responsive-sm">
                            <table class="table table-centered table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Name</th>
                                        <th>Code</th>
                                        <th>Address</th>
                                        <th>Manager</th>
                                        <th>Status</th>
                                        <th style="width: 125px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($warehouses as $warehouse)
                                        <tr>
                                            <td><strong>{{ $warehouse->name }}</strong></td>
                                            <td><span class="badge bg-light text-dark">{{ $warehouse->code }}</span></td>
                                            <td>{{ $warehouse->address ?? 'N/A' }}</td>
                                            <td>{{ $warehouse->manager->name ?? 'Unassigned' }}</td>
                                            <td>
                                                @if ($warehouse->status)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-secondary">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('warehouses.edit', $warehouse->id) }}" class="text-reset fs-16 px-1" title="Edit"> 
                                                    <i class="ri-settings-3-line"></i>
                                                </a>
                                                <form action="{{ route('warehouses.destroy', $warehouse->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Are you sure you want to delete this warehouse?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-link text-reset fs-16 p-0 border-0" title="Delete">
                                                        <i class="ri-delete-bin-2-line"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center">No warehouses found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div> <!-- end table-responsive-->
                    </div> <!-- end card body-->
                </div> <!-- end card -->
            </div><!-- end col-->
        </div>
        <!-- end row-->

    </div> <!-- container -->
@endsection

@section('script')
@endsection
