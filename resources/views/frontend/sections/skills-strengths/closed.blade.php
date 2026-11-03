@extends('layouts.layout')

@section('title', 'Skills & Strengths unavailable | YuktiOne')
@section('meta_description', 'This assessment is unavailable right now.')
@section('body_class', 'yk-ssa-body')

@section('content')
<main class="yk-ssa">
    <div class="yk-ssa__wrap">
        <section class="yk-ssa__card" aria-labelledby="ssa-closed-heading">
            <p class="yk-ssa__badge">Career tool</p>
            <h1 id="ssa-closed-heading">Skills &amp; Strengths Assessment</h1>
            <p class="yk-ssa__lead">This assessment is unavailable right now.</p>
            <div class="yk-ssa__actions">
                <a class="yk-ssa__btn" href="{{ route('tools.index') }}">Back to Tools</a>
            </div>
        </section>
    </div>
</main>
@endsection
