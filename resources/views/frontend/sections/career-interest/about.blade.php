@extends('layouts.layout')

@section('title', 'About you | Career Interest Test | YuktiOne')
@section('meta_description', 'Tell us a little about yourself so we can show career information relevant to your stage.')
@section('body_class', 'yk-cit-body')

@section('content')
<main class="yk-cit">
    <div class="yk-cit__wrap">
        <form class="yk-cit__card" method="post" action="{{ route('tools.career-interest.about.store') }}">
            @csrf
            <p class="yk-cit__badge">About you</p>
            <h1>Tell us a little about yourself.</h1>
            <p class="yk-cit__lead">This helps us show career information relevant to your current stage.</p>

            @if ($errors->any())
                <p class="yk-cit__error">{{ $errors->first() }}</p>
            @endif

            @php
                $fields = [
                    'age_group' => ['label' => 'Age group', 'options' => $ageGroups],
                    'education' => ['label' => 'Current education', 'options' => $education],
                    'situation' => ['label' => 'Current situation', 'options' => $situations],
                    'geography' => ['label' => 'Preferred career geography', 'options' => $geographies],
                ];
            @endphp

            @foreach ($fields as $name => $field)
                <fieldset class="yk-cit__field">
                    <legend>{{ $field['label'] }}</legend>
                    <ul class="yk-cit__chips">
                        @foreach ($field['options'] as $value => $label)
                            <li>
                                <label>
                                    <input type="radio" name="{{ $name }}" value="{{ $value }}" @checked(old($name, $selected[$name] ?? null) === $value)>
                                    <span>{{ $label }}</span>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                </fieldset>
            @endforeach

            <div class="yk-cit__actions">
                <a class="yk-cit__btn-secondary" href="{{ route('tools.career-interest') }}">Previous</a>
                <button class="yk-cit__btn" type="submit">Continue</button>
            </div>
        </form>
    </div>
</main>
@endsection
