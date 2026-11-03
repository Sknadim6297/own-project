@extends('layouts.layout')

@section('title', 'Today’s Intake | Water Tracker | YuktiOne')
@section('meta_description', 'Water entries for the selected day.')
@section('body_class', 'yk-water-body')

@section('content')
<main class="yk-water">
    <div class="yk-water__wrap">
        <section class="yk-water__card">
            <p class="yk-water__badge">{{ $dash['date'] }} · Asia/Kolkata</p>
            <h1>Day’s intake</h1>
            <p class="yk-water__lead">Total {{ $dash['total_label'] }} of {{ $dash['goal_label'] }} ({{ $dash['percent'] }}%).</p>

            @if (session('status'))
                <p class="yk-water__ok">{{ session('status') }}</p>
            @endif
            @if ($errors->any())
                <p class="yk-water__error">{{ $errors->first() }}</p>
            @endif

            <div class="yk-water__actions" style="margin-top:0;">
                <a class="yk-water__btn-secondary" href="{{ route('tools.water-intake.today', ['date' => $prevDate]) }}">Previous day</a>
                @unless ($isToday)
                    <a class="yk-water__btn-secondary" href="{{ route('tools.water-intake.today', ['date' => $nextDate]) }}">Next day</a>
                @endunless
                <a class="yk-water__btn" href="{{ route('tools.water-intake') }}">Dashboard</a>
            </div>

            @if ($entries->isEmpty())
                <p class="yk-water__lead" style="margin-top:1.2rem;">No entries logged for this day. An empty day means no saved logs—not proof that nothing was drunk.</p>
            @else
                <ul class="yk-water__list" style="margin-top:1.2rem;">
                    @foreach ($entries as $entry)
                        @php
                            $local = $entry->consumed_at->timezone($timezone);
                        @endphp
                        <li>
                            <div>
                                <strong>{{ number_format($entry->amount_ml) }} ml</strong>
                                <div class="yk-water__note" style="margin:0;">{{ $local->format('g:i A') }}</div>
                            </div>
                            <div class="yk-water__actions" style="margin:0;">
                                <details>
                                    <summary class="yk-water__btn-secondary" style="list-style:none;">Edit</summary>
                                    <form method="post" action="{{ route('tools.water-intake.entries.update', $entry) }}" style="margin-top:0.55rem;">
                                        @csrf
                                        @method('PUT')
                                        <label class="visually-hidden" for="edit-{{ $entry->id }}">Amount ml</label>
                                        <input id="edit-{{ $entry->id }}" type="number" name="amount_ml" min="1" max="5000" value="{{ $entry->amount_ml }}" required>
                                        <label class="visually-hidden" for="edit-time-{{ $entry->id }}">Time</label>
                                        <input id="edit-time-{{ $entry->id }}" type="datetime-local" name="consumed_at" value="{{ $local->format('Y-m-d\TH:i') }}">
                                        <button class="yk-water__btn" type="submit">Save</button>
                                    </form>
                                </details>
                                <form method="post" action="{{ route('tools.water-intake.entries.destroy', $entry) }}" onsubmit="return confirm('Delete this entry?');">
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
@endsection
