@extends('errors.layout')

@section('title', '503 · Service Unavailable')

@section('label', 'Error 503 · Service unavailable')

@section('icon', '🔧')

@section('title-h1', 'We\'ll be right back')

@section('lead', 'Our service is temporarily under maintenance. We\'re pushing updates to improve performance. Please check back in a few minutes.')

@section('actions')
    <a href="javascript:location.reload()" class="btn btn-primary">
        <i class="bi bi-arrow-clockwise"></i> Refresh
    </a>
    <a href="{{ url('/') }}" class="btn btn-ghost">
        <i class="bi bi-house-fill"></i> Back to Home
    </a>
@endsection

@section('footer-note')
    <p class="footer-note">
        Estimated downtime: <strong>a few minutes</strong>. Follow updates on
        <a href="https://developer.grab.com/" target="_blank" rel="noopener">Grab Developer</a>.
    </p>
@endsection
