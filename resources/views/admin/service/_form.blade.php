@php
    $category = $service ?? null;
@endphp
<div class="col-md-6">
    <label class="form-label">Category Name</label>
    <input type="text" name="name" id="category-name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $category->name ?? '') }}" required>
    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="col-md-6">
    <label class="form-label">Slug</label>
    <input type="text" name="slug" id="category-slug" class="form-control" value="{{ old('slug', $category->slug ?? '') }}" placeholder="Auto-generated from name">
</div>
<div class="col-12">
    <label class="form-label">Short Description</label>
    <textarea name="short_description" class="form-control" rows="2" maxlength="1000">{{ old('short_description', $category->short_description ?? '') }}</textarea>
</div>
<div class="col-md-6">
    <label class="form-label">Meta Title</label>
    <input type="text" name="meta_title" class="form-control" value="{{ old('meta_title', $category->meta_title ?? '') }}" maxlength="255">
</div>
<div class="col-md-6">
    <label class="form-label">Display Order</label>
    <input type="number" name="display_order" class="form-control" min="0" value="{{ old('display_order', $category->display_order ?? 0) }}">
</div>
<div class="col-12">
    <label class="form-label">Meta Description</label>
    <textarea name="meta_description" class="form-control" rows="2" maxlength="500">{{ old('meta_description', $category->meta_description ?? '') }}</textarea>
</div>
<div class="col-md-6">
    <label class="form-label">Category Image</label>
    @if(!empty($category?->image))
        <div class="mb-2"><img src="{{ asset('storage/' . $category->image) }}" width="80" alt=""></div>
    @endif
    <input type="file" name="image" class="form-control" accept="image/*">
</div>
<div class="col-md-3">
    <label class="form-label">Icon Name</label>
    <input type="text" name="icon_name" class="form-control" value="{{ old('icon_name', $category->icon_name ?? '') }}" placeholder="e.g. lotus">
</div>
<div class="col-md-3">
    <label class="form-label">Tone</label>
    <input type="text" name="tone" class="form-control" value="{{ old('tone', $category->tone ?? '') }}" placeholder="mint / lilac">
</div>
<div class="col-md-6">
    <label class="form-label">Optional Icon File</label>
    <input type="file" name="icon_file" class="form-control" accept="image/*">
</div>
<div class="col-md-3">
    <label class="form-label">Status</label>
    <select name="status" class="form-select" required>
        <option value="active" @selected(old('status', $category->status ?? 'active') === 'active')>Active</option>
        <option value="inactive" @selected(old('status', $category->status ?? '') === 'inactive')>Inactive</option>
    </select>
</div>
<div class="col-md-3 d-flex align-items-end gap-3">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="show_on_homepage" value="1" id="show_on_homepage" @checked(old('show_on_homepage', $category->show_on_homepage ?? false))>
        <label class="form-check-label" for="show_on_homepage">Homepage</label>
    </div>
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="show_in_explore_tools" value="1" id="show_in_explore_tools" @checked(old('show_in_explore_tools', $category->show_in_explore_tools ?? true))>
        <label class="form-check-label" for="show_in_explore_tools">Explore Tools</label>
    </div>
</div>
<div class="col-12">
    <label class="form-label">Discovery Needles (comma-separated)</label>
    <input type="text" name="discovery_needles" class="form-control" value="{{ old('discovery_needles', isset($category) && is_array($category->discovery_needles) ? implode(', ', $category->discovery_needles) : '') }}" placeholder="mental, psycholog, therap">
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const nameInput = document.getElementById('category-name');
    const slugInput = document.getElementById('category-slug');
    if (!nameInput || !slugInput) return;
    nameInput.addEventListener('input', function () {
        if (!slugInput.value || slugInput.dataset.userEdited !== 'true') {
            slugInput.value = this.value.toLowerCase().replace(/[^a-z0-9\s-]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-').trim();
        }
    });
    slugInput.addEventListener('input', function () { this.dataset.userEdited = 'true'; });
});
</script>
