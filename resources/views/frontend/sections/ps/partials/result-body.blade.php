@php
    $icon = fn (string $name, int $size = 18) => view('frontend.sections.ps.partials.icon', ['name' => $name, 'size' => $size])->render();
@endphp

@if ($toolKey === 'parenting-stress-check')
    @php
        $score = (int) ($result['score'] ?? 0);
        $level = (string) ($result['level'] ?? '');
        $levelKey = strtolower($level) ?: 'low';
        $circ = 2 * M_PI * 52;
        $offset = $circ * (1 - max(0, min(100, $score)) / 100);
        $levelCopy = [
            'low' => 'Your answers suggest the pressure feels manageable right now. Keep protecting what is working.',
            'moderate' => 'Some areas are putting steady pressure on you. Small, specific changes can make a real difference.',
            'high' => 'Pressure looks high right now. Gentle steps and support can help—you do not have to carry it alone.',
        ][$levelKey] ?? '';
    @endphp
    <div class="yk-ps__score yk-ps__score--{{ $levelKey }}">
        <div class="yk-ps__ring" role="img" aria-label="Stress score {{ $score }} out of 100">
            <svg viewBox="0 0 120 120" aria-hidden="true">
                <circle class="yk-ps__ring-track" cx="60" cy="60" r="52"/>
                <circle class="yk-ps__ring-bar" cx="60" cy="60" r="52" stroke-dasharray="{{ round($circ, 2) }}" stroke-dashoffset="{{ round($offset, 2) }}" transform="rotate(-90 60 60)"/>
            </svg>
            <div class="yk-ps__ring-read"><strong>{{ $score }}</strong><span>out of 100</span></div>
        </div>
        <div class="yk-ps__score-copy">
            <p class="yk-ps__kicker">Stress level</p>
            <p class="yk-ps__level"><span class="yk-ps__pill yk-ps__pill--{{ $levelKey }}">{{ $level }}</span></p>
            <p>{{ $levelCopy }}</p>
            <div class="yk-ps__meter" aria-hidden="true"><span style="left: {{ max(2, min(98, $score)) }}%"></span></div>
            <div class="yk-ps__meter-labels" aria-hidden="true"><span>Low</span><span>Moderate</span><span>High</span></div>
        </div>
    </div>

    <h2 class="yk-ps__section-title">Pressure areas</h2>
    <ul class="yk-ps__cards">
        @foreach (($result['pressure_areas'] ?? []) as $area)
            <li class="yk-ps__card-item">
                <span class="yk-ps__rank">{{ $loop->iteration }}</span>
                <div>
                    <p class="yk-ps__card-label">{{ $area['label'] ?? '' }}</p>
                    <p class="yk-ps__card-title">{{ $area['action_title'] ?? '' }}</p>
                    <p class="yk-ps__card-text">{{ $area['action_content'] ?? '' }}</p>
                </div>
            </li>
        @endforeach
    </ul>

    @if (! empty($result['show_support_option']))
        <div class="yk-ps__support" role="note">
            <span class="yk-ps__support-icon">{!! $icon('heart-pulse', 22) !!}</span>
            <div>
                <p>If things feel too heavy, consider speaking with a trusted person or a qualified professional. This tool does not diagnose and is not emergency care.</p>
                <a class="yk-ps__btn yk-ps__btn--ghost" href="{{ route('categories.index') }}">Browse categories {!! $icon('arrow', 16) !!}</a>
            </div>
        </div>
    @endif

@elseif ($toolKey === 'child-routine-planner')
    @php
        $typeLabels = ['anchor' => 'Anchor', 'meal' => 'Meal', 'fixed' => 'Fixed', 'flexible' => 'Flexible', 'buffer' => 'Buffer'];
        $balance = $result['balance'] ?? [];
        $tiles = [
            ['label' => 'Sleep opportunity', 'value' => ($balance['sleep_hours'] ?? '—').' h', 'pct' => min(100, ((float) ($balance['sleep_hours'] ?? 0)) / 12 * 100), 'tone' => 'sleep'],
            ['label' => 'Study', 'value' => ($balance['study_minutes'] ?? 0).' min', 'pct' => min(100, ($balance['study_minutes'] ?? 0) / 120 * 100), 'tone' => 'study'],
            ['label' => 'Play / outdoor', 'value' => ($balance['play_minutes'] ?? 0).' min', 'pct' => min(100, ($balance['play_minutes'] ?? 0) / 120 * 100), 'tone' => 'play'],
            ['label' => 'Screen', 'value' => ($balance['screen_minutes'] ?? 0).' min', 'pct' => min(100, ($balance['screen_minutes'] ?? 0) / 120 * 100), 'tone' => 'screen'],
            ['label' => 'Family', 'value' => ($balance['family_minutes'] ?? 0).' min', 'pct' => min(100, ($balance['family_minutes'] ?? 0) / 120 * 100), 'tone' => 'family'],
        ];
    @endphp
    @if (! empty($result['conflict']))
        <p class="yk-ps__error" role="status">{!! $icon('alert', 18) !!} <span>Too many activities for the available time. Review the tips below.</span></p>
    @endif
    <h2 class="yk-ps__section-title">Daily timeline</h2>
    <ol class="yk-ps__timeline">
        @foreach (($result['blocks'] ?? []) as $block)
            <li class="yk-ps__tl-item yk-ps__tl-item--{{ $block['type'] }}">
                <span class="yk-ps__time">{{ $block['start'] }}@if ($block['end'] !== $block['start'])<small>– {{ $block['end'] }}</small>@endif</span>
                <span class="yk-ps__tl-dot" aria-hidden="true"></span>
                <span class="yk-ps__tl-body">
                    <strong>{{ $block['label'] }}</strong>
                    <em class="yk-ps__chip yk-ps__chip--{{ $block['type'] }}">{{ $typeLabels[$block['type']] ?? $block['type'] }}</em>
                </span>
            </li>
        @endforeach
    </ol>

    <h2 class="yk-ps__section-title">Balance check</h2>
    <div class="yk-ps__balance">
        @foreach ($tiles as $tile)
            <div class="yk-ps__balance-tile yk-ps__balance-tile--{{ $tile['tone'] }}">
                <span>{{ $tile['label'] }}</span>
                <strong>{{ $tile['value'] }}</strong>
                <i aria-hidden="true"><b style="width: {{ round($tile['pct']) }}%"></b></i>
            </div>
        @endforeach
    </div>

    <h2 class="yk-ps__section-title">Tips</h2>
    <ul class="yk-ps__tips">
        @foreach (($result['tips'] ?? []) as $tip)
            <li>{!! $icon('bulb', 18) !!}<span>{{ $tip }}</span></li>
        @endforeach
    </ul>

@elseif ($toolKey === 'parent-child-conversation-guide')
    @php
        $avoidItems = array_values(array_filter(array_map('trim', explode(' / ', (string) ($result['avoid'] ?? '')))));
    @endphp
    @if (! empty($result['urgent_support']))
        <div class="yk-ps__support yk-ps__support--urgent" role="alert">
            <span class="yk-ps__support-icon">{!! $icon('alert', 22) !!}</span>
            <div><p>If anyone is in immediate danger, contact local emergency services. For ongoing safety concerns, seek appropriate professional support.</p></div>
        </div>
    @endif
    <ul class="yk-ps__chips">
        @if (! empty($result['situation_label']))<li class="yk-ps__chip">{{ $result['situation_label'] }}</li>@endif
        @if (! empty($result['age_band']))<li class="yk-ps__chip">Ages {{ $result['age_band'] }}</li>@endif
        @if (! empty($result['goal_label']))<li class="yk-ps__chip">{{ $result['goal_label'] }}</li>@endif
    </ul>
    <div class="yk-ps__guide">
        <section class="yk-ps__guide-card yk-ps__guide-card--open">
            <h2 class="yk-ps__section-title">{!! $icon('chat', 20) !!} Opening</h2>
            <blockquote>{{ $result['opening'] ?? '' }}</blockquote>
        </section>
        <section class="yk-ps__guide-card">
            <h2 class="yk-ps__section-title">{!! $icon('sparkle', 20) !!} What to say</h2>
            <p>{{ $result['say'] ?? '' }}</p>
        </section>
        <section class="yk-ps__guide-card yk-ps__guide-card--avoid">
            <h2 class="yk-ps__section-title">{!! $icon('alert', 20) !!} What to avoid</h2>
            @if (count($avoidItems) > 1)
                <ul>@foreach ($avoidItems as $item)<li>{{ $item }}</li>@endforeach</ul>
            @else
                <p>{{ $result['avoid'] ?? '' }}</p>
            @endif
        </section>
        <section class="yk-ps__guide-card yk-ps__guide-card--shutdown">
            <h2 class="yk-ps__section-title">{!! $icon('refresh', 20) !!} If the child shuts down</h2>
            <p>{{ $result['shutdown'] ?? '' }}</p>
        </section>
        <section class="yk-ps__guide-card yk-ps__guide-card--next">
            <h2 class="yk-ps__section-title">{!! $icon('arrow', 20) !!} Next step</h2>
            <p>{{ $result['next'] ?? '' }}</p>
        </section>
    </div>

@elseif ($toolKey === 'daily-mindfulness-calm')
    @php
        $target = (int) ($result['target_seconds'] ?? 180);
        $ringCirc = 2 * M_PI * 54;
    @endphp
    <div class="yk-ps__metrics">
        <div class="yk-ps__metric yk-ps__metric--primary"><span>Practice</span><strong>{{ $result['practice'] ?? '' }}</strong></div>
        <div class="yk-ps__metric"><span>Duration</span><strong>{{ $result['duration'] ?? 0 }} min</strong></div>
        @if (! empty($result['state_label']))
            <div class="yk-ps__metric"><span>Feeling</span><strong>{{ $result['state_label'] }}</strong></div>
        @endif
    </div>
    <p class="yk-ps__purpose"><strong>Purpose:</strong> {{ $result['purpose'] ?? '' }}</p>
    <div class="yk-ps__practice">
        <div class="yk-ps__script-wrap">
            <p class="yk-ps__kicker">Guided script</p>
            <p class="yk-ps__script">{{ $result['script'] ?? '' }}</p>
        </div>
        <div class="yk-ps__timer" data-ps-timer data-seconds="{{ $target }}">
            <div class="yk-ps__timer-ring">
                <svg viewBox="0 0 120 120" aria-hidden="true">
                    <circle class="yk-ps__ring-track" cx="60" cy="60" r="54"/>
                    <circle class="yk-ps__ring-bar" data-ps-timer-ring cx="60" cy="60" r="54" stroke-dasharray="{{ round($ringCirc, 2) }}" stroke-dashoffset="{{ round($ringCirc, 2) }}" transform="rotate(-90 60 60)"/>
                </svg>
                <p class="yk-ps__timer-display" data-ps-timer-display role="timer" aria-live="off">{{ sprintf('%02d:%02d', intdiv($target, 60), $target % 60) }}</p>
            </div>
            <div class="yk-ps__actions yk-ps__actions--center">
                <button type="button" class="yk-ps__btn" data-ps-timer-start>{!! $icon('play', 16) !!} Start</button>
                <button type="button" class="yk-ps__btn yk-ps__btn--ghost" data-ps-timer-pause hidden>{!! $icon('pause', 16) !!} Pause</button>
                <button type="button" class="yk-ps__btn yk-ps__btn--ghost" data-ps-timer-resume hidden>{!! $icon('play', 16) !!} Resume</button>
                <button type="button" class="yk-ps__btn yk-ps__btn--ghost" data-ps-timer-finish>{!! $icon('check', 16) !!} Mark complete</button>
            </div>
            <p class="yk-ps__status yk-ps__status--center" data-ps-timer-status role="status" hidden>{!! $icon('sparkle', 18) !!} <span>Session complete. Well done for taking this time.</span></p>
        </div>
    </div>

@elseif ($toolKey === 'purpose-values-finder')
    <h2 class="yk-ps__section-title">Top values</h2>
    <ol class="yk-ps__values">
        @foreach (($result['top_values'] ?? []) as $value)
            <li><span class="yk-ps__rank">{{ $loop->iteration }}</span><strong>{{ $value }}</strong></li>
        @endforeach
    </ol>

    @if (! empty($result['themes']))
        <h2 class="yk-ps__section-title">Themes</h2>
        <ul class="yk-ps__chips">
            @foreach (($result['themes'] ?? []) as $theme)
                <li class="yk-ps__chip yk-ps__chip--theme">{{ $theme }}</li>
            @endforeach
        </ul>
    @endif

    <h2 class="yk-ps__section-title">Action plan</h2>
    <ul class="yk-ps__cards">
        @foreach (($result['actions'] ?? []) as $action)
            <li class="yk-ps__card-item">
                <span class="yk-ps__rank yk-ps__rank--soft">{{ $loop->iteration }}</span>
                <div>
                    <p class="yk-ps__card-label">{{ $action['value'] ?? '' }}</p>
                    <p class="yk-ps__card-text">{{ $action['action'] ?? '' }}</p>
                </div>
            </li>
        @endforeach
    </ul>
    <blockquote class="yk-ps__reflection"><strong>Reflection</strong>{{ $result['reflection'] ?? '' }}</blockquote>
@endif
