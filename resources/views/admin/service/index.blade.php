@extends('admin.layouts.layout')
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="my-4 page-header-breadcrumb d-flex align-items-center flex-wrap gap-2">
            <div>
                <h1 class="page-title fw-medium fs-18 mb-2">Categories</h1>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Catalogue</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Categories</li>
                    </ol>
                </nav>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card custom-card">
            <div class="card-header justify-content-between flex-wrap gap-2">
                <div class="card-title mb-0">All Categories</div>
                <div class="d-flex flex-wrap gap-2">
                    <form method="GET" class="d-flex flex-wrap gap-2">
                        <input type="search" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Search name or slug">
                        <select name="status" class="form-select form-select-sm">
                            <option value="">All statuses</option>
                            <option value="active" @selected(request('status') === 'active')>Active</option>
                            <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                        </select>
                        <button class="btn btn-sm btn-outline-primary">Filter</button>
                    </form>
                    <a href="{{ route('admin.service.create') }}" class="btn btn-sm btn-primary">
                        <i class="ri-add-line align-middle me-1"></i> Add Category
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table text-nowrap mb-0">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Category</th>
                                <th>Slug</th>
                                <th>Services</th>
                                <th>Tools</th>
                                <th>Professionals</th>
                                <th>Order</th>
                                <th>Visibility</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($services as $service)
                                <tr>
                                    <td>
                                        @if($service->image)
                                            <img src="{{ asset('storage/' . $service->image) }}" width="48" height="48" class="rounded" alt="">
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="fw-medium">{{ $service->name }}</td>
                                    <td class="text-muted">{{ $service->slug }}</td>
                                    <td>{{ $service->services_count }}</td>
                                    <td>{{ $service->tools_count }}</td>
                                    <td>{{ $service->professionals_count }}</td>
                                    <td>{{ $service->display_order }}</td>
                                    <td>
                                        @if($service->show_on_homepage)<span class="badge bg-primary-transparent">Home</span>@endif
                                        @if($service->show_in_explore_tools)<span class="badge bg-success-transparent">Tools</span>@endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $service->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                            {{ ucfirst($service->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.service.edit', $service->id) }}" class="btn btn-primary-light btn-icon btn-sm" title="Edit">
                                            <i class="ri-edit-line"></i>
                                        </a>
                                        <form action="{{ route('admin.service.destroy', $service->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Deactivate or delete this category? Categories with dependents are deactivated safely.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger-light btn-icon btn-sm" title="Delete / deactivate">
                                                <i class="ri-delete-bin-5-line"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-4 text-muted">No categories found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($services->hasPages())
                <div class="card-footer">{{ $services->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
