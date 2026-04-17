<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Proxy controller — forwards /api/customers/{id}/... requests from
 * JARVIES frontend to EcoSystem API.
 *
 * Security: only the currently logged-in customer can access their own data
 * (session id must match the requested customerId).
 */
class CustomerApiController extends Controller
{
    private function ecosystemBase(): string
    {
        // config('ecosystem.url') = ECOSYSTEM_URL from .env (full API base, e.g. https://ecosystem.domain.com/api)
        return rtrim(config('ecosystem.url') ?: config('services.ecosystem.url', ''), '/');
    }

    private function apiKey(): ?string
    {
        return config('ecosystem.api_key') ?: env('ECOSYSTEM_API_KEY') ?: env('EXTERNAL_TICKET_API_KEY');
    }

    /**
     * Return 403 if the session customer does not match the URL parameter.
     */
    private function authorize(int $customerId): bool
    {
        return (int) session('user.id') === $customerId;
    }

    /**
     * Forward a GET request to EcoSystem and return the JSON response.
     */
    private function proxy(string $path): JsonResponse
    {
        try {
            $response = Http::withHeaders([
                'Accept'    => 'application/json',
                'X-Api-Key' => $this->apiKey(),
            ])->timeout(10)->get($this->ecosystemBase() . $path);

            return response()->json(
                $response->json(),
                $response->status()
            );
        } catch (\Throwable $e) {
            Log::error('CustomerApiController proxy failed', [
                'path'  => $path,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => 'Service unavailable'], 503);
        }
    }

    // =========================================================================
    // Contacts
    // =========================================================================

    public function contacts(int $customerId): JsonResponse
    {
        if (!$this->authorize($customerId)) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        return $this->proxy("/jarvies/customers/{$customerId}/contacts");
    }

    public function contact(int $customerId, int $contactId): JsonResponse
    {
        if (!$this->authorize($customerId)) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        return $this->proxy("/jarvies/customers/{$customerId}/contacts/{$contactId}");
    }

    // =========================================================================
    // Addresses
    // =========================================================================

    public function addresses(int $customerId): JsonResponse
    {
        if (!$this->authorize($customerId)) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        return $this->proxy("/jarvies/customers/{$customerId}/addresses");
    }

    public function address(int $customerId, int $addressId): JsonResponse
    {
        if (!$this->authorize($customerId)) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        return $this->proxy("/jarvies/customers/{$customerId}/addresses/{$addressId}");
    }

    // =========================================================================
    // Identifications
    // =========================================================================

    public function identifications(int $customerId): JsonResponse
    {
        if (!$this->authorize($customerId)) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        return $this->proxy("/jarvies/customers/{$customerId}/identifications");
    }

    public function identification(int $customerId, int $identificationId): JsonResponse
    {
        if (!$this->authorize($customerId)) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        return $this->proxy("/jarvies/customers/{$customerId}/identifications/{$identificationId}");
    }

    // =========================================================================
    // Banks
    // =========================================================================

    public function banks(int $customerId): JsonResponse
    {
        if (!$this->authorize($customerId)) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        return $this->proxy("/jarvies/customers/{$customerId}/banks");
    }

    public function bank(int $customerId, int $bankId): JsonResponse
    {
        if (!$this->authorize($customerId)) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        return $this->proxy("/jarvies/customers/{$customerId}/banks/{$bankId}");
    }

    // =========================================================================
    // Attachments
    // =========================================================================

    public function attachments(int $customerId): JsonResponse
    {
        if (!$this->authorize($customerId)) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        return $this->proxy("/jarvies/customers/{$customerId}/attachments");
    }

    public function attachment(int $customerId, int $attachmentId): JsonResponse
    {
        if (!$this->authorize($customerId)) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        return $this->proxy("/jarvies/customers/{$customerId}/attachments/{$attachmentId}");
    }
}
