@php
    $count = $catalogueCount ?? 0;
@endphp
@if ($count < 1)
    <div class="yk-nut__empty yk-nut__empty--gate" role="status">
        <p class="yk-nut__empty-title">Food Catalogue Pending</p>
        <p class="yk-nut__empty-copy">
            No published foods are available yet, so meal logging is not fully operational.
            YuktiOne does not invent nutrition values. Approved workbooks must be imported and reviewed first.
        </p>
        @if (! empty($importWorkbooksMissing))
            <p class="yk-nut__empty-copy">
                Expected import files are not present under <code>storage/app/nutrition/imports/</code>.
            </p>
        @endif
    </div>
@endif
