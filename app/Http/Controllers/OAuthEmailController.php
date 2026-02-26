<?php

namespace App\Http\Controllers;

use App\Models\CustomerEmailToken;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class OAuthEmailController extends Controller
{
    private const ALLOWED_PROVIDERS = ['google', 'azure'];

    /**
     * GET /oauth/email/status
     * Return the currently linked email account for this customer (JSON).
     */
    public function status()
    {
        $customerId = session('user.id');

        if (!$customerId) {
            return response()->json(['linked' => false]);
        }

        $token = CustomerEmailToken::where('customer_id', $customerId)->first();

        if (!$token) {
            return response()->json(['linked' => false]);
        }

        return response()->json([
            'linked'   => true,
            'provider' => $token->provider,
            'email'    => $token->provider_email,
            'expired'  => $token->isExpired() && !$token->refresh_token,
        ]);
    }

    /**
     * GET /oauth/email/redirect/{provider}
     * Kick off the OAuth flow for the given provider.
     */
    public function redirect(string $provider)
    {
        if (!in_array($provider, self::ALLOWED_PROVIDERS)) {
            abort(404);
        }

        // Store return intent in session
        session(['oauth_email_intent' => 'link_email', 'oauth_email_provider' => $provider]);

        return match ($provider) {
            'google' => Socialite::driver('google')
                ->scopes(['https://www.googleapis.com/auth/gmail.send'])
                ->with(['access_type' => 'offline', 'prompt' => 'consent'])
                ->redirect(),
            'azure'  => Socialite::driver('azure')
                ->scopes(['https://graph.microsoft.com/Mail.Send', 'offline_access'])
                ->redirect(),
        };
    }

    /**
     * GET /oauth/email/callback/{provider}
     * Handle OAuth callback, save token to DB.
     */
    public function callback(string $provider)
    {
        if (!in_array($provider, self::ALLOWED_PROVIDERS)) {
            abort(404);
        }

        $customerId = session('user.id');

        if (!$customerId) {
            return redirect()->route('login')->with('error', 'Please login first.');
        }

        try {
            $guzzle     = new GuzzleClient(['timeout' => 15, 'connect_timeout' => 5]);
            $socialUser = Socialite::driver($provider)->setHttpClient($guzzle)->user();

            CustomerEmailToken::updateOrCreate(
                ['customer_id' => $customerId, 'provider' => $provider],
                [
                    'provider_email'    => $socialUser->getEmail(),
                    'provider_user_id'  => $socialUser->getId(),
                    'access_token'      => $socialUser->token,
                    'refresh_token'     => $socialUser->refreshToken ?? null,
                    'token_expires_at'  => $socialUser->expiresIn
                        ? now()->addSeconds($socialUser->expiresIn)
                        : null,
                ]
            );

            Log::info('OAuthEmailController: token saved', [
                'customer_id' => $customerId,
                'provider'    => $provider,
                'email'       => $socialUser->getEmail(),
            ]);

            session()->forget(['oauth_email_intent', 'oauth_email_provider']);

            return redirect()->route('tickets.create')
                ->with('oauth_success', "Your {$provider} account has been linked successfully.");

        } catch (\Throwable $e) {
            Log::error('OAuthEmailController@callback failed', [
                'provider' => $provider,
                'error'    => $e->getMessage(),
            ]);

            return redirect()->route('tickets.create')
                ->with('oauth_error', 'Failed to link account: ' . $e->getMessage());
        }
    }

    /**
     * DELETE /oauth/email/disconnect
     * Remove the stored token so the customer can re-link a different account.
     */
    public function disconnect(Request $request)
    {
        $customerId = session('user.id');

        if (!$customerId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        CustomerEmailToken::where('customer_id', $customerId)->delete();

        Log::info('OAuthEmailController: token disconnected', ['customer_id' => $customerId]);

        return response()->json(['success' => true, 'message' => 'Email account disconnected.']);
    }
}
