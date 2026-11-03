@extends('layouts.layout')

@section('title', 'Log Weight | YuktiOne')
@section('meta_description', 'Add or edit weight measurements.')
@section('body_class', 'yk-wj-body')

@section('content')
@php $svc = app(\App\Services\WeightJourneyService::class); @endphp
<main class="yk-wj">
    <div class="yk-wj__wrap">
        <section class="yk-wj__card">
            <p class="yk-wj__badge">Log</p>
            <h1>Log weight</h1>
            <p class="yk-wj__lead">Store measurements in kilograms (fixed precision). Multiple readings on the same day are allowed.</p>

            @if (session('status'))
                <p class="yk-wj__ok">{{ session('status') }}</p>
            @endif
            @if ($errors->any())
                <p class="yk-wj__error">{{ $errors->first() }}</p>
            @endif

            <form method="post" action="{{ route('tools.weight-journey.measurements.store') }}">
                @csrf
                <fieldset class="yk-wj__field">
                    <legend>Unit</legend>
                    <div class="yk-wj__units">
                        <label><input type="radio" name="unit" value="kg" @checked(old('unit', $unit) === 'kg')> kg</label>
                        <label><input type="radio" name="unit" value="lb" @checked(old('unit', $unit) === 'lb')> lb</label>
                    </div>
                </fieldset>
                <div class="yk-wj__field">
                    <label for="weight">Weight</label>
                    <input id="weight" type="number" name="weight" step="0.001" min="0" value="{{ old('weight') }}" required>
                </div>
                <div class="yk-wj__field">
                    <label for="recorded">Date/time (Asia/Kolkata, optional)</label>
                    <input id="recorded" type="datetime-local" name="recorded_at" value="{{ old('recorded_at') }}">
                </div>
                <div class="yk-wj__field">
                    <label for="note">Optional note</label>
                    <textarea id="note" name="note" maxlength="240">{{ old('note') }}</textarea>
                </div>
                <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                <div class="yk-wj__actions">
                    <button class="yk-wj__btn" type="submit">Save measurement</button>
                    <a class="yk-wj__btn-secondary" href="{{ route('tools.weight-journey') }}">Your journey</a>
                </div>
            </form>

            <h2 style="font-size:1.05rem;margin:1.3rem 0 0.5rem;">Recent measurements</h2>
            @if ($recent->isEmpty())
                <p class="yk-wj__note">No measurements yet. When you log one, it will appear here.</p>
            @else
                <ul class="yk-wj__list">
                    @foreach ($recent as $row)
                        @php $local = $row->recorded_at->timezone($timezone); @endphp
                        <li>
                            <div>
                                <strong>{{ $svc->formatWeight((string) $row->weight_kg, $unit) }}</strong>
                                <div class="yk-wj__note" style="margin:0;">{{ $local->format('d M Y, g:i A') }}@if($row->note) · {{ $row->note }}@endif</div>
                            </div>
                            <div class="yk-wj__actions" style="margin:0;">
                                <details>
                                    <summary class="yk-wj__btn-secondary" style="list-style:none;">Edit</summary>
                                    <form method="post" action="{{ route('tools.weight-journey.measurements.update', $row) }}" style="margin-top:0.5rem;">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="unit" value="{{ $unit }}">
                                        <input type="number" name="weight" step="0.001" value="{{ $unit === 'lb' ? $svc->kgToLb((string)$row->weight_kg) : $row->weight_kg }}" required>
                                        <input type="datetime-local" name="recorded_at" value="{{ $local->format('Y-m-d\TH:i') }}">
                                        <input type="text" name="note" maxlength="240" value="{{ $row->note }}">
                                        <button class="yk-wj__btn" type="submit">Update</button>
                                    </form>
                                </details>
                                <form method="post" action="{{ route('tools.weight-journey.measurements.destroy', $row) }}" onsubmit="return confirm('Delete this measurement?');">
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
@endsection
