<?php

namespace Euginepj\ApiLogger\Http\Controllers;

use Euginepj\ApiLogger\Models\ApiRequestLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class ApiLogViewerController extends Controller
{
    /**
     * Display a paginated list of API logs with metrics and filtering.
     */
    public function index(Request $request): View
    {
        $query = ApiRequestLog::query()->latest('id');

        // Method filter
        if ($request->filled('method')) {
            $query->where('method', strtoupper($request->string('method')));
        }

        // Status filter
        if ($request->filled('status')) {
            $status = (string) $request->string('status');
            if ($status === 'errors') {
                $query->where('status_code', '>=', 400);
            } elseif (in_array($status, ['2xx', '3xx', '4xx', '5xx'], true)) {
                $query->statusClass($status);
            } elseif (is_numeric($status)) {
                $query->where('status_code', (int) $status);
            }
        }

        // Search filter (path, IP, user_id, device, error_message, url, or structured operators)
        if ($request->filled('search')) {
            $search = trim((string) $request->string('search'));

            // Check for user-specific search (e.g., #35, user:35, or u:35)
            if (preg_match('/^(?:user:|u:)?#?(\d+)$/i', $search, $matches)) {
                $userId = (int) $matches[1];
                $query->where(function ($q) use ($userId, $search) {
                    $q->where('user_id', $userId)
                        ->orWhere('path', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%");
                });
            } elseif (preg_match('/^ip:(.+)$/i', $search, $matches)) {
                $query->where('ip_address', trim($matches[1]));
            } elseif (preg_match('/^status:(\d+|errors|2xx|3xx|4xx|5xx)$/i', $search, $matches)) {
                $targetStatus = strtolower(trim($matches[1]));
                if ($targetStatus === 'errors') {
                    $query->where('status_code', '>=', 400);
                } elseif (in_array($targetStatus, ['2xx', '3xx', '4xx', '5xx'], true)) {
                    $query->statusClass($targetStatus);
                } elseif (is_numeric($targetStatus)) {
                    $query->where('status_code', (int) $targetStatus);
                }
            } elseif (preg_match('/^method:(get|post|put|patch|delete)$/i', $search, $matches)) {
                $query->where('method', strtoupper($matches[1]));
            } else {
                $cleanSearch = ltrim($search, '#');
                $query->where(function ($q) use ($search, $cleanSearch) {
                    $q->where('path', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%")
                        ->orWhere('device_name', 'like', "%{$search}%")
                        ->orWhere('error_message', 'like', "%{$search}%")
                        ->orWhere('url', 'like', "%{$search}%");

                    if (is_numeric($cleanSearch)) {
                        $q->orWhere('user_id', (int) $cleanSearch);
                    }
                });
            }
        }

        // Slow requests filter (> 1000ms)
        if ($request->boolean('slow')) {
            $query->where('duration_ms', '>=', 1000);
        }

        // Date filter
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->string('date'));
        }

        $perPage = (int) config('api-logger.dashboard.per_page', 25);
        $logs = $query->paginate($perPage)->withQueryString();

        // Calculate summary metrics
        $totalCount = ApiRequestLog::count();
        $todayCount = ApiRequestLog::whereDate('created_at', now()->utc()->toDateString())->count();
        $errorCount = ApiRequestLog::where('status_code', '>=', 400)->count();
        $errorRate = $totalCount > 0 ? round(($errorCount / $totalCount) * 100, 1) : 0;
        $avgDuration = round((float) ApiRequestLog::avg('duration_ms'), 1);

        $metrics = [
            'total_requests' => $totalCount,
            'today_requests' => $todayCount,
            'error_rate' => $errorRate,
            'avg_duration_ms' => $avgDuration,
        ];

        return view('api-logger::index', compact('logs', 'metrics'));
    }

    /**
     * Get single log details (JSON for modal preview).
     */
    public function show(int $id): JsonResponse
    {
        $log = ApiRequestLog::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $log,
        ]);
    }

    /**
     * Clear all recorded API logs.
     */
    public function clear(): RedirectResponse
    {
        ApiRequestLog::truncate();

        return redirect()->route('admin.api-logs.index')->with('success', 'All API logs have been cleared successfully.');
    }
}
