@extends('layouts.layout')

@section('title', 'Career Interest Test unavailable | YuktiOne')
@section('meta_description', 'This assessment is unavailable right now.')
@section('body_class', 'yk-cit-body')

@section('content')
<main class="yk-cit">
    <div class="yk-cit__wrap">
        <section class="yk-cit__card" aria-labelledby="cit-closed-heading">
            <p class="yk-cit__badge">Free tool</p>
            <h1 id="cit-closed-heading">Career Interest Test</h1>
            <p class="yk-cit__lead">This assessment is unavailable right now.</p>
            <div class="yk-cit__actions">
                <a class="yk-cit__btn" href="{{ route('tools.index') }}">Back to Tools</a>
            </div>
        </section>
    </div>
</main>
@endsection
