<?php

namespace Euginepj\ApiLogger\Support;

use Illuminate\Http\UploadedFile;

class PayloadSanitizer
{
    /**
     * Sanitize input payload (masking passwords, secrets, tokens).
     */
    public static function cleanPayload(mixed $data, array $hiddenKeys = []): mixed
    {
        if (empty($hiddenKeys)) {
            $hiddenKeys = config('api-logger.hidden_fields', [
                'password',
                'password_confirmation',
                'current_password',
                'token',
                'secret',
                'authorization',
            ]);
        }

        if ($data instanceof UploadedFile) {
            return [
                '__type' => 'UploadedFile',
                'name' => $data->getClientOriginalName(),
                'mime' => $data->getClientMimeType(),
                'size_kb' => round($data->getSize() / 1024, 2),
            ];
        }

        if (is_array($data)) {
            $cleaned = [];
            foreach ($data as $key => $value) {
                if (is_string($key) && self::matchesHiddenPattern($key, $hiddenKeys)) {
                    $cleaned[$key] = '********';
                } elseif ($value instanceof UploadedFile) {
                    $cleaned[$key] = [
                        '__type' => 'UploadedFile',
                        'name' => $value->getClientOriginalName(),
                        'mime' => $value->getClientMimeType(),
                        'size_kb' => round($value->getSize() / 1024, 2),
                    ];
                } elseif (is_array($value)) {
                    $cleaned[$key] = self::cleanPayload($value, $hiddenKeys);
                } else {
                    $cleaned[$key] = $value;
                }
            }

            return $cleaned;
        }

        return $data;
    }

    /**
     * Sanitize request headers.
     */
    public static function cleanHeaders(array $headers, array $hiddenKeys = []): array
    {
        $cleaned = [];
        foreach ($headers as $key => $values) {
            $lowerKey = strtolower($key);
            if (in_array($lowerKey, ['authorization', 'php-auth-pw', 'cookie', 'x-api-key', 'x-xsrf-token', 'token'])) {
                $cleaned[$key] = ['********'];
            } else {
                $cleaned[$key] = $values;
            }
        }

        return $cleaned;
    }

    /**
     * Format and limit response body.
     */
    public static function formatResponseBody(mixed $content, int $maxLength = 65535): ?string
    {
        if ($content === null || $content === '') {
            return null;
        }

        if (is_array($content) || is_object($content)) {
            $content = json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        if (! is_string($content)) {
            $content = (string) $content;
        }

        if (mb_strlen($content) > $maxLength) {
            return mb_substr($content, 0, $maxLength)."\n... [TRUNCATED - Payload exceeded {$maxLength} characters]";
        }

        return $content;
    }

    /**
     * Check if a field key matches any of the hidden field patterns.
     */
    protected static function matchesHiddenPattern(string $key, array $patterns): bool
    {
        $lowerKey = strtolower($key);
        foreach ($patterns as $pattern) {
            $lowerPattern = strtolower($pattern);
            if ($lowerKey === $lowerPattern || str_contains($lowerKey, $lowerPattern)) {
                return true;
            }
        }

        return false;
    }
}
