@extends('layouts.layout')

@section('title', 'About you | Career Interest Test | YuktiOne')
@section('meta_description', 'Tell us a little about yourself so we can show career information relevant to your stage.')
@section('body_class', 'yk-cit-body')

@php
    $icon = fn (string $name, int $size = 18) => view('frontend.sections.career-interest.partials.icon', ['name' => $name, 'size' => $size])->render();
@endphp

@section('content')
<main class="yk-cit yk-tools-ds" data-yk-tools-ds data-yk-cit>
    <div class="yk-cit__wrap">
        @include('frontend.sections.partials.tool-back-to-tools')

        <form class="yk-cit__card" method="post" action="{{ route('tools.career-interest.about.store') }}">
            @csrf
            <p class="yk-cit__badge">{!! $icon('user', 14) !!} Before you begin</p>
            <h1><x-yuktione.two-tone text="Tell us a little about yourself" /></h1>
            <p class="yk-cit__lead">This helps us show career information relevant to your current stage.</p>

            @if ($errors->any())
                <p class="yk-cit__error" role="alert">Please choose one option in every group to continue.</p>
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
                                    <input type="radio" name="{{ $name }}" value="{{ $value }}" @checked(old($name, $selected[$name] ?? null) === $value) required>
                                    <span>{{ $label }}</span>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                </fieldset>
            @endforeach

            <div class="yk-cit__actions yk-cit__actions--between">
                <a class="yk-cit__btn-secondary" href="{{ route('tools.career-interest') }}">{!! $icon('arrow-left', 18) !!} Back</a>
                <button class="yk-cit__btn" type="submit">Continue {!! $icon('arrow', 18) !!}</button>
            </div>
        </form>
    </div>
</main>
@endsection
