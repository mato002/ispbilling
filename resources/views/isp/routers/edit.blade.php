@extends('layouts.admin')

@section('title', 'Edit router')

@section('header')
    <x-isp-page-header
        :title="'Edit router · '.$router->name"
        module="Network"
    >
        <x-slot name="actions">
            <a href="{{ route('isp.routers.show', $router) }}" class="pb-btn">View router</a>
            <a href="{{ route('isp.routers.index') }}" class="pb-btn">Back to fleet</a>
        </x-slot>
    </x-isp-page-header>
@endsection

@section('content')
    <form method="POST" action="{{ route('isp.routers.update', $router) }}" class="space-y-6">
        @csrf
        @method('PUT')

        @include('isp.routers.partials.form', ['router' => $router, 'owners' => $owners])

        <div class="flex justify-end gap-3">
            <a href="{{ route('isp.routers.show', $router) }}" class="pb-btn">Cancel</a>
            <button type="submit" class="pb-btn pb-btn-primary">Save changes</button>
        </div>
    </form>
@endsection
