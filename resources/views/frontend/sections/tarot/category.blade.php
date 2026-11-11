@extends('layouts.layout')

@section('title', 'Choose Category | Tarot Reflection')
@section('meta_description', 'Choose what you would like clarity on for your Tarot Reflection.')
@section('body_class', 'yk-tarot-body')

@section('content')
<main class="yk-tarot yk-tools-ds" data-yk-tools-ds data-yk-tarot>
    <div class="yk-tarot__wrap">
        <a class="yk-tarot__back" href="{{ route('tools.tarot.intro') }}"><span aria-hidden="true">←</span> Back</a>

        <p class="yk-tarot__step">Step 1 of 5</p>
        <h1>What would you like clarity on?</h1>
        <p class="yk-tarot__lead">Choose one category. Your reading lens will follow this focus.</p>

        @if ($errors->any())
            <p class="yk-tarot__error" role="alert">{{ $errors->first() }}</p>
        @endif

        <form method="post" action="{{ route('tools.tarot.category.store') }}" class="yk-tarot__form" data-tarot-select-form>
            @csrf
            <ul class="yk-tarot__options yk-tarot__options--categories">
                @foreach ($categories as $key => $category)
                    @php $ui = \App\Support\Tarot\TarotUi::categoryPresentation($key); @endphp
                    <li>
                        <label class="yk-tarot__option is-{{ $category['tone'] }} @if ($selected === $key) is-selected @endif">
                            <input type="radio" name="category" value="{{ $key }}" @checked($selected === $key) required>
                            <span class="yk-tarot__option-emoji" aria-hidden="true">{{ $ui['emoji'] }}</span>
                            <span class="yk-tarot__option-copy">
                                <span class="yk-tarot__option-label">{{ $category['label'] }}</span>
                                <span class="yk-tarot__option-meta">{{ $ui['blurb'] }}</span>
                            </span>
                            <span class="yk-tarot__option-mark" aria-hidden="true"></span>
                        </label>
                    </li>
                @endforeach
            </ul>
            <button class="yk-tarot__btn" type="submit" data-tarot-continue @disabled($selected === null)>Continue</button>
        </form>
    </div>
</main>
@endsection
