@extends('layouts.app')

@section('title', 'Customers')
@section('page-title', 'Customers Management')
@section('page-subtitle', 'Manage all your customers')

@section('content')
<div class="bg-white rounded-xl border border-gray-100 p-6">
    <div class="text-center py-12">
        <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
        </svg>
        <h3 class="mt-4 text-lg font-semibold text-gray-900">Customers Page</h3>
        <p class="mt-2 text-sm text-gray-500">Customer management interface will be displayed here</p>
        <p class="mt-1 text-xs text-gray-400">{{ count($customers ?? []) }} customers found</p>
    </div>
</div>
@endsection