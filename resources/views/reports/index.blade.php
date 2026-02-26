@extends('layouts.app')

@section('title', 'Reports')
@section('page-title', 'Reports & Analytics')
@section('page-subtitle', 'View detailed reports and analytics')

@section('content')
<div class="bg-white rounded-xl border border-gray-100 p-6">
    <div class="text-center py-12">
        <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <h3 class="mt-4 text-lg font-semibold text-gray-900">Reports Page</h3>
        <p class="mt-2 text-sm text-gray-500">Detailed reports and analytics will be displayed here</p>
    </div>
</div>
@endsection