@extends('admin.layouts.layout')

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="my-4">
            <h1 class="page-title fw-medium fs-18 mb-2">Import batch #{{ $batch->id }}</h1>
            <p class="text-muted mb-1">Source key: {{ $batch->source_key }} · Actor: {{ $batch->actor }}</p>
            <p class="text-muted mb-1">Worksheet: {{ $batch->worksheet_name }} · Checksum: <code>{{ $batch->checksum_sha256 }}</code></p>
            <p class="text-muted">Filename recorded: {{ $batch->source_filename }} (filesystem path not exposed)</p>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-2"><div class="card custom-card"><div class="card-body"><strong>{{ $batch->total_rows }}</strong><div>Total</div></div></div></div>
            <div class="col-md-2"><div class="card custom-card"><div class="card-body"><strong>{{ $batch->imported_drafts }}</strong><div>Drafts</div></div></div></div>
            <div class="col-md-2"><div class="card custom-card"><div class="card-body"><strong>{{ $batch->rejected_rows }}</strong><div>Rejected</div></div></div></div>
            <div class="col-md-2"><div class="card custom-card"><div class="card-body"><strong>{{ $batch->duplicate_candidates }}</strong><div>Duplicates</div></div></div></div>
            <div class="col-md-2"><div class="card custom-card"><div class="card-body"><strong>{{ $batch->conflicts }}</strong><div>Conflicts</div></div></div></div>
            <div class="col-md-2"><div class="card custom-card"><div class="card-body"><strong>{{ $batch->skipped_existing }}</strong><div>Skipped</div></div></div></div>
        </div>

        <div class="card custom-card">
            <div class="card-header d-flex justify-content-between">
                <span>Row log</span>
                <a href="{{ route('admin.nutrition-foods.index', ['batch_id' => $batch->id]) }}">Foods from this batch</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Sheet row</th>
                                <th>Name</th>
                                <th>Status</th>
                                <th>Message</th>
                                <th>Food</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr>
                                    <td>{{ $row->worksheet_row }}</td>
                                    <td>{{ $row->name }}</td>
                                    <td>{{ $row->status }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($row->message, 120) }}</td>
                                    <td>
                                        @if ($row->nutrition_food_id)
                                            <a href="{{ route('admin.nutrition-foods.edit', $row->nutrition_food_id) }}">#{{ $row->nutrition_food_id }}</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center p-4">No row log entries.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($rows->hasPages())
                <div class="card-footer">{{ $rows->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
