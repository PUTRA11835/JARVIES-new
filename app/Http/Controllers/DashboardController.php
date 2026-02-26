<?php

namespace App\Http\Controllers;

use App\Services\EcosystemApiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Dashboard Controller for Jarvies Portal
 * 
 * Displays overview statistics and metrics
 * Fetches data from Ecosystem API with intelligent caching
 * 
 * Performance:
 * - Statistics cached for 5 minutes
 * - Reduces API calls by ~80%
 * - Fallback to empty data on API failure
 */
class DashboardController extends Controller
{
    /**
     * Cache TTL in seconds (5 minutes)
     */
    private const CACHE_TTL = 300;

    /**
     * Ecosystem API Service
     */
    private EcosystemApiService $api;

    /**
     * Constructor - Inject API service
     */
    public function __construct(EcosystemApiService $api)
    {
        $this->api = $api;
    }

    /**
     * Display dashboard
     */
    public function index(): View
    {
        $user = session('user');
        $roleId = $this->extractRoleId($user);
        $userId = $user['id'];

        Log::info('Dashboard accessed', [
            'user_id' => $userId,
            'role_id' => $roleId
        ]);

        // Fetch statistics with caching
        $statistics = $this->getStatistics($roleId, $userId);

        return view('dashboard', [
            'user' => $user,
            'stats' => $statistics['overview'],
            'unresolvedTickets' => $statistics['unresolved_by_group'],
            'satisfaction' => $statistics['satisfaction'],
            'todos' => $statistics['todos'],
            'trendStats' => $statistics['trends'],
        ]);
    }

    /**
     * Get statistics via AJAX
     */
    public function getStats(): JsonResponse
    {
        $user = session('user');
        $roleId = $this->extractRoleId($user);
        $userId = $user['id'];

        $statistics = $this->getStatistics($roleId, $userId);

        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }

    /**
     * Get chart data via AJAX
     */
    public function getChartData(Request $request): JsonResponse
    {
        $chartType = $request->input('type', 'tickets_trend');
        $userId = session('user.id');

        // Validate chart type
        if (!$this->isValidChartType($chartType)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid chart type'
            ], 400);
        }

        // Fetch with caching
        $cacheKey = "jarvies_chart_{$chartType}_{$userId}";
        
        $chartData = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($chartType) {
            return $this->fetchChartData($chartType);
        });

        return response()->json([
            'success' => true,
            'data' => $chartData
        ]);
    }

    // ==================== PRIVATE HELPER METHODS ====================

    /**
     * Extract role ID from user data
     */
    private function extractRoleId(array $user): int
    {
        return $user['role']['id'] 
            ?? $user['role_id'] 
            ?? 3; // Default to customer
    }

    /**
     * Get statistics from Ecosystem API with caching
     */
    private function getStatistics(int $roleId, int $userId): array
    {
        $cacheKey = "jarvies_dashboard_stats_{$roleId}_{$userId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($roleId) {
            // Fetch from Ecosystem API
            $response = $this->api->get('/tickets/statistics', [], self::CACHE_TTL);

            if ($response['success']) {
                // Extract data (handle nested structure)
                $apiStats = $response['data']['data'] ?? $response['data'];
                
                return $this->formatStatistics($apiStats);
            }

            // Log failure and return empty stats
            Log::warning('Failed to fetch dashboard statistics', [
                'status' => $response['status'] ?? 'unknown',
                'message' => $response['message'] ?? 'unknown'
            ]);

            return $this->getEmptyStatistics();
        });
    }

    /**
     * Format API statistics for dashboard display
     */
    private function formatStatistics(array $apiStats): array
    {
        return [
            'overview' => [
                'unresolved' => (int) ($apiStats['in_process'] ?? 0),
                'overdue' => (int) ($apiStats['overdue'] ?? 0),
                'due_today' => (int) ($apiStats['due_today'] ?? 0),
                'open' => (int) ($apiStats['open'] ?? 0),
                'on_hold' => (int) ($apiStats['on_hold'] ?? 0),
                'unassigned' => (int) ($apiStats['unassigned'] ?? 0),
            ],
            'unresolved_by_group' => $this->formatGroupTickets($apiStats['by_group'] ?? []),
            'satisfaction' => [
                'total_responses' => (int) ($apiStats['satisfaction']['total'] ?? 0),
                'positive' => (int) ($apiStats['satisfaction']['positive'] ?? 0),
                'neutral' => (int) ($apiStats['satisfaction']['neutral'] ?? 0),
                'negative' => (int) ($apiStats['satisfaction']['negative'] ?? 0),
            ],
            'todos' => $this->formatTodos($apiStats['todos'] ?? []),
            'trends' => [
                'resolved' => (int) ($apiStats['trends']['resolved'] ?? 0),
                'received' => (int) ($apiStats['trends']['received'] ?? 0),
                'avg_response' => $apiStats['trends']['avg_response'] ?? '0m',
                'sla_percentage' => (int) ($apiStats['trends']['sla_percentage'] ?? 0),
            ],
        ];
    }

    /**
     * Format group tickets data
     */
    private function formatGroupTickets(array $groups): array
    {
        if (empty($groups)) {
            return [];
        }

        return array_map(function ($group) {
            return [
                'group' => $group['name'] ?? 'Unknown',
                'count' => (int) ($group['count'] ?? 0),
            ];
        }, $groups);
    }

    /**
     * Format todos data
     */
    private function formatTodos(array $todos): array
    {
        if (empty($todos)) {
            return [];
        }

        return array_map(function ($todo) {
            return [
                'title' => $todo['title'] ?? 'Task',
                'description' => $todo['description'] ?? '',
                'priority' => $todo['priority'] ?? 'medium',
                'due' => $todo['due'] ?? 'No deadline',
            ];
        }, $todos);
    }

    /**
     * Get empty statistics (fallback)
     */
    private function getEmptyStatistics(): array
    {
        return [
            'overview' => [
                'unresolved' => 0,
                'overdue' => 0,
                'due_today' => 0,
                'open' => 0,
                'on_hold' => 0,
                'unassigned' => 0,
            ],
            'unresolved_by_group' => [],
            'satisfaction' => [
                'total_responses' => 0,
                'positive' => 0,
                'neutral' => 0,
                'negative' => 0,
            ],
            'todos' => [],
            'trends' => [
                'resolved' => 0,
                'received' => 0,
                'avg_response' => '0m',
                'sla_percentage' => 0,
            ],
        ];
    }

    /**
     * Fetch chart data from API
     */
    private function fetchChartData(string $chartType): array
    {
        $response = $this->api->get("/dashboard/charts/{$chartType}");

        if ($response['success']) {
            return $response['data']['data'] ?? $response['data'] ?? [];
        }

        Log::warning('Failed to fetch chart data', [
            'chart_type' => $chartType,
            'message' => $response['message'] ?? 'unknown'
        ]);

        return [];
    }

    /**
     * Validate chart type
     */
    private function isValidChartType(string $type): bool
    {
        $validTypes = [
            'tickets_trend',
            'satisfaction_trend',
            'response_time',
            'resolution_time',
            'tickets_by_priority',
            'tickets_by_status',
        ];

        return in_array($type, $validTypes, true);
    }
}