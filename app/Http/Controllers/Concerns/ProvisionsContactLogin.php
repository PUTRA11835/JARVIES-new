<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Self-serve login provisioning for customer contacts.
 *
 * Shared by the web (App\Http\Controllers\AuthController) and mobile
 * (App\Http\Controllers\Api\AuthController) login flows so the master-data check
 * lives in exactly one place.
 *
 * When someone logs in with an email that has no auth_users row yet, we look it up
 * in customer master data (customer_contact.email_work / email_personal) under an
 * active customer. If it is a known contact, an account is provisioned on the fly
 * with is_already_cp=false — the same outcome as an admin clicking "Grant Access" in
 * EcoSystem — so a password-setup email can be sent. If the email is not a known
 * contact, the caller responds with a generic "Invalid email or password".
 */
trait ProvisionsContactLogin
{
    /**
     * @return object|null The auth_users row to continue login with, or null if the
     *                     email is not a registered customer contact.
     */
    protected function provisionContactLogin(string $email, ?string $requestId = null): ?object
    {
        // Only meaningful for email identifiers (login also accepts username/phone)
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        // Is this email a contact registered under an active customer?
        $contact = DB::table('customer_contact as cc')
            ->join('customer as c', 'c.customer_id', '=', 'cc.customer_id')
            ->where('c.is_active', true)
            ->where(function ($q) use ($email) {
                $q->where('cc.email_work', $email)
                  ->orWhere('cc.email_personal', $email);
            })
            ->orderBy('cc.contact_id')
            ->select('cc.contact_id', 'cc.customer_id', 'cc.full_name', 'cc.cell_phone', 'c.customer_code')
            ->first();

        if (!$contact) {
            return null;
        }

        // If this contact was already granted under a different login email, reuse that
        // row instead of creating a duplicate (auth_users keeps one row per contact).
        $existing = DB::table('auth_users')->where('contact_id', $contact->contact_id)->first();
        if ($existing) {
            return $existing;
        }

        // Build a unique username, mirroring EcoSystem CustomerContactController::createLogin
        $customerCode = $contact->customer_code ?: 'CP';
        $namePart     = Str::slug($contact->full_name ?? (string) $contact->contact_id, '');
        $username     = strtolower($customerCode . '_' . $namePart);
        $baseUsername = $username;
        $i = 1;
        while (DB::table('auth_users')->where('username', $username)->exists()) {
            $username = $baseUsername . $i++;
        }

        $authUserId = DB::table('auth_users')->insertGetId([
            'employee_id'   => null,
            'customer_id'   => $contact->customer_id,
            'contact_id'    => $contact->contact_id,
            'username'      => $username,
            'email'         => $email,
            'phone'         => $contact->cell_phone ?: null,
            'password'      => Hash::make(Str::random(32)),
            'is_active'     => true,
            'is_already_cp' => false,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        Log::channel('daily')->info('=== SELF-SERVE CONTACT LOGIN PROVISIONED ===', [
            'request_id'   => $requestId,
            'auth_user_id' => $authUserId,
            'customer_id'  => $contact->customer_id,
            'contact_id'   => $contact->contact_id,
            'email'        => $email,
        ]);

        return DB::table('auth_users')->where('id', $authUserId)->first();
    }
}
