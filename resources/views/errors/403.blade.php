@extends('errors.layout')

@section('title', '403 · Forbidden')

@section('label', 'Error 403 · Forbidden')

@section('stage', 'dark')

{{-- Latar gambarnya sudah gelap bawaan, panggungnya ikut gelap supaya tepinya menyatu. --}}
@section('image')
    <picture>
        <source srcset="{{ asset('images/errors/403.webp') }}" type="image/webp">
        <img src="{{ asset('images/errors/403.png') }}" alt="403" width="900" height="600">
    </picture>
@endsection

@section('title-h1', 'Access denied')

@section('lead', 'You don\'t have permission to view this resource. If you believe this is a mistake, make sure you\'re signed in with the right account or contact an administrator.')

@section('actions')
    <a href="{{ url('/') }}" class="btn btn-primary">
        <i class="bi bi-house-fill"></i> Back to Home
    </a>
    <a href="javascript:history.back()" class="btn btn-ghost">
        <i class="bi bi-arrow-left"></i> Previous page
    </a>
@endsection
