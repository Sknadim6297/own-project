@extends('layouts.layout')

@section('title', 'Log Weight | YuktiOne')
@section('meta_description', 'Add or edit weight measurements.')
@section('body_class', 'yk-wj-body')

@section('content')
@php $svc = app(\App\Services\WeightJourneyService::class); @endphp
<main class="yk-wj">
    <div class="yk-wj__wrap yk-wj__wrap--wide">
        <section class="yk-wj__card">
            <p class="yk-wj__badge">Log · Asia/Kolkata</p>
            <h1>Log weight</h1>
            <p class="yk-wj__lead">Multiple same-day readings are allowed. Stored precisely in kilograms.</p>

            @if (session('status'))
                <p class="yk-wj__ok" role="status">{{ session('status') }}</p>
            @endif
            @if ($errors->any())
                <p class="yk-wj__error" role="alert">{{ $errors->first() }}</p>
            @endif

            <form method="post" action="{{ route('tools.weight-journey.measurements.store') }}" class="yk-wj__log-form" data-wj-submit-once>
                @csrf
                <div class="yk-wj__log-grid">
                    <fieldset class="yk-wj__field">
                        <legend>Unit</legend>
                        <div class="yk-wj__units" role="radiogroup" aria-label="Weight unit">
                            <label><input type="radio" name="unit" value="kg" @checked(old('unit', $unit) === 'kg')> kg</label>
                            <label><input type="radio" name="unit" value="lb" @checked(old('unit', $unit) === 'lb')> lb</label>
                        </div>
                    </fieldset>
                    <div class="yk-wj__field">
                        <label for="weight">Weight</label>
                        <input id="weight" type="number" name="weight" step="0.001" min="0" inputmode="decimal" value="{{ old('weight') }}" required placeholder="e.g. 70.5">
                    </div>
                    <div class="yk-wj__field">
                        <label for="recorded">Date/time (optional)</label>
                        <input id="recorded" type="datetime-local" name="recorded_at" value="{{ old('recorded_at') }}">
                    </div>
                </div>
                <div class="yk-wj__field">
                    <label for="note">Optional note</label>
                    <textarea id="note" name="note" maxlength="240" placeholder="Context for this reading">{{ old('note') }}</textarea>
                </div>
                <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                <div class="yk-wj__actions" style="margin-top:0.35rem;">
                    <button class="yk-wj__btn" type="submit">Save measurement</button>
                    <a class="yk-wj__btn-secondary" href="{{ route('tools.weight-journey') }}">Dashboard</a>
                </div>
            </form>

            <h2 class="yk-wj__section-title" style="margin-top:1.35rem;">Recent measurements</h2>
            @if ($recent->isEmpty())
                <div class="yk-wj__empty" role="status">No measurements yet.</div>
            @else
                <ul class="yk-wj__list yk-wj__list--manage">
                    @foreach ($recent as $index => $row)
                        @php
                            $local = $row->recorded_at->timezone($timezone);
                            $older = $recent->get($index + 1);
                            $delta = $older
                                ? $svc->formatNetChange(
                                    bcsub((string) $row->weight_kg, (string) $older->weight_kg, 3),
                                    $unit
                                )
                                : null;
                        @endphp
                        <li>
                            <div>
                                <strong>{{ $svc->formatWeight((string) $row->weight_kg, $unit) }}</strong>
                                @if ($delta)
                                    <span class="yk-wj__delta">{{ $delta }}</span>
                                @endif
                                <div class="yk-wj__meta">{{ $local->format('d M Y, g:i A') }}</div>
                                @if ($row->note)
                                    <div class="yk-wj__meta">{{ $row->note }}</div>
                                @endif
                            </div>
                            <div class="yk-wj__entry-actions">
                                <details>
                                    <summary>Edit</summary>
                                    <form method="post" action="{{ route('tools.weight-journey.measurements.update', $row) }}" class="yk-wj__edit-form" data-wj-submit-once>
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="unit" value="{{ $unit }}">
                                        <label class="visually-hidden" for="edit-w-{{ $row->id }}">Weight</label>
                                        <input id="edit-w-{{ $row->id }}" type="number" name="weight" step="0.001" value="{{ $unit === 'lb' ? $svc->kgToLb((string)$row->weight_kg) : $row->weight_kg }}" required>
                                        <label class="visually-hidden" for="edit-t-{{ $row->id }}">Time</label>
                                        <input id="edit-t-{{ $row->id }}" type="datetime-local" name="recorded_at" value="{{ $local->format('Y-m-d\TH:i') }}">
                                        <label class="visually-hidden" for="edit-n-{{ $row->id }}">Note</label>
                                        <input id="edit-n-{{ $row->id }}" type="text" name="note" maxlength="240" value="{{ $row->note }}">
                                        <button class="yk-wj__btn" type="submit">Update</button>
                                    </form>
                                </details>
                                <form method="post" action="{{ route('tools.weight-journey.measurements.destroy', $row) }}" onsubmit="return confirm('Delete this measurement?');" data-wj-submit-once>
                                    @csrf
                                    @method('DELETE')
                                    <button class="yk-wj__btn-secondary" type="submit">Delete</button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</main>
<script>
(function () {
    document.querySelectorAll('[data-wj-submit-once]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (form.getAttribute('data-submitting') === '1') {
                event.preventDefault();
                return;
            }
            form.setAttribute('data-submitting', '1');
            form.querySelectorAll('button[type="submit"]').forEach(function (btn) {
                btn.disabled = true;
            });
        });
    });
})();
</script>
@endsection
