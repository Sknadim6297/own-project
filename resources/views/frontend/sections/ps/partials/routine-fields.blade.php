<div class="yk-ps__step is-active" data-ps-step="0">
    <h2 class="yk-ps__step-title">About your child's day</h2>
    <div class="yk-ps__field-grid">
        <div class="yk-ps__field yk-ps__field--wide">
            <label class="yk-ps__label" for="child_age">Child age</label>
            <div class="yk-ps__unit"><input class="yk-ps__input" type="number" id="child_age" name="child_age" min="3" max="18" inputmode="numeric" placeholder="e.g. 8" value="{{ $old['child_age'] ?? '' }}" required><span>years</span></div>
            <p class="yk-ps__hint">Between 3 and 18.</p>
        </div>
        <div class="yk-ps__field">
            <label class="yk-ps__label" for="wake_time">Wake time</label>
            <input class="yk-ps__input" type="time" id="wake_time" name="wake_time" value="{{ $old['wake_time'] ?? '07:00' }}" required>
        </div>
        <div class="yk-ps__field">
            <label class="yk-ps__label" for="bed_time">Bedtime</label>
            <input class="yk-ps__input" type="time" id="bed_time" name="bed_time" value="{{ $old['bed_time'] ?? '21:00' }}" required>
        </div>
    </div>
</div>
<div class="yk-ps__step" data-ps-step="1">
    <h2 class="yk-ps__step-title">Fixed commitments</h2>
    <div class="yk-ps__field-grid">
        <div class="yk-ps__field">
            <label class="yk-ps__label" for="school_start">School / daycare start</label>
            <input class="yk-ps__input" type="time" id="school_start" name="school_start" value="{{ $old['school_start'] ?? '08:30' }}" required>
        </div>
        <div class="yk-ps__field">
            <label class="yk-ps__label" for="school_end">School / daycare end</label>
            <input class="yk-ps__input" type="time" id="school_end" name="school_end" value="{{ $old['school_end'] ?? '14:30' }}" required>
        </div>
        <div class="yk-ps__field">
            <label class="yk-ps__label" for="commute_minutes">Commute <small>(each way)</small></label>
            <div class="yk-ps__unit"><input class="yk-ps__input" type="number" id="commute_minutes" name="commute_minutes" min="0" max="180" inputmode="numeric" value="{{ $old['commute_minutes'] ?? 0 }}"><span>min</span></div>
        </div>
        <div class="yk-ps__field">
            <label class="yk-ps__label" for="lunch_time">Lunch time <small>(optional)</small></label>
            <input class="yk-ps__input" type="time" id="lunch_time" name="lunch_time" value="{{ $old['lunch_time'] ?? '13:00' }}">
        </div>
        <div class="yk-ps__field">
            <label class="yk-ps__label" for="tuition_start">Tuition / activity start <small>(optional)</small></label>
            <input class="yk-ps__input" type="time" id="tuition_start" name="tuition_start" value="{{ $old['tuition_start'] ?? '' }}">
        </div>
        <div class="yk-ps__field">
            <label class="yk-ps__label" for="tuition_minutes">Tuition duration</label>
            <div class="yk-ps__unit"><input class="yk-ps__input" type="number" id="tuition_minutes" name="tuition_minutes" min="0" max="240" inputmode="numeric" value="{{ $old['tuition_minutes'] ?? 0 }}"><span>min</span></div>
        </div>
    </div>
</div>
<div class="yk-ps__step" data-ps-step="2">
    <h2 class="yk-ps__step-title">Daily balance</h2>
    <div class="yk-ps__field-grid">
        <div class="yk-ps__field">
            <label class="yk-ps__label" for="study_minutes">Study / homework</label>
            <div class="yk-ps__unit"><input class="yk-ps__input" type="number" id="study_minutes" name="study_minutes" min="0" max="300" inputmode="numeric" value="{{ $old['study_minutes'] ?? 45 }}"><span>min</span></div>
        </div>
        <div class="yk-ps__field">
            <label class="yk-ps__label" for="play_minutes">Play / outdoor</label>
            <div class="yk-ps__unit"><input class="yk-ps__input" type="number" id="play_minutes" name="play_minutes" min="0" max="300" inputmode="numeric" value="{{ $old['play_minutes'] ?? 45 }}"><span>min</span></div>
        </div>
        <div class="yk-ps__field">
            <label class="yk-ps__label" for="screen_minutes">Screen time</label>
            <div class="yk-ps__unit"><input class="yk-ps__input" type="number" id="screen_minutes" name="screen_minutes" min="0" max="300" inputmode="numeric" value="{{ $old['screen_minutes'] ?? 30 }}"><span>min</span></div>
        </div>
        <div class="yk-ps__field">
            <label class="yk-ps__label" for="family_minutes">Family time</label>
            <div class="yk-ps__unit"><input class="yk-ps__input" type="number" id="family_minutes" name="family_minutes" min="0" max="300" inputmode="numeric" value="{{ $old['family_minutes'] ?? 30 }}"><span>min</span></div>
        </div>
        <div class="yk-ps__field yk-ps__field--wide">
            <label class="yk-ps__label" for="preferences">Child preferences <small>(optional)</small></label>
            <textarea class="yk-ps__input yk-ps__textarea" id="preferences" name="preferences" rows="3" maxlength="500" placeholder="Anything that helps—favourite activities, sensitivities, rules at home…">{{ $old['preferences'] ?? '' }}</textarea>
            <p class="yk-ps__hint">Up to 500 characters.</p>
        </div>
    </div>
</div>
