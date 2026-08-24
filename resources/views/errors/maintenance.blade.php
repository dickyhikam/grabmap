@php
    $feature = $feature ?? 'This page';
    $featureId = $featureId ?? 'this page';
@endphp

@extends('errors.layout')

@section('title', 'Under Maintenance')

@section('label', 'Under maintenance')

@section('icon', '🔧')

@section('title-h1', $feature . ' is being updated')

@section('lead')
    We're improving {{ $featureId }} to be more accurate. This page will be back online shortly — thanks for your patience. The live map, API tester, and tutorials stay open.
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
