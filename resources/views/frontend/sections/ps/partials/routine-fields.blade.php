<div class="yk-pfm__step" data-pfm-step="0">
    <label class="yk-pfm__label" for="child_age">Child age</label>
    <input class="yk-pfm__input" type="number" id="child_age" name="child_age" min="3" max="18" value="{{ $old['child_age'] ?? '' }}" required>
    <label class="yk-pfm__label" for="wake_time">Wake time</label>
    <input class="yk-pfm__input" type="time" id="wake_time" name="wake_time" value="{{ $old['wake_time'] ?? '07:00' }}" required>
    <label class="yk-pfm__label" for="bed_time">Bedtime</label>
    <input class="yk-pfm__input" type="time" id="bed_time" name="bed_time" value="{{ $old['bed_time'] ?? '21:00' }}" required>
</div>
<div class="yk-pfm__step" data-pfm-step="1" hidden>
    <label class="yk-pfm__label" for="school_start">School / daycare start</label>
    <input class="yk-pfm__input" type="time" id="school_start" name="school_start" value="{{ $old['school_start'] ?? '08:30' }}" required>
    <label class="yk-pfm__label" for="school_end">School / daycare end</label>
    <input class="yk-pfm__input" type="time" id="school_end" name="school_end" value="{{ $old['school_end'] ?? '14:30' }}" required>
    <label class="yk-pfm__label" for="commute_minutes">Commute (minutes)</label>
    <input class="yk-pfm__input" type="number" id="commute_minutes" name="commute_minutes" min="0" max="180" value="{{ $old['commute_minutes'] ?? 0 }}">
    <label class="yk-pfm__label" for="tuition_start">Tuition / activity start (optional)</label>
    <input class="yk-pfm__input" type="time" id="tuition_start" name="tuition_start" value="{{ $old['tuition_start'] ?? '' }}">
    <label class="yk-pfm__label" for="tuition_minutes">Tuition duration (minutes)</label>
    <input class="yk-pfm__input" type="number" id="tuition_minutes" name="tuition_minutes" min="0" max="240" value="{{ $old['tuition_minutes'] ?? 0 }}">
    <label class="yk-pfm__label" for="lunch_time">Lunch time (optional)</label>
    <input class="yk-pfm__input" type="time" id="lunch_time" name="lunch_time" value="{{ $old['lunch_time'] ?? '13:00' }}">
</div>
<div class="yk-pfm__step" data-pfm-step="2" hidden>
    <label class="yk-pfm__label" for="study_minutes">Study / homework (minutes)</label>
    <input class="yk-pfm__input" type="number" id="study_minutes" name="study_minutes" min="0" max="300" value="{{ $old['study_minutes'] ?? 45 }}">
    <label class="yk-pfm__label" for="play_minutes">Play / outdoor (minutes)</label>
    <input class="yk-pfm__input" type="number" id="play_minutes" name="play_minutes" min="0" max="300" value="{{ $old['play_minutes'] ?? 45 }}">
    <label class="yk-pfm__label" for="screen_minutes">Screen time (minutes)</label>
    <input class="yk-pfm__input" type="number" id="screen_minutes" name="screen_minutes" min="0" max="300" value="{{ $old['screen_minutes'] ?? 30 }}">
    <label class="yk-pfm__label" for="family_minutes">Family time (minutes)</label>
    <input class="yk-pfm__input" type="number" id="family_minutes" name="family_minutes" min="0" max="300" value="{{ $old['family_minutes'] ?? 30 }}">
    <label class="yk-pfm__label" for="preferences">Child preferences (optional)</label>
    <textarea class="yk-pfm__input" id="preferences" name="preferences" rows="3" maxlength="500">{{ $old['preferences'] ?? '' }}</textarea>
</div>
