@php
    $accent = '#dc2626';
    $accentDark = '#991b1b';
    $accentTint = 'rgba(220,38,38,0.15)';
@endphp

@extends('errors.layout')

@section('title', '500 · Server Error')

@section('badge')
    <div class="badge-status">
        <span class="dot"></span> 500 · SERVER ERROR
    </div>
@endsection

@section('code', '500')

@section('title-h1')Something went wrong on our end@endsection

@section('lead')
    An unexpected error occurred while processing your request. Our team has been automatically notified. Please try refreshing in a moment.
@endsection

@section('actions')
    <a href="javascript:location.reload()" class="btn btn-primary">
        <i class="bi bi-arrow-clockwise"></i> Try Again
    </a>
    <a href="{{ url('/') }}" class="btn btn-ghost">
        <i class="bi bi-house-fill"></i> Back to Home
    </a>
@endsection

@section('footer-note')
    <p class="footer-note">
        If the problem persists, contact our support team or try again in a few minutes.
    </p>
@endsection
