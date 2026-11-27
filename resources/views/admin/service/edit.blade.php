@extends('admin.layouts.layout')
@section('content')
<div class="main-content app-content">
    <div class="container mt-4 mb-5">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h4 class="mb-0">Edit Category</h4>
            <div class="text-muted small">
                Services: {{ $service->sub_services_count }} ·
                Tools: {{ $service->category_tools_count }} ·
                Professionals: {{ $service->professional_services_count }}
            </div>
        </div>

        @if($errors->any())
            <div class="alert alert-danger mt-3">
                <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form action="{{ route('admin.service.update', $service->id) }}" method="POST" enctype="multipart/form-data" class="card mt-3">
            @csrf
            @method('PUT')
            <input type="hidden" name="tools_sync" value="1">
            <div class="card-body row gy-3">
                @include('admin.service._form', ['service' => $service])

                <div class="col-12">
                    <hr>
                    <h6 class="mb-3">Assigned Tools</h6>
                    <p class="text-muted small">Select existing tools to show under this category in Explore Tools. Tool URLs and implementations stay unchanged.</p>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Assign</th>
                                    <th>Tool</th>
                                    <th>Slug</th>
                                    <th>Order</th>
                                    <th>Visible</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tools as $tool)
                                    @php
                                        $pivot = $service->categoryTools->firstWhere('tool_id', $tool->id);
                                        $checked = old('tool_ids') ? in_array($tool->id, old('tool_ids', [])) : (bool) $pivot;
                                        $order = old('tool_order.'.$tool->id, $pivot->display_order ?? 0);
                                        $visible = old('tool_visible.'.$tool->id, $pivot?->is_visible ?? true);
                                    @endphp
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="tool_ids[]" value="{{ $tool->id }}" @checked($checked)>
                                        </td>
                                        <td>{{ $tool->name }}</td>
                                        <td class="text-muted">{{ $tool->slug }}</td>
                                        <td style="max-width:90px">
                                            <input type="number" name="tool_order[{{ $tool->id }}]" value="{{ $order }}" class="form-control form-control-sm" min="0">
                                        </td>
                                        <td>
                                            <input type="checkbox" name="tool_visible[{{ $tool->id }}]" value="1" @checked($visible)>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between">
                <a href="{{ route('admin.service.index') }}" class="btn btn-secondary">Back</a>
                <button type="submit" class="btn btn-primary">Update Category</button>
            </div>
        </form>
    </div>
</div>
@endsection
