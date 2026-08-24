@extends('errors.layout')

@section('title', '404 · Not Found')

@section('label', 'Error 404 · Not found')

{{-- PNG-nya berlatar transparan. WebP dipakai lebih dulu — ukurannya seperlima PNG. --}}
@section('image')
<picture>
    <source srcset="{{ asset('images/errors/404.webp') }}" type="image/webp">
    <img src="{{ asset('images/errors/404.png') }}" alt="404" width="900" height="675">
</picture>
@endsection

@section('title-h1', 'Page not found')

@section('lead', 'The URL you\'re looking for isn\'t on our server. It may have moved, been renamed, or simply never existed. Double-check the address and try again.')

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