@php
    $selectedIcon = old('icon_name', $categoryBox?->icon_name);
    $currentIconPath = $categoryBox?->icon_path;
    $pickerId = $pickerId ?? 'category-icon-picker';
@endphp

<div class="col-xl-12 category-icon-picker" data-category-icon-picker>
    <label class="form-label" for="{{ $pickerId }}-search">Category Icon</label>
    <input type="hidden" name="icon_name" value="{{ $selectedIcon }}" data-icon-value>
    <input type="hidden" name="remove_icon" value="0" data-remove-icon-value>

    <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
        <div class="border rounded p-2 text-center" style="min-width: 76px;">
            <span class="text-muted small d-block">Preview</span>
            <span class="category-icon-preview fs-3" data-icon-preview>
                @if($currentIconPath)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($currentIconPath) }}" alt="Current category icon" style="width: 38px; height: 38px; object-fit: contain;">
                @elseif($selectedIcon)
                    <i class="{{ $selectedIcon }}" aria-hidden="true"></i>
                @else
                    <i class="ri-shapes-line" aria-hidden="true"></i>
                @endif
            </span>
        </div>

        @if($currentIconPath)
            <button type="button" class="btn btn-sm btn-outline-danger" data-remove-category-icon>
                Remove Icon
            </button>
        @endif
        <span class="text-muted small" data-icon-upload-status>
            @if($currentIconPath) Current uploaded icon @else Choose a library icon or upload a raster image. @endif
        </span>
    </div>

    <label class="form-label" for="{{ $pickerId }}-upload">Upload or Replace Custom Icon (PNG, JPG, WebP; max 2 MB)</label>
    <input class="form-control mb-3" id="{{ $pickerId }}-upload" type="file" name="category_icon" accept="image/png,image/jpeg,image/webp" data-category-icon-file>
    @error('category_icon')
        <div class="text-danger mb-2">{{ $message }}</div>
    @enderror

    <label class="form-label" for="{{ $pickerId }}-search">Search icon library</label>
    <input class="form-control mb-2" id="{{ $pickerId }}-search" type="search" placeholder="Search service icons..." autocomplete="off" data-icon-search>
    <div class="category-icon-options" role="group" aria-label="Available category icons" data-icon-options>
        @foreach($iconOptions as $iconOption)
            <button type="button"
                    class="btn btn-sm btn-light border category-icon-option {{ $selectedIcon === $iconOption ? 'is-selected' : '' }}"
                    data-icon-option="{{ $iconOption }}"
                    data-icon-search-text="{{ str_replace(['ri-', '-line'], ['', ''], $iconOption) }}"
                    aria-pressed="{{ $selectedIcon === $iconOption ? 'true' : 'false' }}">
                <i class="{{ $iconOption }}" aria-hidden="true"></i>
                <span>{{ \Illuminate\Support\Str::of($iconOption)->after('ri-')->beforeLast('-line')->replace('-', ' ')->title() }}</span>
            </button>
        @endforeach
    </div>
    @error('icon_name')
        <div class="text-danger mt-2">{{ $message }}</div>
    @enderror
</div>

<style>
    .category-icon-options {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(145px, 1fr));
        gap: .5rem;
        max-height: 210px;
        overflow: auto;
        padding: .25rem;
    }

    .category-icon-option {
        display: flex;
        align-items: center;
        gap: .55rem;
        text-align: left;
    }

    .category-icon-option i {
        font-size: 1.15rem;
    }

    .category-icon-option.is-selected {
        border-color: #168a80 !important;
        background: #eaf8f5;
        color: #8a0f4d;
    }
</style>

<script>
    document.querySelectorAll('[data-category-icon-picker]').forEach(function (picker) {
        const iconValue = picker.querySelector('[data-icon-value]');
        const preview = picker.querySelector('[data-icon-preview]');
        const search = picker.querySelector('[data-icon-search]');
        const fileInput = picker.querySelector('[data-category-icon-file]');
        const removeValue = picker.querySelector('[data-remove-icon-value]');
        const removeButton = picker.querySelector('[data-remove-category-icon]');
        const status = picker.querySelector('[data-icon-upload-status]');
        const options = Array.from(picker.querySelectorAll('[data-icon-option]'));

        function renderIconPreview(iconClass) {
            preview.replaceChildren();
            const icon = document.createElement('i');
            icon.className = iconClass || 'ri-shapes-line';
            icon.setAttribute('aria-hidden', 'true');
            preview.appendChild(icon);
        }

        options.forEach(function (option) {
            option.addEventListener('click', function () {
                iconValue.value = option.dataset.iconOption;
                removeValue.value = '0';
                options.forEach(function (item) {
                    const selected = item === option;
                    item.classList.toggle('is-selected', selected);
                    item.setAttribute('aria-pressed', selected ? 'true' : 'false');
                });
                if (fileInput) fileInput.value = '';
                renderIconPreview(iconValue.value);
                if (status) status.textContent = 'Selected icon from the library';
            });
        });

        search.addEventListener('input', function () {
            const query = search.value.trim().toLowerCase();
            options.forEach(function (option) {
                option.hidden = !option.dataset.iconSearchText.includes(query);
            });
        });

        if (fileInput) {
            fileInput.addEventListener('change', function () {
                const file = fileInput.files && fileInput.files[0];
                if (!file) return;
                if (!['image/png', 'image/jpeg', 'image/webp'].includes(file.type)) {
                    fileInput.value = '';
                    if (status) status.textContent = 'Choose a PNG, JPG, or WebP image.';
                    return;
                }
                const reader = new FileReader();
                reader.addEventListener('load', function () {
                    preview.replaceChildren();
                    const image = document.createElement('img');
                    image.src = reader.result;
                    image.alt = 'Selected category icon preview';
                    image.style.cssText = 'width:38px;height:38px;object-fit:contain;';
                    preview.appendChild(image);
                    removeValue.value = '0';
                    if (status) status.textContent = file.name;
                });
                reader.readAsDataURL(file);
            });
        }

        if (removeButton) {
            removeButton.addEventListener('click', function () {
                removeValue.value = '1';
                iconValue.value = '';
                if (fileInput) fileInput.value = '';
                options.forEach(function (option) {
                    option.classList.remove('is-selected');
                    option.setAttribute('aria-pressed', 'false');
                });
                renderIconPreview('');
                if (status) status.textContent = 'Icon will be removed when saved';
            });
        }
    });
</script>
