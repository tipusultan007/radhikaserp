@extends('layouts.vertical', ['page_title' => 'Units Management', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('css')
@endsection

@section('content')
    <div class="container-fluid">
         <div class="row">
            <div class="col-12">
                <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                    <h4 class="page-title">Units Management</h4>
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Products</a></li>
                        <li class="breadcrumb-item active">Units</li>
                    </ol>
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-sm-4">
                                <button type="button" class="btn btn-danger mb-2" data-bs-toggle="modal" data-bs-target="#addUnitModal"><i class="ri-add-line me-1"></i> Add Unit</button>
                            </div>
                        </div>

                        <div class="table-responsive-sm">
                            <table class="table table-centered table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Name</th>
                                        <th>Short Name</th>
                                        <th>Multiplier</th>
                                        <th>Parent Unit</th>
                                        <th>Status</th>
                                        <th style="width: 125px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($units as $unit)
                                        <tr>
                                            <td><strong>{{ $unit->name }}</strong></td>
                                            <td><span class="badge bg-light text-dark">{{ $unit->short_name }}</span></td>
                                            <td>{{ $unit->multiplier }}</td>
                                            <td>
                                                @if($unit->parent)
                                                    {{ $unit->parent->name }} ({{ $unit->parent->short_name }})
                                                @else
                                                    <span class="text-muted">None (Base Unit)</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($unit->status)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-secondary">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-link text-reset fs-16 px-1 p-0 border-0" title="Edit"
                                                    data-bs-toggle="modal" data-bs-target="#editUnitModal-{{ $unit->id }}"> 
                                                    <i class="ri-settings-3-line"></i>
                                                </button>
                                                <form action="{{ route('units.destroy', $unit->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Are you sure you want to delete this unit?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-link text-reset fs-16 p-0 border-0" title="Delete">
                                                        <i class="ri-delete-bin-2-line"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>

                                        <!-- Edit Modal -->
                                        <div class="modal fade" id="editUnitModal-{{ $unit->id }}" tabindex="-1" aria-labelledby="editUnitModalLabel-{{ $unit->id }}" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="editUnitModalLabel-{{ $unit->id }}">Edit Unit</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form action="{{ route('units.update', $unit->id) }}" method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                                                <input type="text" name="name" class="form-control" value="{{ $unit->name }}" required placeholder="e.g. Kilogram">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Short Name <span class="text-danger">*</span></label>
                                                                <input type="text" name="short_name" class="form-control" value="{{ $unit->short_name }}" required placeholder="e.g. kg">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Parent Unit</label>
                                                                <select name="parent_id" class="form-select">
                                                                    <option value="">None (Base Unit)</option>
                                                                    @foreach($allUnits as $parent)
                                                                        @if($parent->id != $unit->id)
                                                                            <option value="{{ $parent->id }}" {{ $unit->parent_id == $parent->id ? 'selected' : '' }}>
                                                                                {{ $parent->name }} ({{ $parent->short_name }})
                                                                            </option>
                                                                        @endif
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Multiplier <span class="text-danger">*</span></label>
                                                                <input type="number" step="any" name="multiplier" class="form-control" value="{{ $unit->multiplier }}" required>
                                                                <small class="text-muted">How many of this unit makes 1 parent unit? (e.g. if parent is kg, multiplier for g is 0.001)</small>
                                                            </div>
                                                            <div class="mb-3 form-check">
                                                                <input type="checkbox" name="status" class="form-check-input" id="status-{{ $unit->id }}" value="1" {{ $unit->status ? 'checked' : '' }}>
                                                                <label class="form-check-label" for="status-{{ $unit->id }}">Active</label>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center">No units found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div> <!-- end table-responsive-->

                        <div class="mt-3">
                            {{ $units->links('pagination::bootstrap-5') }}
                        </div>
                    </div> <!-- end card body-->
                </div> <!-- end card -->
            </div><!-- end col-->
        </div>
        <!-- end row-->

    </div> <!-- container -->

    <!-- Add Unit Modal -->
    <div class="modal fade" id="addUnitModal" tabindex="-1" aria-labelledby="addUnitModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUnitModalLabel">Add New Unit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('units.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required placeholder="e.g. Kilogram">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Short Name <span class="text-danger">*</span></label>
                            <input type="text" name="short_name" class="form-control" required placeholder="e.g. kg">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Parent Unit</label>
                            <select name="parent_id" class="form-select">
                                <option value="">None (Base Unit)</option>
                                @foreach($allUnits as $parent)
                                    <option value="{{ $parent->id }}">{{ $parent->name }} ({{ $parent->short_name }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Multiplier <span class="text-danger">*</span></label>
                            <input type="number" step="any" name="multiplier" class="form-control" value="1" required>
                            <small class="text-muted">How many of this unit makes 1 parent unit? (e.g. if parent is kg, multiplier for g is 0.001)</small>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="status" class="form-check-input" id="status-add" value="1" checked>
                            <label class="form-check-label" for="status-add">Active</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Unit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
@endsection
