<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthController extends Controller
{
    /**
     * Perform deep health check verifying Database and Cache systems.
     */
    public function check(Request $request): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
        ];

        $isHealthy = collect($checks)->every(fn ($check) => $check['status'] === 'ok');
        $httpStatus = $isHealthy ? 200 : 503;
        $overallStatus = $isHealthy ? 'healthy' : 'unhealthy';

        if (!$isHealthy) {
            $failedChecks = collect($checks)
                ->filter(fn ($check) => $check['status'] !== 'ok')
                ->keys()
                ->all();

            AuditLogger::error('System healthcheck degraded', [
                'who' => [
                    'actor' => auth()->id() ?? 'system/healthcheck',
                    'client_ip' => $request->ip(),
                    'user_agent' => $request->userAgent() ?? 'internal/probe',
                ],
                'what' => [
                    'action' => 'health_check',
                    'failed_subsystems' => $failedChecks,
                    'details' => $checks,
                ],
                'why' => [
                    'reason' => 'Subsystem probe failed on: ' . implode(', ', $failedChecks),
                ],
            ]);
        }

        return response()->json([
            'status' => $overallStatus,
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ], $httpStatus);
    }

    /**
     * Active probe on PostgreSQL database.
     */
    protected function checkDatabase(): array
    {
        $start = microtime(true);

        try {
            DB::connection()->getPdo();
            $result = DB::select('SELECT 1 as ping');

            if (empty($result) || !isset($result[0]->ping) || (int) $result[0]->ping !== 1) {
                throw new \RuntimeException('Database returned invalid ping result.');
            }

            $latency = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'ok',
                'latency_ms' => $latency,
                'connection' => config('database.default'),
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'failed',
                'error' => AuditLogger::sanitize($e->getMessage()),
            ];
        }
    }

    /**
     * Active read/write/delete probe on configured Cache store.
     */
    protected function checkCache(): array
    {
        $start = microtime(true);
        $probeKey = 'health_probe_' . bin2hex(random_bytes(4));
        $probeVal = 'probe_' . uniqid();

        try {
            Cache::put($probeKey, $probeVal, 10);
            $retrieved = Cache::get($probeKey);
            Cache::forget($probeKey);

            if ($retrieved !== $probeVal) {
                throw new \RuntimeException('Cache write/read value mismatch.');
            }

            $latency = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'ok',
                'latency_ms' => $latency,
                'store' => config('cache.default'),
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'failed',
                'error' => AuditLogger::sanitize($e->getMessage()),
            ];
        }
    }
}
