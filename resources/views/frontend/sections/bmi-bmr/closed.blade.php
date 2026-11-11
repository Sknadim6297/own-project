@extends('layouts.layout')

@section('title', 'BMI & BMR Calculator unavailable | YuktiOne')
@section('meta_description', 'This calculator is unavailable right now.')
@section('body_class', 'yk-bmi-body')

@section('content')
<main class="yk-bmi yk-tools-ds" data-yk-tools-ds data-yk-bmi>
    <div class="yk-bmi__wrap">
        <section class="yk-bmi__card">
            <p class="yk-bmi__badge">Nutrition tool</p>
            <h1>BMI &amp; BMR Calculator</h1>
            <p class="yk-bmi__lead">This calculator is unavailable right now.</p>
            <div class="yk-bmi__actions">
                <a class="yk-bmi__btn" href="{{ route('tools.index') }}">Back to Tools</a>
            </div>
        </section>
    </div>
</main>
@endsection
