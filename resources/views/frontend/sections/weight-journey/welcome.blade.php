@extends('layouts.layout')

@section('title', 'Weight Journey | YuktiOne')
@section('meta_description', 'Track weight privately with optional goals. Login required to save history.')
@section('body_class', 'yk-wj-body')

@section('content')
<main class="yk-wj">
    <div class="yk-wj__wrap">
        <section class="yk-wj__card">
            <p class="yk-wj__badge">Nutrition tool</p>
            <h1>Weight Journey</h1>
            <p class="yk-wj__lead">Log measurements, optionally set a personal goal, and review progress over time. Goals are tracking preferences—not medical prescriptions or ideal-weight recommendations.</p>
            <p class="yk-wj__note">Saved weight history requires a YuktiOne account. Guests see this introduction only; no measurements are stored until you sign in.</p>
            <div class="yk-wj__actions">
                <a class="yk-wj__btn" href="{{ route('login') }}">Log in to start</a>
                <a class="yk-wj__btn-secondary" href="{{ route('tools.index') }}">Back to Tools</a>
            </div>
        </section>
    </div>
</main>
@endsection
