@extends('layouts.layout')

@section('title', 'Weight Journey unavailable | YuktiOne')
@section('meta_description', 'This tracker is unavailable right now.')
@section('body_class', 'yk-wj-body')

@section('content')
<main class="yk-wj">
    <div class="yk-wj__wrap">
        <section class="yk-wj__card">
            <p class="yk-wj__badge">Nutrition tool</p>
            <h1>Weight Journey</h1>
            <p class="yk-wj__lead">This tracker is unavailable right now.</p>
            <div class="yk-wj__actions">
                <a class="yk-wj__btn" href="{{ route('tools.index') }}">Back to Tools</a>
            </div>
        </section>
    </div>
</main>
@endsection
