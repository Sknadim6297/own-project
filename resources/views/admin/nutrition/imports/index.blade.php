@extends('admin.layouts.layout')

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h1 class="page-title fw-medium fs-18 mb-2">Nutrition Import Batches</h1>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.nutrition-foods.index') }}">Nutrition Foods</a></li>
                        <li class="breadcrumb-item active">Imports</li>
                    </ol>
                </nav>
            </div>
            <a class="btn btn-outline-primary" href="{{ route('admin.nutrition-imports.duplicates') }}">Duplicate queue</a>
        </div>

        <div class="alert alert-info">
            Import via CLI only: <code>php artisan nutrition:import-foods --dry-run</code> then
            <code>php artisan nutrition:import-foods --source=all</code>. Source workbooks stay in private storage.
        </div>

        <div class="card custom-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Source</th>
                                <th>Status</th>
                                <th>Rows</th>
                                <th>Drafts</th>
                                <th>Rejected</th>
                                <th>Duplicates</th>
                                <th>Conflicts</th>
                                <th>Finished</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($batches as $batch)
                                <tr>
                                    <td>{{ $batch->id }}</td>
                                    <td>{{ $batch->source_key }} · {{ $batch->source_filename }}</td>
                                    <td>{{ $batch->status }}</td>
                                    <td>{{ $batch->total_rows }}</td>
                                    <td>{{ $batch->imported_drafts }}</td>
                                    <td>{{ $batch->rejected_rows }}</td>
                                    <td>{{ $batch->duplicate_candidates }}</td>
                                    <td>{{ $batch->conflicts }}</td>
                                    <td>{{ optional($batch->finished_at)->toDateTimeString() ?: '—' }}</td>
                                    <td><a href="{{ route('admin.nutrition-imports.show', $batch) }}">Details</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="10" class="text-center p-4">No import batches yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($batches->hasPages())
                <div class="card-footer">{{ $batches->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
