@php
    $accent = '#f59e0b';
    $accentDark = '#d97706';
    $accentTint = 'rgba(245,158,11,0.15)';
    $feature = $feature ?? 'This page';
    $featureId = $featureId ?? 'this page';
@endphp

@extends('errors.layout')

@section('title', 'Under Maintenance')

@section('badge')
    <div class="badge-status">
        <span class="dot"></span> UNDER MAINTENANCE
    </div>
@endsection

@section('icon')🔧@endsection

@section('title-h1')
    {{ $feature }}<br>
    <span style="background: linear-gradient(135deg, #f59e0b, #d97706); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;">is being updated</span>
@endsection

@section('lead')
    We're improving {{ $featureId }} to be more accurate. This page will be back online shortly — thanks for your patience.
@endsection

@section('extra')
    <div class="info-card">
        <ul style="list-style:none;font-size:0.86rem;color:#374151;padding:0;margin:0;">
            <li style="display:flex;gap:10px;padding:6px 0;"><i class="bi bi-check-circle-fill" style="color:#00B14F;font-size:1rem;flex-shrink:0;margin-top:2px;"></i> <div><b style="color:#111827;">Interactive map & API tester</b> remain fully accessible.</div></li>
            <li style="display:flex;gap:10px;padding:6px 0;"><i class="bi bi-check-circle-fill" style="color:#00B14F;font-size:1rem;flex-shrink:0;margin-top:2px;"></i> <div><b style="color:#111827;">AWS API Key setup tutorial</b> is still available in the tutorial menu.</div></li>
            <li style="display:flex;gap:10px;padding:6px 0;"><i class="bi bi-check-circle-fill" style="color:#00B14F;font-size:1rem;flex-shrink:0;margin-top:2px;"></i> <div><b style="color:#111827;">Full API Reference</b> stays available too.</div></li>
        </ul>
    </div>
@endsection

@section('actions')
    <a href="{{ url('/') }}" class="btn btn-primary">
        <i class="bi bi-map-fill"></i> Open Live Map
    </a>
    <a href="{{ url('/tutorial') }}" class="btn btn-ghost">
        <i class="bi bi-book-fill"></i> Tutorial Hub
    </a>
@endsection

@section('footer-note')
    <p class="footer-note">
        Need current info? Check the official sources:
        <a href="https://aws.amazon.com/location/" target="_blank" rel="noopener">AWS Location</a>
        &middot;
        <a href="https://developer.grab.com/" target="_blank" rel="noopener">Grab Developer</a>
    </p>
@endsection
