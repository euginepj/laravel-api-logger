<?php

namespace Euginepj\ApiLogger\Http\Middleware;

use Closure;
use Euginepj\ApiLogger\Models\ApiRequestLog;
use Euginepj\ApiLogger\Support\PayloadSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LogApiRequests
{
    /**
     * Start timestamp of the request.
     */
    protected float $startTime;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->startTime = defined('LARAVEL_START') ? LARAVEL_START : microtime(true);

        return $next($request);
    }

    /**
     * Terminate request lifecycle and record log asynchronously after response was sent.
     */
    public function terminate(Request $request, Response $response): void
    {
        if (! config('api-logger.enabled', true)) {
            return;
        }

        // Check if path is excluded
        $path = $request->path();
        $exceptPaths = config('api-logger.except_paths', []);
        foreach ($exceptPaths as $except) {
            if ($request->is($except)) {
                return;
            }
        }

        $statusCode = $response->getStatusCode();
        $onlyErrors = config('api-logger.only_errors', false);
        if ($onlyErrors && $statusCode < 400) {
            return;
        }

        $durationMs = round((microtime(true) - ($this->startTime ?? microtime(true))) * 1000, 2);

        try {
            $this->recordLog($request, $response, $statusCode, $durationMs);
        } catch (Throwable $e) {
            // Fail silently to avoid breaking the client response
            Log::channel('daily')->warning('API Logger failed to record log: '.$e->getMessage(), [
                'exception' => $e,
            ]);
        }
    }

    /**
     * Record request and response details.
     */
    protected function recordLog(Request $request, Response $response, int $statusCode, float $durationMs): void
    {
        $hiddenFields = config('api-logger.hidden_fields', []);
        $maxBodyLength = (int) config('api-logger.max_body_length', 65535);

        // Sanitize request data
        $sanitizedPayload = PayloadSanitizer::cleanPayload($request->all(), $hiddenFields);
        $sanitizedHeaders = PayloadSanitizer::cleanHeaders($request->headers->all(), $hiddenFields);
        $queryParams = $request->query();

        // Response body processing
        $rawResponseBody = $response->getContent();
        $responseBody = PayloadSanitizer::formatResponseBody($rawResponseBody, $maxBodyLength);

        // Extract error message if response indicates error
        $errorMessage = null;
        if ($statusCode >= 400) {
            $decoded = json_decode($rawResponseBody, true);
            if (is_array($decoded)) {
                $errorMessage = $decoded['message'] ?? $decoded['error'] ?? null;
            }
        }

        $deviceName = $request->header('X-Device-Name')
            ?? $request->header('x-device-name')
            ?? $request->input('device_name')
            ?? null;

        $logData = [
            'method' => strtoupper($request->method()),
            'url' => $request->fullUrl(),
            'path' => '/'.ltrim($request->path(), '/'),
            'status_code' => $statusCode,
            'duration_ms' => $durationMs,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'user_id' => $request->user()?->getAuthIdentifier(),
            'device_name' => is_string($deviceName) ? substr($deviceName, 0, 100) : null,
            'headers' => $sanitizedHeaders,
            'query_params' => ! empty($queryParams) ? $queryParams : null,
            'payload' => ! empty($sanitizedPayload) ? $sanitizedPayload : null,
            'response_headers' => $response->headers->all(),
            'response_body' => $responseBody,
            'error_message' => $errorMessage,
            'created_at' => now(),
        ];

        $driver = config('api-logger.driver', 'database');

        if (in_array($driver, ['database', 'both'], true)) {
            ApiRequestLog::create($logData);
        }

        if (in_array($driver, ['file', 'both'], true)) {
            $channel = config('api-logger.log_channel', 'daily');
            Log::channel($channel)->info("API Request Log: [{$logData['method']}] {$logData['path']} ({$statusCode})", $logData);
        }
    }
}
