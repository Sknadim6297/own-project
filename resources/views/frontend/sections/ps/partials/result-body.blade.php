@if ($toolKey === 'parenting-stress-check')
    <div class="yk-pfm__metrics">
        <div class="yk-pfm__metric yk-pfm__metric--primary"><span>Score</span><strong>{{ $result['score'] ?? 0 }}/100</strong></div>
        <div class="yk-pfm__metric"><span>Level</span><strong>{{ $result['level'] ?? '' }}</strong></div>
    </div>
    <h2 class="yk-pfm__section-title">Pressure areas</h2>
    <ul class="yk-pfm__list">
        @foreach (($result['pressure_areas'] ?? []) as $area)
            <li class="yk-pfm__action-card">
                <strong>{{ $area['label'] ?? '' }}</strong>
                <p>{{ $area['action_title'] ?? '' }}</p>
                <p>{{ $area['action_content'] ?? '' }}</p>
            </li>
        @endforeach
    </ul>
    @if (! empty($result['show_support_option']))
        <div class="yk-pfm__support" role="note">
            <p>If things feel too heavy, consider speaking with a trusted person or a qualified professional. This tool does not diagnose and is not emergency care.</p>
            <a class="yk-pfm__btn yk-pfm__btn--ghost" href="{{ route('gridlisting') }}">Explore expert support</a>
        </div>
    @endif
@elseif ($toolKey === 'child-routine-planner')
    @if (! empty($result['conflict']))
        <p class="yk-pfm__error" role="status">Too many activities for the available time. Review the tips below.</p>
    @endif
    <h2 class="yk-pfm__section-title">Daily timeline</h2>
    <ol class="yk-pfm__timeline">
        @foreach (($result['blocks'] ?? []) as $block)
            <li>
                <span class="yk-pfm__time">{{ $block['start'] }}–{{ $block['end'] }}</span>
                <span>{{ $block['label'] }}</span>
                <span class="yk-pfm__muted">{{ $block['type'] }}</span>
            </li>
        @endforeach
    </ol>
    <h2 class="yk-pfm__section-title">Balance check</h2>
    <ul class="yk-pfm__list">
        <li>Sleep opportunity: {{ $result['balance']['sleep_hours'] ?? '—' }} hours</li>
        <li>Study: {{ $result['balance']['study_minutes'] ?? 0 }} min</li>
        <li>Play/outdoor: {{ $result['balance']['play_minutes'] ?? 0 }} min</li>
        <li>Screen: {{ $result['balance']['screen_minutes'] ?? 0 }} min</li>
        <li>Family: {{ $result['balance']['family_minutes'] ?? 0 }} min</li>
    </ul>
    <h2 class="yk-pfm__section-title">Tips</h2>
    <ul class="yk-pfm__list">
        @foreach (($result['tips'] ?? []) as $tip)
            <li>{{ $tip }}</li>
        @endforeach
    </ul>
@elseif ($toolKey === 'parent-child-conversation-guide')
    @if (! empty($result['urgent_support']))
        <div class="yk-pfm__support" role="alert">
            <p>If anyone is in immediate danger, contact local emergency services. For ongoing safety concerns, seek appropriate professional support.</p>
        </div>
    @endif
    <div class="yk-pfm__guide-block">
        <h2 class="yk-pfm__section-title">Opening</h2>
        <p>{{ $result['opening'] ?? '' }}</p>
        <h2 class="yk-pfm__section-title">What to say</h2>
        <p>{{ $result['say'] ?? '' }}</p>
        <h2 class="yk-pfm__section-title">What to avoid</h2>
        <p>{{ $result['avoid'] ?? '' }}</p>
        <h2 class="yk-pfm__section-title">If the child shuts down</h2>
        <p>{{ $result['shutdown'] ?? '' }}</p>
        <h2 class="yk-pfm__section-title">Next step</h2>
        <p>{{ $result['next'] ?? '' }}</p>
    </div>
@elseif ($toolKey === 'daily-mindfulness-calm')
    <div class="yk-pfm__metrics">
        <div class="yk-pfm__metric yk-pfm__metric--primary"><span>Practice</span><strong>{{ $result['practice'] ?? '' }}</strong></div>
        <div class="yk-pfm__metric"><span>Duration</span><strong>{{ $result['duration'] ?? 0 }} min</strong></div>
    </div>
    <p><strong>Purpose:</strong> {{ $result['purpose'] ?? '' }}</p>
    <p class="yk-pfm__script">{{ $result['script'] ?? '' }}</p>
    <div class="yk-ps-timer" data-ps-timer data-seconds="{{ (int) ($result['target_seconds'] ?? 180) }}">
        <p class="yk-ps-timer__display" data-ps-timer-display>00:00</p>
        <div class="yk-pfm__actions">
            <button type="button" class="yk-pfm__btn" data-ps-timer-start>Start</button>
            <button type="button" class="yk-pfm__btn yk-pfm__btn--ghost" data-ps-timer-pause hidden>Pause</button>
            <button type="button" class="yk-pfm__btn yk-pfm__btn--ghost" data-ps-timer-resume hidden>Resume</button>
            <button type="button" class="yk-pfm__btn yk-pfm__btn--ghost" data-ps-timer-finish>Mark complete</button>
        </div>
        <p class="yk-pfm__status" data-ps-timer-status hidden>Session complete.</p>
    </div>
@elseif ($toolKey === 'purpose-values-finder')
    <h2 class="yk-pfm__section-title">Top values</h2>
    <ol class="yk-pfm__list">
        @foreach (($result['top_values'] ?? []) as $value)
            <li><strong>{{ $value }}</strong></li>
        @endforeach
    </ol>
    <h2 class="yk-pfm__section-title">Themes</h2>
    <ul class="yk-pfm__list">
        @foreach (($result['themes'] ?? []) as $theme)
            <li>{{ $theme }}</li>
        @endforeach
    </ul>
    <h2 class="yk-pfm__section-title">Action plan</h2>
    <ul class="yk-pfm__list">
        @foreach (($result['actions'] ?? []) as $action)
            <li><strong>{{ $action['value'] ?? '' }}:</strong> {{ $action['action'] ?? '' }}</li>
        @endforeach
    </ul>
    <p class="yk-pfm__note"><strong>Reflection:</strong> {{ $result['reflection'] ?? '' }}</p>
@endif
