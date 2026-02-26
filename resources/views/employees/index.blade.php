@extends('layouts.app')

@section('title', 'Employees')
@section('page-title', 'Employees Management')
@section('page-subtitle', 'Manage all your employees')

@section('content')
<div class="bg-white rounded-xl border border-gray-100 p-6">
    <div class="text-center py-12">
        <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
        </svg>
        <h3 class="mt-4 text-lg font-semibold text-gray-900">Employees Page</h3>
        <p class="mt-2 text-sm text-gray-500">Employee management interface will be displayed here</p>
        <p class="mt-1 text-xs text-gray-400">{{ count($employees ?? []) }} employees found</p>
    </div>
</div>
@endsection