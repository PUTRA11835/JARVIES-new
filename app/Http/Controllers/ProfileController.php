<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\Customer;
use App\Http\Controllers\PasswordSetupController;

class ProfileController extends Controller
{
    /**
     * Show the customer profile page (read-only).
     */
    public function edit(Request $request)
    {
        $sessionUser = session('user');

        if (!$sessionUser) {
            return redirect()->route('login');
        }

        if (($sessionUser['type'] ?? null) !== 'customer') {
            return redirect()->route('dashboard');
        }

        $customer = Customer::with([
            'basicData',
            'contact',
            'addresses',
            'identifications',
            'banks',
            'attachments',
        ])->find($sessionUser['id']);

        if (!$customer) {
            return redirect()->route('dashboard')->with('error', 'Profile not found.');
        }

        $authUser = DB::table('auth_users')
            ->where('customer_id', $sessionUser['id'])
            ->select('id', 'email', 'phone', 'username', 'last_login_at', 'created_at')
            ->first();

        return view('profile.customer.show', compact('customer', 'authUser'));
    }

    /**
     * @deprecated - kept for reference only, not reachable via routes.
     */
    private function _editLegacy(Request $request)
    {
        $sessionUser = session('user');
        $type    = $sessionUser['type'] ?? null;
        $profile = null;

        if ($type === 'employee') {
            $profile = DB::table('employee as e')
                ->join('employee_basic_data as eb', 'e.employee_id', '=', 'eb.employee_id')
                ->leftJoin('employee_address as ea', 'e.employee_id', '=', 'ea.employee_id')
                ->leftJoin('employee_role as r', 'e.role_id', '=', 'r.id')
                ->where('e.employee_id', $sessionUser['id'])
                ->select(
                    'e.employee_id',
                    'e.eci',
                    'e.is_active',
                    'eb.title',
                    'eb.first_name',
                    'eb.last_name',
                    'eb.nick_name',
                    'eb.gender',
                    'eb.birth_date',
                    'eb.birth_place',
                    'eb.religion',
                    'eb.marital_status',
                    'eb.since_date',
                    'eb.position',
                    'eb.division',
                    'eb.department',
                    'eb.employee_group',
                    'eb.employee_subgroup',
                    'ea.street',
                    'ea.city',
                    'ea.region',
                    'ea.country',
                    'ea.postal_code',
                    'ea.cell_phone',
                    'ea.telephone',
                    'ea.email_personal',
                    'ea.email_work',
                    'r.name as role_name'
                )
                ->first();

        } elseif ($type === 'customer') {
            $profile = DB::table('customer as c')
                ->join('customer_basic_data as cb', 'c.customer_id', '=', 'cb.customer_id')
                ->leftJoin('customer_contact as cc', 'c.customer_id', '=', 'cc.customer_id')
                ->where('c.customer_id', $sessionUser['id'])
                ->select(
                    'c.customer_id',
                    'c.customer_code',
                    'c.email',
                    'c.is_active',
                    'cb.title',
                    'cb.name_1',
                    'cb.name_2',
                    'cb.customer_group',
                    'cb.customer_category',
                    'cb.industry_sector',
                    'cb.ec_account_executive',
                    'cc.full_name as contact_name',
                    'cc.cell_phone as contact_phone'
                )
                ->first();
        }

        // Email & phone dari auth_users (sumber utama login)
        $authUser = DB::table('auth_users')
            ->where(function ($q) use ($sessionUser, $type) {
                if ($type === 'employee') {
                    $q->where('employee_id', $sessionUser['id']);
                } else {
                    $q->where('customer_id', $sessionUser['id']);
                }
            })
            ->select('id', 'email', 'phone', 'username', 'last_login_at', 'created_at')
            ->first();

        return view('profile.edit', compact('profile', 'authUser', 'sessionUser'));
    }

    /**
     * Send password reset link to the customer's registered email.
     * Called from the profile Change Password tab.
     */
    public function sendResetLink(Request $request)
    {
        $sessionUser = session('user');

        if (!$sessionUser) {
            return redirect()->route('login');
        }

        $authUser = DB::table('auth_users')
            ->where('customer_id', $sessionUser['id'])
            ->first();

        if (!$authUser || empty($authUser->email)) {
            return redirect()->route('profile')
                ->with('error', 'No email address is associated with your account. Please contact support.');
        }

        PasswordSetupController::generateAndSendToken($authUser, 'reset');

        Log::info('ProfileController: password reset link sent from profile', [
            'auth_user_id' => $authUser->id,
            'customer_id'  => $sessionUser['id'],
        ]);

        // Mask email for the check-email page
        [$local, $domain] = explode('@', $authUser->email, 2);
        $maskedEmail = substr($local, 0, 2) . str_repeat('*', max(strlen($local) - 2, 3)) . '@' . $domain;

        return redirect()->route('password-setup.check-email', [
            'email' => $maskedEmail,
            'type'  => 'reset',
        ]);
    }
}
