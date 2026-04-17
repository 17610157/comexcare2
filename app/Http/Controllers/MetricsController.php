<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class MetricsController extends Controller
{
    public function index(Request $request)
    {
        $now = Carbon::now();
        $oneMinuteAgo = $now->copy()->subMinute();

        $requestCount = 0;
        $dbDriver = config('database.default');

        if ($dbDriver === 'pgsql') {
            try {
                $requestCount = DB::table('audit_logs')
                    ->where('created_at', '>=', $oneMinuteAgo)
                    ->count();
            } catch (\Exception $e) {
                $requestCount = 0;
            }
        }

        $redisConnected = $this->checkRedisConnection();
        $queuePending = $this->getQueuePending();

        return response()->json([
            'metrics' => [
                'requests_per_minute' => $requestCount,
                'timestamp' => $now->toIso8601String(),
            ],
            'system' => [
                'redis_connected' => $redisConnected,
                'queue_pending' => $queuePending,
                'php_fpm_max_children' => 50,
            ],
            'status' => 'ok',
        ]);
    }

    public function health(Request $request)
    {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => Carbon::now()->toIso8601String(),
        ]);
    }

    private function checkRedisConnection(): bool
    {
        try {
            Redis::connection('default')->ping();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getQueuePending(): int
    {
        try {
            $queue = Redis::connection('queue');
            if ($queue && method_exists($queue, 'llen')) {
                return (int) $queue->llen('default');
            }

            return 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
}
