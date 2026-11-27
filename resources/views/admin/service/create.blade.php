@extends('admin.layouts.layout')
@section('content')
<div class="main-content app-content">
    <div class="container mt-4 mb-5">
        <h4>Add Category</h4>
        <form action="{{ route('admin.service.store') }}" method="POST" enctype="multipart/form-data" class="card mt-3">
            @csrf
            <div class="card-body row gy-3">
                @include('admin.service._form')
            </div>
            <div class="card-footer d-flex justify-content-between">
                <a href="{{ route('admin.service.index') }}" class="btn btn-secondary">Back</a>
                <button type="submit" class="btn btn-primary">Save Category</button>
            </div>
        </form>
    </div>
</div>
@endsection
