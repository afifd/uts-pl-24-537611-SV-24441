<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class AuditLogger
{
    /**
     * Keys that must never be recorded in logs.
     */
    protected static array $sensitiveKeys = [
        'password',
        'password_confirmation',
        'token',
        'access_token',
        'refresh_token',
        'remember_token',
        'authorization',
        'bearer',
        'secret',
        'api_key',
        'api_token',
        'pin',
        'cvv',
        'credit_card',
        'private_key',
    ];

    /**
     * Log an informative structured event.
     */
    public static function info(string $message, array $context): void
    {
        self::log('info', $message, $context);
    }

    /**
     * Log a warning structured event.
     */
    public static function warning(string $message, array $context): void
    {
        self::log('warning', $message, $context);
    }

    /**
     * Log an error structured event.
     */
    public static function error(string $message, array $context): void
    {
        self::log('error', $message, $context);
    }

    /**
     * Core structured logging method.
     */
    public static function log(string $level, string $message, array $context): void
    {
        $sanitizedContext = self::sanitize($context);

        // Ensure baseline context contracts (who, what, why)
        $structured = [
            'who' => $sanitizedContext['who'] ?? self::resolveWho(),
            'what' => $sanitizedContext['what'] ?? ['action' => 'unspecified'],
            'why' => $sanitizedContext['why'] ?? ['reason' => 'unspecified'],
        ];

        // Merge any extra metadata
        foreach ($sanitizedContext as $k => $v) {
            if (!in_array($k, ['who', 'what', 'why'], true)) {
                $structured['metadata'][$k] = $v;
            }
        }

        Log::$level($message, $structured);
    }

    /**
     * Resolve default actor information.
     */
    public static function resolveWho(): array
    {
        $userId = auth()->id() ?? 'unauthenticated';
        $ip = request()?->ip() ?? '127.0.0.1';
        $userAgent = request()?->userAgent() ?? 'internal/system';

        return [
            'actor' => $userId,
            'client_ip' => $ip,
            'user_agent' => $userAgent,
        ];
    }

    /**
     * Recursively sanitize context data to remove any passwords, tokens, or PII.
     */
    public static function sanitize(mixed $data): mixed
    {
        if (is_string($data)) {
            return preg_replace('/(password|pwd|secret)=([^&;\s]+)/i', '$1=[REDACTED]', $data);
        }

        if (!is_array($data)) {
            return $data;
        }

        $sanitized = [];
        foreach ($data as $key => $value) {
            $lowerKey = is_string($key) ? strtolower($key) : $key;

            if (is_string($lowerKey) && self::isSensitiveKey($lowerKey)) {
                $sanitized[$key] = '[REDACTED]';
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = self::sanitize($value);
            } elseif (is_string($value)) {
                $sanitized[$key] = preg_replace('/(password|pwd|secret)=([^&;\s]+)/i', '$1=[REDACTED]', $value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Check if a given key matches sensitive patterns.
     */
    protected static function isSensitiveKey(string $key): bool
    {
        foreach (self::$sensitiveKeys as $pattern) {
            if (str_contains($key, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
