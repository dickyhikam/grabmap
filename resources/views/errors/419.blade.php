@php
    $accent = '#7c3aed';
    $accentDark = '#6d28d9';
    $accentTint = 'rgba(124,58,237,0.15)';
@endphp

@extends('errors.layout')

@section('title', '419 · Session Expired')

@section('badge')
    <div class="badge-status">
        <span class="dot"></span> 419 · SESSION EXPIRED
    </div>
@endsection

@section('icon')⏱️@endsection

@section('title-h1')Your session has expired@endsection

@section('lead')
    For security reasons, your session timed out. Please refresh the page and try again. If you were submitting a form, you may need to resubmit your input.
@endsection

@section('actions')
    <a href="javascript:location.reload()" class="btn btn-primary">
        <i class="bi bi-arrow-clockwise"></i> Refresh
    </a>
    <a href="{{ url('/') }}" class="btn btn-ghost">
        <i class="bi bi-house-fill"></i> Back to Home
    </a>
@endsection
