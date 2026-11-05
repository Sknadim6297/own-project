@extends('layouts.layout')

@section('title', 'Today’s Intake | Water Tracker | YuktiOne')
@section('meta_description', 'Water entries for the selected day.')
@section('body_class', 'yk-water-body')

@section('content')
<main class="yk-water">
    <div class="yk-water__wrap yk-water__wrap--wide">
        <section class="yk-water__card">
            <p class="yk-water__badge">{{ $dash['date'] }} · Asia/Kolkata</p>
            <h1>Day’s intake</h1>
            <p class="yk-water__lead">{{ $dash['total_label'] }} of {{ $dash['goal_label'] }} · {{ $dash['percent'] }}%</p>

            @if (session('status'))
                <p class="yk-water__ok" role="status">{{ session('status') }}</p>
            @endif
            @if ($errors->any())
                <p class="yk-water__error" role="alert">{{ $errors->first() }}</p>
            @endif

            <div class="yk-water__day-progress" aria-hidden="true">
                <div class="yk-water__day-progress-fill" style="width: {{ min(100, max(0, (int) $dash['percent'])) }}%"></div>
            </div>

            <div class="yk-water__actions" style="margin-top:0.85rem;">
                <a class="yk-water__btn-secondary" href="{{ route('tools.water-intake.today', ['date' => $prevDate]) }}">Previous day</a>
                @unless ($isToday)
                    <a class="yk-water__btn-secondary" href="{{ route('tools.water-intake.today', ['date' => $nextDate]) }}">Next day</a>
                @endunless
                <a class="yk-water__btn" href="{{ route('tools.water-intake') }}">Dashboard</a>
            </div>

            @if ($entries->isEmpty())
                <div class="yk-water__empty" role="status">
                    <p style="margin:0 0 0.25rem;font-weight:700;color:var(--yk-navy);">No entries logged</p>
                    <p style="margin:0;">Empty means no saved logs—not proof that nothing was drunk.</p>
                </div>
            @else
                <ul class="yk-water__list yk-water__list--manage" style="margin-top:1.15rem;">
                    @foreach ($entries as $entry)
                        @php $local = $entry->consumed_at->timezone($timezone); @endphp
                        <li>
                            <div>
                                <strong>{{ number_format($entry->amount_ml) }} ml</strong>
                                <div class="yk-water__meta">{{ $local->format('g:i A') }}</div>
                            </div>
                            <div class="yk-water__entry-actions">
                                <details>
                                    <summary>Edit</summary>
                                    <form method="post" action="{{ route('tools.water-intake.entries.update', $entry) }}" class="yk-water__edit-form" data-water-submit-once>
                                        @csrf
                                        @method('PUT')
                                        <label class="visually-hidden" for="edit-{{ $entry->id }}">Amount ml</label>
                                        <input id="edit-{{ $entry->id }}" type="number" name="amount_ml" min="1" max="5000" value="{{ $entry->amount_ml }}" required>
                                        <label class="visually-hidden" for="edit-time-{{ $entry->id }}">Time</label>
                                        <input id="edit-time-{{ $entry->id }}" type="datetime-local" name="consumed_at" value="{{ $local->format('Y-m-d\TH:i') }}">
                                        <button class="yk-water__btn" type="submit">Save</button>
                                    </form>
                                </details>
                                <form method="post" action="{{ route('tools.water-intake.entries.destroy', $entry) }}" onsubmit="return confirm('Delete this entry?');" data-water-submit-once>
                                    @csrf
                                    @method('DELETE')
                                    <button class="yk-water__btn-secondary" type="submit">Delete</button>
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
    document.querySelectorAll('[data-water-submit-once]').forEach(function (form) {
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
