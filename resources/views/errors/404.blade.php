@php
    $accent = '#00B14F';
    $accentDark = '#008b3d';
    $accentTint = 'rgba(0,177,79,0.15)';
@endphp

@extends('errors.layout')

@section('title', '404 · Not Found')

@section('badge')
    <div class="badge-status">
        <span class="dot"></span> 404 · NOT FOUND
    </div>
@endsection

@section('code', '404')

@section('title-h1')Page not found@endsection

@section('lead')
    The URL you're looking for isn't on our server. It may have moved, been renamed, or simply never existed. Double-check the address and try again.
@endsection

@section('actions')
    <a href="{{ url('/') }}" class="btn btn-primary">
        <i class="bi bi-house-fill"></i> Back to Home
    </a>
    <a href="javascript:history.back()" class="btn btn-ghost">
        <i class="bi bi-arrow-left"></i> Previous page
    </a>
@endsection

@section('footer-note')
    <p class="footer-note">
        Looking for something? Try
        <a href="{{ url('/tutorial') }}">Tutorial Hub</a>
        &middot;
        <a href="{{ url('/docs/aws-api') }}">API Reference</a>
        &middot;
        <a href="{{ url('/') }}">Live Map</a>
    </p>
@endsection
