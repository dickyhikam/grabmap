@extends('errors.layout')

@section('title', '500 · Server Error')

@section('label', 'Error 500 · Server error')

@section('icon', '💥')

@section('title-h1', 'Something went wrong on our end')

@section('lead', 'The server hit an unexpected error while handling your request. The team has been notified — please try again in a moment.')

@section('actions')
    <a href="javascript:location.reload()" class="btn btn-primary">
        <i class="bi bi-arrow-clockwise"></i> Try again
    </a>
    <a href="{{ url('/') }}" class="btn btn-ghost">
        <i class="bi bi-house-fill"></i> Back to Home
    </a>
@endsection
