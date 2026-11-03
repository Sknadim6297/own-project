@extends('admin.layouts.layout')

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="my-4 page-header-breadcrumb d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h1 class="page-title fw-medium fs-18 mb-2">{{ $food->exists ? 'Edit food' : 'Add food' }}</h1>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.nutrition-foods.index') }}">Nutrition Foods</a></li>
                        <li class="breadcrumb-item active">{{ $food->exists ? 'Edit' : 'Create' }}</li>
                    </ol>
                </nav>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="card custom-card mb-3">
            <div class="card-body">
                <form method="post" action="{{ route('admin.nutrition-foods.categories.store') }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-8">
                        <label class="form-label" for="new-category">Quick-add category</label>
                        <input id="new-category" class="form-control" type="text" name="name" maxlength="120" required>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-outline-primary w-100" type="submit">Save category</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-body">
                <form method="post" action="{{ $food->exists ? route('admin.nutrition-foods.update', $food) : route('admin.nutrition-foods.store') }}">
                    @csrf
                    @if ($food->exists) @method('PUT') @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="name">Name</label>
                            <input id="name" class="form-control" name="name" value="{{ old('name', $food->name) }}" required maxlength="180">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="brand">Brand</label>
                            <input id="brand" class="form-control" name="brand" value="{{ old('brand', $food->brand) }}" maxlength="120">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="variant">Variant</label>
                            <input id="variant" class="form-control" name="variant" value="{{ old('variant', $food->variant) }}" maxlength="120">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="nutrition_food_category_id">Category</label>
                            <select id="nutrition_food_category_id" class="form-select" name="nutrition_food_category_id">
                                <option value="">—</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected(old('nutrition_food_category_id', $food->nutrition_food_category_id) == $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="preparation_state">Preparation</label>
                            <select id="preparation_state" class="form-select" name="preparation_state" required>
                                @foreach ($states as $key => $label)
                                    <option value="{{ $key }}" @selected(old('preparation_state', $food->preparation_state) === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="reference_basis">Reference basis</label>
                            <select id="reference_basis" class="form-select" name="reference_basis" required>
                                @foreach ($bases as $key => $label)
                                    <option value="{{ $key }}" @selected(old('reference_basis', $food->reference_basis) === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        @foreach (['calories_kcal' => 'Calories', 'protein_g' => 'Protein g', 'carbs_g' => 'Carbs g', 'fat_g' => 'Fat g', 'fibre_g' => 'Fibre g', 'sugar_g' => 'Sugar g', 'sodium_mg' => 'Sodium mg', 'saturated_fat_g' => 'Saturated fat g'] as $field => $label)
                            <div class="col-md-4">
                                <label class="form-label" for="{{ $field }}">{{ $label }} (blank = NULL)</label>
                                <input id="{{ $field }}" class="form-control" type="number" step="0.0001" name="{{ $field }}" value="{{ old($field, $food->$field) }}">
                            </div>
                        @endforeach

                        <div class="col-md-6">
                            <label class="form-label" for="source_name">Source name</label>
                            <input id="source_name" class="form-control" name="source_name" value="{{ old('source_name', $food->source_name) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="source_url">Source URL</label>
                            <input id="source_url" class="form-control" name="source_url" value="{{ old('source_url', $food->source_url) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="verification_status">Verification</label>
                            <select id="verification_status" class="form-select" name="verification_status" required>
                                @foreach ($statuses as $key => $label)
                                    <option value="{{ $key }}" @selected(old('verification_status', $food->verification_status) === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_published" value="1" id="is_published" @checked(old('is_published', $food->is_published))>
                                <label class="form-check-label" for="is_published">Published in catalogue (verified only)</label>
                            </div>
                        </div>
                        @if ($food->exists && $food->isVerified())
                            <div class="col-md-4 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="allow_verified_edit" value="1" id="allow_verified_edit">
                                    <label class="form-check-label" for="allow_verified_edit">Allow editing verified nutrients</label>
                                </div>
                            </div>
                        @endif
                        <div class="col-12"><hr><h2 class="fs-16">Serving definition (optional)</h2></div>

                        @php $serving = $food->relationLoaded('servings') ? $food->servings->first() : null; @endphp
                        <div class="col-md-3">
                            <label class="form-label" for="serving_label">Label</label>
                            <input id="serving_label" class="form-control" name="serving_label" value="{{ old('serving_label', optional($serving)->label) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="serving_quantity">Quantity</label>
                            <input id="serving_quantity" class="form-control" type="number" step="0.0001" name="serving_quantity" value="{{ old('serving_quantity', optional($serving)->quantity) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="serving_unit">Unit</label>
                            <input id="serving_unit" class="form-control" name="serving_unit" value="{{ old('serving_unit', optional($serving)->unit) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="serving_grams">Grams equivalent</label>
                            <input id="serving_grams" class="form-control" type="number" step="0.0001" name="serving_grams" value="{{ old('serving_grams', optional($serving)->grams_equivalent) }}" placeholder="Leave blank if unknown">
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button class="btn btn-primary" type="submit">Save food</button>
                        <a class="btn btn-outline-secondary" href="{{ route('admin.nutrition-foods.index') }}">Back</a>
                    </div>
                </form>
            </div>
        </div>

        @if ($food->exists)
            <div class="card custom-card mt-3">
                <div class="card-header">Review actions</div>
                <div class="card-body">
                    <p class="small text-muted">Verification requires source basis notes. Publication requires verified status. Imported values are never auto-verified.</p>
                    <form method="post" action="{{ route('admin.nutrition-foods.review', $food) }}" class="row g-2">
                        @csrf
                        <div class="col-md-3">
                            <label class="form-label" for="review-action">Action</label>
                            <select id="review-action" class="form-select" name="action" required>
                                <option value="verify">Verify</option>
                                <option value="needs_verification">Needs verification</option>
                                <option value="reject">Reject</option>
                                <option value="publish">Publish</option>
                                <option value="unpublish">Unpublish</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="source_basis">Source basis</label>
                            <input id="source_basis" class="form-control" name="source_basis" placeholder="e.g. IFCT 2017 table review">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="review-notes">Notes</label>
                            <input id="review-notes" class="form-control" name="notes" placeholder="Review evidence summary">
                        </div>
                        <div class="col-md-4">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="publish_after_verify" value="1" id="publish_after_verify">
                                <label class="form-check-label" for="publish_after_verify">Also publish after verify</label>
                            </div>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button class="btn btn-success w-100" type="submit">Record review</button>
                        </div>
                    </form>

                    @if ($food->duplicateReviews->isNotEmpty())
                        <hr>
                        <h2 class="fs-16">Duplicate warnings</h2>
                        <ul>
                            @foreach ($food->duplicateReviews as $dup)
                                <li>{{ $dup->match_reason }} vs
                                    <a href="{{ route('admin.nutrition-foods.edit', $dup->candidate_food_id) }}">#{{ $dup->candidate_food_id }} {{ $dup->candidate->name ?? '' }}</a>
                                    ({{ $dup->decision }})
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($food->reviewEvents->isNotEmpty())
                        <hr>
                        <h2 class="fs-16">Audit history</h2>
                        <ul class="mb-0">
                            @foreach ($food->reviewEvents as $event)
                                <li>
                                    {{ $event->created_at }} · {{ $event->action }}
                                    · {{ $event->from_status }} → {{ $event->to_status }}
                                    @if ($event->source_basis) · {{ $event->source_basis }} @endif
                                    @if ($event->notes) — {{ $event->notes }} @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

