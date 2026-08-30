@extends('layouts.admin')

@section('title', 'Edit Plan')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Edit Plan') }}
        </h2>
        <a href="{{ route('isp.plans.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
            Back to List
        </a>
    </div>
@endsection

@section('content')
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <form method="POST" action="{{ route('isp.plans.update', $plan->id) }}" class="space-y-6">
                @csrf
                @method('PUT')
                @include('isp.plans.partials.form-fields', ['plan' => $plan])
                <div class="flex justify-end space-x-4">
                    <a href="{{ route('isp.plans.index') }}" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">Cancel</a>
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Update Plan</button>
                </div>
            </form>
        </div>
    </div>
@endsection
