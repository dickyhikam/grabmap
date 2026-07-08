@php
    $accent = '#2563eb';
    $accentDark = '#1d4ed8';
    $accentTint = 'rgba(37,99,235,0.15)';
@endphp

@extends('errors.layout')

@section('title', '429 · Too Many Requests')

@section('badge')
    <div class="badge-status">
        <span class="dot"></span> 429 · RATE LIMITED
    </div>
@endsection

@section('icon')🚦@endsection

@section('title-h1')Slow down for a moment@endsection

@section('lead')
    You've made too many requests in a short time. To keep the service fair for everyone, please wait a bit before trying again.
@endsection

@section('actions')
    <a href="javascript:setTimeout(()=>location.reload(),3000)" class="btn btn-primary">
        <i class="bi bi-arrow-clockwise"></i> Try Again (3s)
    </a>
    <a href="{{ url('/') }}" class="btn btn-ghost">
        <i class="bi bi-house-fill"></i> Back to Home
    </a>
@endsection

@section('footer-note')
    <p class="footer-note">
        Building an app? Check the
        <a href="{{ url('/docs/aws-api') }}">API Reference</a>
        for rate limits and best practices.
    </p>
@endsection
