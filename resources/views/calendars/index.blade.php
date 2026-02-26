@extends('layouts.app')

@section('title', 'Calendar')
@section('page-title', 'Calendar')
@section('page-subtitle', 'View and manage your schedule')

@section('content')
<div class="bg-white rounded-xl border border-gray-100 p-6">
    <div class="text-center py-12">
        <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <h3 class="mt-4 text-lg font-semibold text-gray-900">Calendar Page</h3>
        <p class="mt-2 text-sm text-gray-500">Calendar and scheduling interface will be displayed here</p>
    </div>
</div>
@endsection