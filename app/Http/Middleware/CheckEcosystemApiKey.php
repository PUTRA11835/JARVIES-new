<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckEcosystemApiKey
{
    public function handle(Request $request, Closure $next)
    {
        $expected = config('services.ecosystem.api_key');
        $provided = $request->header('X-Api-Key');

        if (!$expected || $provided !== $expected) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
