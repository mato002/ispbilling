@extends('layouts.admin')

@section('title', $title ?? __('Admin'))

@if (isset($header))
    @section('header')
        {{ $header }}
    @endsection
@endif

@section('content')
    {{ $slot }}
@endsection
