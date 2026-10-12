<?php

return [
    /*
     * Enable or disable API logging globally.
     */
    'enabled' => env('API_LOGGER_ENABLED', true),

    /*
     * Whether to log only error responses (status >= 400).
     * Set to false to log all calls (200, 201, 4xx, 5xx).
     */
    'only_errors' => env('API_LOGGER_ONLY_ERRORS', false),

    /*
     * Log driver to use: 'database', 'file', or 'both'.
     */
    'driver' => env('API_LOGGER_DRIVER', 'database'),

    /*
     * Dedicated log channel if using 'file' or 'both'.
     */
    'log_channel' => env('API_LOGGER_CHANNEL', 'daily'),

    /*
     * Sensitive fields that should be masked/redacted in headers and payloads.
     */
    'hidden_fields' => [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'token',
        'access_token',
        'refresh_token',
        'secret',
        'client_secret',
        'api_key',
        'authorization',
        'x-api-key',
        'credit_card',
        'cvv',
        'card_number',
    ],

    /*
     * Request paths or patterns to exclude from logging.
     */
    'except_paths' => [
        'api/v1/health',
        'up',
    ],

    /*
     * Maximum character length for recording response body (prevents log bloat).
     */
    'max_body_length' => env('API_LOGGER_MAX_BODY_LENGTH', 65535),

    /*
     * Number of days to retain logs before pruning.
     */
    'prune_days' => env('API_LOGGER_PRUNE_DAYS', 30),

    /*
     * Dashboard configuration.
     */
    'dashboard' => [
        'enabled' => env('API_LOGGER_DASHBOARD_ENABLED', true),
        'prefix' => 'admin/api-logs',
        'middleware' => ['web', 'auth', 'role:Admin|Staff'],
        'per_page' => 25,
    ],
];
