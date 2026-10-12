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
            $status = $request->string('status');
            if (in_array($status, ['2xx', '3xx', '4xx', '5xx'], true)) {
                $query->statusClass($status);
            } elseif (is_numeric($status)) {
                $query->where('status_code', (int) $status);
            }
        }

        // Search filter (path, IP, user_id, or error_message)
        if ($request->filled('search')) {
            $search = trim($request->string('search'));
            $query->where(function ($q) use ($search) {
                $q->where('path', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('device_name', 'like', "%{$search}%")
                    ->orWhere('error_message', 'like', "%{$search}%")
                    ->orWhere('url', 'like', "%{$search}%");
                if (is_numeric($search)) {
                    $q->orWhere('user_id', (int) $search);
                }
            });
        }

        // Date filter
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->string('date'));
        }

        $perPage = (int) config('api-logger.dashboard.per_page', 25);
        $logs = $query->paginate($perPage)->withQueryString();

        // Calculate summary metrics
        $totalCount = ApiRequestLog::count();
        $todayCount = ApiRequestLog::whereDate('created_at', now()->toDateString())->count();
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
