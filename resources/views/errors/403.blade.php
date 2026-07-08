@php
    $accent = '#dc2626';
    $accentDark = '#991b1b';
    $accentTint = 'rgba(220,38,38,0.15)';
@endphp

@extends('errors.layout')

@section('title', '403 · Forbidden')

@section('badge')
    <div class="badge-status">
        <span class="dot"></span> 403 · FORBIDDEN
    </div>
@endsection

@section('code', '403')

@section('title-h1')Access denied@endsection

@section('lead')
    You don't have permission to view this resource. If you believe this is a mistake, make sure you're signed in with the right account or contact an administrator.
@endsection

@section('actions')
    <a href="{{ url('/') }}" class="btn btn-primary">
        <i class="bi bi-house-fill"></i> Back to Home
    </a>
    <a href="javascript:history.back()" class="btn btn-ghost">
        <i class="bi bi-arrow-left"></i> Previous page
    </a>
@endsection
