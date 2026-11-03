@extends('admin.layouts.layout')

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="my-4 d-flex justify-content-between align-items-center">
            <h1 class="page-title fw-medium fs-18 mb-0">Duplicate review</h1>
            <a class="btn btn-outline-secondary" href="{{ route('admin.nutrition-imports.index') }}">Back to batches</a>
        </div>
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <div class="alert alert-warning">Candidates are flagged for human decision. Automatic merges are never applied.</div>
        <div class="card custom-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Imported food</th>
                                <th>Candidate</th>
                                <th>Reason</th>
                                <th>Score</th>
                                <th>Decision</th>
                                <th>Act</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($duplicates as $dup)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.nutrition-foods.edit', $dup->nutrition_food_id) }}">{{ $dup->food->name ?? ('#'.$dup->nutrition_food_id) }}</a>
                                        <div class="small text-muted">{{ $dup->food->preparation_state ?? '' }}</div>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.nutrition-foods.edit', $dup->candidate_food_id) }}">{{ $dup->candidate->name ?? ('#'.$dup->candidate_food_id) }}</a>
                                        <div class="small text-muted">{{ $dup->candidate->preparation_state ?? '' }}</div>
                                    </td>
                                    <td>{{ $dup->match_reason }}</td>
                                    <td>{{ $dup->score }}</td>
                                    <td>{{ $dup->decision }}</td>
                                    <td>
                                        @if ($dup->decision === 'pending')
                                            <form method="post" action="{{ route('admin.nutrition-imports.duplicates.decide', $dup) }}" class="d-flex gap-1 flex-wrap">
                                                @csrf
                                                <select name="decision" class="form-select form-select-sm" required>
                                                    <option value="retain_separate">Retain separately</option>
                                                    <option value="reject_import">Reject import</option>
                                                    <option value="merge_later">Mark for later merge</option>
                                                </select>
                                                <input class="form-control form-control-sm" name="notes" placeholder="Notes">
                                                <button class="btn btn-sm btn-primary" type="submit">Save</button>
                                            </form>
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center p-4">No duplicate candidates.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($duplicates->hasPages())
                <div class="card-footer">{{ $duplicates->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
