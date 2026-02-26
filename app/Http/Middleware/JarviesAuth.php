<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

/**
 * Jarvies Authentication Middleware
 * 
 * Protects routes by ensuring:
 * 1. Valid authentication token exists in session
 * 2. User has allowed role (Super Admin or Customer)
 * 3. Session data structure is valid
 * 
 * Performance: Session data checked only once per request
 */
class JarviesAuth
{
    /**
     * Allowed roles for Jarvies Portal
     * 1 = Super Admin, 3 = Customer
     */
    private const ALLOWED_ROLES = [1, 3];

    /**
     * Cached authentication check result
     */
    private static ?bool $authChecked = null;
    private static ?bool $authResult = null;

    /**
     * Handle an incoming request
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Performance: Check auth only once per request lifecycle
        if (self::$authChecked === null) {
            self::$authChecked = true;
            self::$authResult = $this->performAuthCheck();
        }

        if (!self::$authResult) {
            // Clear invalid session
            $this->clearSession();
            
            return redirect()
                ->route('login')
                ->with('error', 'Please login to continue');
        }

        return $next($request);
    }

    /**
     * Perform comprehensive authentication check
     */
    private function performAuthCheck(): bool
    {
        // 1. Check basic authentication
        if (!session()->has('auth_token') || !session()->has('user')) {
            return false;
        }

        $user = session('user');

        // 2. Validate user data structure
        if (!is_array($user) || !isset($user['id'], $user['type'])) {
            Log::warning('JarviesAuth: Invalid user data structure', [
                'has_id' => isset($user['id']),
                'has_type' => isset($user['type'])
            ]);
            return false;
        }

        // 3. Check role authorization
        $roleId = $this->extractRoleId($user);
        
        if ($roleId === null) {
            Log::warning('JarviesAuth: Role ID not found', [
                'user_id' => $user['id'] ?? 'unknown'
            ]);
            return false;
        }

        if (!in_array($roleId, self::ALLOWED_ROLES, true)) {
            Log::warning('JarviesAuth: Unauthorized role', [
                'user_id' => $user['id'],
                'role_id' => $roleId,
                'allowed_roles' => self::ALLOWED_ROLES
            ]);
            return false;
        }

        return true;
    }

    /**
     * Extract role ID from user data (supports multiple formats)
     */
    private function extractRoleId(array $user): ?int
    {
        // Try multiple possible structures
        return $user['role']['id'] 
            ?? $user['role_id'] 
            ?? $user['roleId'] 
            ?? null;
    }

    /**
     * Clear session data on auth failure
     */
    private function clearSession(): void
    {
        session()->forget(['auth_token', 'user']);
    }

    /**
     * Reset static cache (useful for testing)
     */
    public static function resetCache(): void
    {
        self::$authChecked = null;
        self::$authResult = null;
    }
}