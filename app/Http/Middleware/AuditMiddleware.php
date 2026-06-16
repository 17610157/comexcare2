<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuditMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $this->logAudit($request, $response, $startTime);

        return $response;
    }

    protected function logAudit(Request $request, Response $response, float $startTime): void
    {
        $shouldAudit = $this->shouldAudit($request);

        if (! $shouldAudit) {
            return;
        }

        try {
            $auditData = [
                'user_id' => $request->user()?->id,
                'action' => $this->determineAction($request),
                'endpoint' => $request->fullUrl(),
                'method' => $request->method(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'request_data' => $this->sanitizeRequestData($request),
                'response_code' => $response->getStatusCode(),
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'created_at' => now()->toDateTimeString(),
            ];

            if (class_exists('\App\Models\AuditLog')) {
                AuditLog::create($auditData);
            }

            if (config('audit.log_to_file', false)) {
                $this->logToFile($auditData);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to write audit log: '.$e->getMessage());
        }
    }

    protected function shouldAudit(Request $request): bool
    {
        $excludedPaths = [
            'api/health',
            'api/heartbeat',
            '_debugbar',
        ];

        foreach ($excludedPaths as $path) {
            if ($request->is($path)) {
                return false;
            }
        }

        $excludedMethods = ['OPTIONS'];

        if (in_array($request->method(), $excludedMethods)) {
            return false;
        }

        return true;
    }

    protected function determineAction(Request $request): string
    {
        $method = $request->method();
        $path = $request->path();

        $action = match ($method) {
            'GET' => 'view',
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => 'other',
        };

        if (str_contains($path, 'login')) {
            $action = 'login';
        } elseif (str_contains($path, 'logout')) {
            $action = 'logout';
        } elseif (str_contains($path, 'sync')) {
            $action = 'sync';
        } elseif (str_contains($path, 'export')) {
            $action = 'export';
        }

        return $action;
    }

    protected function sanitizeRequestData(Request $request): ?array
    {
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'current_password',
            'secret',
            'token',
            'api_key',
            'DB_PASSWORD',
        ];

        $data = $request->except($sensitiveFields);

        $data = $this->recursiveSanitize($data, $sensitiveFields);

        return array_slice($data, 0, 50);
    }

    protected function recursiveSanitize(array $data, array $sensitiveFields): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->recursiveSanitize($value, $sensitiveFields);
            } elseif (in_array(strtolower($key), $sensitiveFields)) {
                $data[$key] = '[REDACTED]';
            }
        }

        return $data;
    }

    protected function logToFile(array $auditData): void
    {
        $logPath = storage_path('logs/audit/'.date('Y-m').'.log');
        $directory = dirname($logPath);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $logEntry = sprintf(
            "[%s] %s | User: %s | IP: %s | %s %s | Response: %s | Duration: %sms\n",
            $auditData['created_at'],
            $auditData['action'],
            $auditData['user_id'] ?? 'anonymous',
            $auditData['ip_address'],
            $auditData['method'],
            $auditData['endpoint'],
            $auditData['response_code'],
            $auditData['duration_ms']
        );

        file_put_contents($logPath, $logEntry, FILE_APPEND);
    }
}
