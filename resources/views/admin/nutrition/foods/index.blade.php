@extends('admin.layouts.layout')

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h1 class="page-title fw-medium fs-18 mb-2">Food Catalogue</h1>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item">Tools Management</li>
                        <li class="breadcrumb-item active">Nutrition Foods</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-primary" href="{{ route('admin.nutrition-imports.index') }}">Import batches</a>
                <a class="btn btn-primary" href="{{ route('admin.nutrition-foods.create') }}">Add food</a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card custom-card">
            <div class="card-header">
                <form class="row g-2 align-items-end" method="get" action="{{ route('admin.nutrition-foods.index') }}">
                    <div class="col-md-3">
                        <label class="form-label" for="food-q">Search</label>
                        <input id="food-q" class="form-control" type="search" name="q" value="{{ request('q') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="food-status">Verification</label>
                        <select id="food-status" class="form-select" name="status">
                            <option value="">All</option>
                            @foreach ($statuses as $key => $label)
                                <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="food-completeness">Completeness</label>
                        <select id="food-completeness" class="form-select" name="completeness">
                            <option value="">All</option>
                            @foreach ($completeness as $key => $label)
                                <option value="{{ $key }}" @selected(request('completeness') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="food-published">Published</label>
                        <select id="food-published" class="form-select" name="published">
                            <option value="">All</option>
                            <option value="1" @selected(request('published') === '1')>Yes</option>
                            <option value="0" @selected(request('published') === '0')>No</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100" type="submit">Filter</button>
                    </div>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Brand</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Completeness</th>
                                <th>Published</th>
                                <th>Calories</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($foods as $food)
                                <tr>
                                    <td>{{ $food->name }}</td>
                                    <td>{{ $food->brand ?: '—' }}</td>
                                    <td>{{ $food->category->name ?? '—' }}</td>
                                    <td>{{ $statuses[$food->verification_status] ?? $food->verification_status }}</td>
                                    <td>{{ $completeness[$food->nutrient_completeness] ?? ($food->nutrient_completeness ?: '—') }}</td>
                                    <td>{{ $food->is_published ? 'Yes' : 'No' }}</td>
                                    <td>{{ $food->calories_kcal === null ? 'NULL' : $food->calories_kcal }}</td>
                                    <td><a href="{{ route('admin.nutrition-foods.edit', $food) }}">Review</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center p-4">No foods yet. Place Excel sources in private storage and run the import command.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($foods->hasPages())
                <div class="card-footer">{{ $foods->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
