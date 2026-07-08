@php
    $accent = '#f59e0b';
    $accentDark = '#d97706';
    $accentTint = 'rgba(245,158,11,0.15)';
@endphp

@extends('errors.layout')

@section('title', '503 · Service Unavailable')

@section('badge')
    <div class="badge-status">
        <span class="dot"></span> 503 · SERVICE UNAVAILABLE
    </div>
@endsection

@section('icon')🔧@endsection

@section('title-h1')We'll be right back@endsection

@section('lead')
    Our service is temporarily under maintenance. We're pushing updates to improve performance. Please check back in a few minutes.
@endsection

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
