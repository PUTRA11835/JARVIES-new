<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Jarvies Guest Middleware
 * 
 * Redirects authenticated users away from guest-only pages (login)
 * Performance: Simple session check only
 */
class JarviesGuest
{
    /**
     * Handle an incoming request
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If already authenticated, redirect to dashboard
        if (session()->has('auth_token') && session()->has('user')) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}