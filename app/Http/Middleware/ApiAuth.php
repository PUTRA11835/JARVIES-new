<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * API Authentication Middleware (Mobile - Customer Only)
 *
 * Validasi Bearer token dari header Authorization.
 * Format token: base64("{customer_code}|{timestamp}|customer")
 */
class ApiAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token not found. Please login first.',
            ], 401);
        }

        $decoded = base64_decode($token, true);
        $parts   = explode('|', $decoded ?? '');

        if (count($parts) < 3 || $parts[2] !== 'customer') {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token.',
            ], 401);
        }

        // Cek expiry: access token berlaku 7 hari sejak diterbitkan
        $issuedAt = (int) ($parts[1] ?? 0);
        if ($issuedAt === 0 || time() > $issuedAt + (7 * 24 * 3600)) {
            return response()->json([
                'success' => false,
                'message' => 'Session expired. Please refresh the token.',
                'code'    => 'TOKEN_EXPIRED',
            ], 401);
        }

        $customerCode = $parts[0];

        $customer = DB::table('customer as c')
            ->join('customer_basic_data as cb', 'c.customer_id', '=', 'cb.customer_id')
            ->leftJoin('auth_users as au', 'au.customer_id', '=', 'c.customer_id')
            ->where('c.customer_code', $customerCode)
            ->where('c.is_active', true)
            ->select(
                'c.customer_id',
                'c.customer_code',
                'c.email as company_email',
                'cb.title',
                'cb.name_1',
                'cb.name_2',
                'cb.customer_category',
                'cb.customer_group',
                'au.email as login_email'
            )
            ->first();

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found or inactive.',
            ], 401);
        }

        $companyName = trim($customer->title . ' ' . $customer->name_1 . ' ' . ($customer->name_2 ?? ''));

        // Gunakan login_email (auth_users) untuk pengiriman email — sama dengan web UI
        // Fallback ke company_email jika auth_users tidak ada
        $email = $customer->login_email ?? $customer->company_email;

        // Inject data user ke request agar bisa diakses controller
        $request->attributes->set('api_user', [
            'id'            => $customer->customer_id,
            'type'          => 'customer',
            'customer_code' => $customer->customer_code,
            'company_name'  => $companyName,
            'email'         => $email,
            'category'      => $customer->customer_category,
            'group'         => $customer->customer_group,
            'role'          => ['id' => 3, 'name' => 'Customer'],
        ]);

        return $next($request);
    }
}
