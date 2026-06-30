@extends('layouts.vertical', ['page_title' => 'Roles & Permissions', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('content')
<div class="container-fluid">

    <div class="row">
        <div class="col-12">
            <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                <h4 class="page-title">Roles &amp; Permissions</h4>
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">ERP</a></li>
                    <li class="breadcrumb-item active">Roles</li>
                </ol>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <!-- Create Role -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0"><i class="ri-shield-keyhole-line me-1"></i> Create New Role</h5></div>
                <div class="card-body">
                    <form action="{{ route('roles.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Role Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}" placeholder="e.g. Storekeeper" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Initial Permissions</label>
                            <div class="border rounded p-2" style="max-height:300px;overflow-y:auto;">
                                @foreach($permissions->groupBy(fn($p) => explode(' ', $p->name, 2)[1] ?? 'other') as $module => $perms)
                                    <p class="text-muted fw-semibold mb-1 mt-2 text-uppercase" style="font-size:11px;">{{ $module }}</p>
                                    @foreach($perms as $perm)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permissions[]"
                                               value="{{ $perm->name }}" id="np_{{ $perm->id }}">
                                        <label class="form-check-label small" for="np_{{ $perm->id }}">
                                            {{ $perm->name }}
                                        </label>
                                    </div>
                                    @endforeach
                                @endforeach
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ri-save-line me-1"></i> Create Role
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Roles List -->
        <div class="col-lg-8">
            @foreach($roles as $role)
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <span class="badge bg-primary me-2">{{ $role->name }}</span>
                        <small class="text-muted">{{ $role->permissions->count() }} permissions</small>
                    </h5>
                    <div>
                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse"
                                data-bs-target="#rolePerms{{ $role->id }}">
                            <i class="ri-edit-line"></i> Edit Permissions
                        </button>
                        @if($role->name !== 'Admin')
                        <form action="{{ route('roles.destroy', $role->id) }}" method="POST" class="d-inline-block"
                              onsubmit="return confirm('Delete role {{ $role->name }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger ms-1">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </form>
                        @endif
                    </div>
                </div>

                <!-- Assigned permissions summary -->
                <div class="card-body py-2">
                    @forelse($role->permissions as $perm)
                        <span class="badge bg-light text-dark border me-1 mb-1">{{ $perm->name }}</span>
                    @empty
                        <span class="text-muted small">No permissions assigned.</span>
                    @endforelse
                </div>

                <!-- Editable permission panel -->
                <div class="collapse" id="rolePerms{{ $role->id }}">
                    <div class="card-body border-top">
                        <form action="{{ route('roles.update', $role->id) }}" method="POST">
                            @csrf @method('PUT')
                            <div class="row">
                                @foreach($permissions->groupBy(fn($p) => explode(' ', $p->name, 2)[1] ?? 'other') as $module => $perms)
                                <div class="col-md-4 mb-3">
                                    <p class="text-muted fw-semibold mb-1 text-uppercase" style="font-size:11px;">{{ $module }}</p>
                                    @foreach($perms as $perm)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permissions[]"
                                               value="{{ $perm->name }}" id="rp_{{ $role->id }}_{{ $perm->id }}"
                                               {{ $role->hasPermissionTo($perm->name) ? 'checked' : '' }}>
                                        <label class="form-check-label small" for="rp_{{ $role->id }}_{{ $perm->id }}">
                                            {{ $perm->name }}
                                        </label>
                                    </div>
                                    @endforeach
                                </div>
                                @endforeach
                            </div>
                            <button type="submit" class="btn btn-success mt-2">
                                <i class="ri-save-line me-1"></i> Save Permissions for {{ $role->name }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
