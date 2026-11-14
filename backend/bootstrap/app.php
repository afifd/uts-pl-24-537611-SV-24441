<?php

use App\Http\Controllers\HealthController;
use App\Services\AuditLogger;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::get('/health', [HealthController::class, 'check']);
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->is('health') || $request->expectsJson(),
        );

        // Flow 2: Validation Failure Logger (WARNING)
        $exceptions->render(function (ValidationException $e, Request $request) {
            AuditLogger::warning('Validation failure detected', [
                'who' => [
                    'actor' => auth()->id() ?? 'unauthenticated',
                    'client_ip' => $request->ip(),
                    'user_agent' => $request->userAgent() ?? 'unknown',
                ],
                'what' => [
                    'action' => 'request_validation',
                    'endpoint' => $request->path(),
                    'method' => $request->method(),
                    'failed_fields' => array_keys($e->errors()),
                    'error_messages' => $e->errors(),
                ],
                'why' => [
                    'reason' => 'Incoming request failed validation on fields: ' . implode(', ', array_keys($e->errors())),
                ],
            ]);

            return null; // Fall through to standard Laravel 422 JSON response
        });

        // Flow 3: Database Connection / Query Failure Logger (ERROR)
        $exceptions->reportable(function (QueryException $e) {
            $request = request();
            AuditLogger::error('Database query failure', [
                'who' => [
                    'actor' => auth()->id() ?? 'unauthenticated',
                    'client_ip' => $request?->ip() ?? '127.0.0.1',
                    'user_agent' => $request?->userAgent() ?? 'internal/system',
                ],
                'what' => [
                    'action' => 'database_query',
                    'database' => config('database.default'),
                    'sql_state' => $e->getCode(),
                ],
                'why' => [
                    'reason' => 'Database failure: ' . $e->getMessage(),
                ],
            ]);

            return false;
        });

        $exceptions->reportable(function (PDOException $e) {
            $request = request();
            AuditLogger::error('Database connection failure', [
                'who' => [
                    'actor' => auth()->id() ?? 'unauthenticated',
                    'client_ip' => $request?->ip() ?? '127.0.0.1',
                    'user_agent' => $request?->userAgent() ?? 'internal/system',
                ],
                'what' => [
                    'action' => 'database_connection',
                    'database' => config('database.default'),
                    'error_code' => $e->getCode(),
                ],
                'why' => [
                    'reason' => 'Database connection failed: ' . $e->getMessage(),
                ],
            ]);

            return false;
        });
    })->create();
