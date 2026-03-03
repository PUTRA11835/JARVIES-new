<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;

/**
 * Ecosystem API Service
 * 
 * Centralized service for all HTTP communications with Ecosystem backend
 * 
 * Features:
 * - Automatic token injection from session
 * - Response caching for performance
 * - Retry mechanism for failed requests
 * - Comprehensive error handling
 * - Request/response logging
 * 
 * Performance Optimizations:
 * - Configurable cache TTL per request
 * - Connection pooling via Laravel HTTP client
 * - Automatic retry with exponential backoff
 */
class EcosystemApiService
{
    /**
     * Base API URL
     */
    private string $baseUrl;

    /**
     * Request timeout in seconds
     */
    private int $timeout;

    /**
     * Retry configuration
     */
    private int $retryTimes;
    private int $retrySleep;

    /**
     * Constructor - Initialize from config
     */
    public function __construct()
    {
        $this->baseUrl = config('services.ecosystem.url');
        $this->timeout = config('services.ecosystem.timeout', 30);
        $this->retryTimes = config('services.ecosystem.retry.times', 3);
        $this->retrySleep = config('services.ecosystem.retry.sleep', 1000);
        
        if (empty($this->baseUrl)) {
            throw new \RuntimeException('ECOSYSTEM_API_URL not configured in .env');
        }
    }

    // ==================== PUBLIC API METHODS ====================

    /**
     * GET request with optional caching
     * 
     * @param string $endpoint API endpoint (e.g., '/tickets')
     * @param array $params Query parameters
     * @param int|null $cacheTtl Cache TTL in seconds (null = no cache)
     * @return array ['success' => bool, 'data' => mixed, 'status' => int, 'message' => string]
     */
    public function get(string $endpoint, array $params = [], ?int $cacheTtl = null): array
    {
        // Check cache if enabled
        if ($cacheTtl !== null) {
            $cacheKey = $this->getCacheKey('GET', $endpoint, $params);
            
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                Log::debug('API Cache HIT', [
                    'endpoint' => $endpoint,
                    'key' => substr($cacheKey, 0, 50) . '...'
                ]);
                return $cached;
            }
        }

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout($this->timeout)
                ->retry($this->retryTimes, $this->retrySleep)
                ->get($this->buildUrl($endpoint), $params);

            $result = $this->handleResponse($response, 'GET', $endpoint);

            // Cache successful responses only
            if ($cacheTtl !== null && $result['success']) {
                Cache::put($cacheKey, $result, $cacheTtl);
                Log::debug('API Cache STORE', ['endpoint' => $endpoint]);
            }

            return $result;
            
        } catch (ConnectionException $e) {
            return $this->handleConnectionError($endpoint, $e);
        } catch (\Exception $e) {
            return $this->handleGeneralError($endpoint, $e);
        }
    }

    /**
     * POST request
     */
    public function post(string $endpoint, array $data = []): array
    {
        try {
            Log::info('API POST Request', [
                'endpoint' => $endpoint,
                'data_size' => count($data)
            ]);

            $response = Http::withHeaders($this->getHeaders())
                ->timeout($this->timeout)
                ->post($this->buildUrl($endpoint), $data);

            return $this->handleResponse($response, 'POST', $endpoint);
            
        } catch (ConnectionException $e) {
            return $this->handleConnectionError($endpoint, $e);
        } catch (\Exception $e) {
            return $this->handleGeneralError($endpoint, $e);
        }
    }

    /**
     * PUT request
     */
    public function put(string $endpoint, array $data = []): array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout($this->timeout)
                ->put($this->buildUrl($endpoint), $data);

            return $this->handleResponse($response, 'PUT', $endpoint);
            
        } catch (ConnectionException $e) {
            return $this->handleConnectionError($endpoint, $e);
        } catch (\Exception $e) {
            return $this->handleGeneralError($endpoint, $e);
        }
    }

    /**
     * DELETE request
     */
    public function delete(string $endpoint): array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout($this->timeout)
                ->delete($this->buildUrl($endpoint));

            return $this->handleResponse($response, 'DELETE', $endpoint);
            
        } catch (ConnectionException $e) {
            return $this->handleConnectionError($endpoint, $e);
        } catch (\Exception $e) {
            return $this->handleGeneralError($endpoint, $e);
        }
    }

    // ==================== HELPER METHODS ====================

    /**
     * Build full URL
     */
    private function buildUrl(string $endpoint): string
    {
        return rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');
    }

    /**
     * Get HTTP headers with authentication
     */
    private function getHeaders(): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ];

        // Auto-inject token from session
        $token = session('api_token');
        if ($token) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        return $headers;
    }

    /**
     * Handle API response
     */
    private function handleResponse($response, string $method, string $endpoint): array
    {
        $statusCode = $response->status();
        $isSuccessful = $response->successful();

        Log::info('API Response', [
            'method' => $method,
            'endpoint' => $endpoint,
            'status' => $statusCode,
            'success' => $isSuccessful
        ]);

        // Success (2xx)
        if ($isSuccessful) {
            return [
                'success' => true,
                'data' => $response->json(),
                'status' => $statusCode,
                'message' => 'Success'
            ];
        }

        // 401 Unauthorized - EcoSystem API token expired (not JARVIES session)
        if ($statusCode === 401) {
            Log::warning('API 401 Unauthorized - Token expired or invalid', [
                'endpoint' => $endpoint
            ]);

            // Only forget the external api_token, NOT 'user' (that is JARVIES session data)
            session()->forget('api_token');

            return [
                'success' => false,
                'data' => null,
                'status' => 401,
                'message' => 'Your session has expired. Please login again.',
                'redirect' => route('login')
            ];
        }

        // Other HTTP errors (4xx, 5xx)
        $errorData = $response->json();
        $errorMessage = $errorData['message'] ?? $this->getDefaultErrorMessage($statusCode);
        
        Log::error('API Error Response', [
            'method' => $method,
            'endpoint' => $endpoint,
            'status' => $statusCode,
            'message' => $errorMessage
        ]);

        return [
            'success' => false,
            'data' => $errorData,
            'status' => $statusCode,
            'message' => $errorMessage,
        ];
    }

    /**
     * Handle connection errors
     */
    private function handleConnectionError(string $endpoint, ConnectionException $e): array
    {
        Log::error('API Connection Failed', [
            'endpoint' => $endpoint,
            'error' => $e->getMessage()
        ]);
        
        return [
            'success' => false,
            'data' => null,
            'status' => 0,
            'message' => 'Cannot connect to server. Please ensure Ecosystem API is running at ' . $this->baseUrl
        ];
    }

    /**
     * Handle general errors
     */
    private function handleGeneralError(string $endpoint, \Exception $e): array
    {
        Log::error('API Request Exception', [
            'endpoint' => $endpoint,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return [
            'success' => false,
            'data' => null,
            'status' => 500,
            'message' => 'Request failed: ' . $e->getMessage()
        ];
    }

    /**
     * Get default error message based on status code
     */
    private function getDefaultErrorMessage(int $statusCode): string
    {
        return match(true) {
            $statusCode === 400 => 'Bad request',
            $statusCode === 403 => 'Access forbidden',
            $statusCode === 404 => 'Resource not found',
            $statusCode === 422 => 'Validation failed',
            $statusCode >= 500 => 'Server error occurred',
            default => 'An error occurred'
        };
    }

    /**
     * Generate cache key
     */
    private function getCacheKey(string $method, string $endpoint, array $params = []): string
    {
        $userId = session('user.id', 'guest');
        
        return sprintf(
            'jarvies_api_%s_%s_%s_%s',
            $userId,
            $method,
            md5($endpoint),
            md5(json_encode($params))
        );
    }

    /**
     * Clear all API cache
     */
    public function clearCache(): void
    {
        // Note: Laravel's file cache driver doesn't support wildcard deletion
        // For production, consider using Redis with cache tags
        
        Cache::flush();
        
        Log::info('API Cache cleared', [
            'user_id' => session('user.id', 'guest')
        ]);
    }

    /**
     * Test API connection (for debugging)
     */
    public function testConnection(): array
    {
        try {
            $testUrl = str_replace('/api', '/test', $this->baseUrl);
            
            $response = Http::timeout(5)->get($testUrl);
            
            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'message' => $response->successful() ? 'Connection OK' : 'Connection Failed',
                'url' => $testUrl
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status' => 0,
                'message' => 'Connection test failed: ' . $e->getMessage(),
                'url' => $this->baseUrl
            ];
        }
    }
}