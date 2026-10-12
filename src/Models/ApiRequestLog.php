<?php

namespace Euginepj\ApiLogger\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ApiRequestLog extends Model
{
    /**
     * Disable standard updated_at column since request logs are immutable.
     */
    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'api_request_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'method',
        'url',
        'path',
        'status_code',
        'duration_ms',
        'ip_address',
        'user_agent',
        'user_id',
        'device_name',
        'headers',
        'query_params',
        'payload',
        'response_headers',
        'response_body',
        'error_message',
        'created_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'duration_ms' => 'float',
            'user_id' => 'integer',
            'headers' => 'array',
            'query_params' => 'array',
            'payload' => 'array',
            'response_headers' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Scope for filtering by status code class (e.g., 2xx, 4xx, 5xx).
     */
    public function scopeStatusClass(Builder $query, string $class): Builder
    {
        return match ($class) {
            '2xx' => $query->whereBetween('status_code', [200, 299]),
            '3xx' => $query->whereBetween('status_code', [300, 399]),
            '4xx' => $query->whereBetween('status_code', [400, 499]),
            '5xx' => $query->whereBetween('status_code', [500, 599]),
            default => $query,
        };
    }

    /**
     * Scope for errors only (status >= 400).
     */
    public function scopeErrorsOnly(Builder $query): Builder
    {
        return $query->where('status_code', '>=', 400);
    }
}
