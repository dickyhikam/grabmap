@extends('errors.layout')

@section('title', '419 · Page Expired')

@section('label', 'Error 419 · Session expired')

@section('icon', '⏱️')

@section('title-h1', 'Your session has expired')

@section('lead', 'For your security, the page was open too long and the form token expired. Reload the page and submit again.')

@section('actions')
    <a href="javascript:location.reload()" class="btn btn-primary">
        <i class="bi bi-arrow-clockwise"></i> Reload page
    </a>
    <a href="{{ url('/') }}" class="btn btn-ghost">
        <i class="bi bi-house-fill"></i> Back to Home
    </a>
@endsection
