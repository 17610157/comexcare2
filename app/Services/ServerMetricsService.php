<?php

namespace App\Services;

class ServerMetricsService
{
    private static ?array $lastCpuSample = null;
    private static ?array $lastNetSample = null;

    public static function collect(): array
    {
        $mem = self::memory();

        return [
            'ts' => (int) round(microtime(true) * 1000),
            'cpu' => self::cpuUsage(),
            'cores' => self::cores(),
            'load1' => self::loadAvg(),
            'mem_pct' => $mem['pct'],
            'mem_used' => $mem['used'],
            'mem_total' => $mem['total'],
            'swap' => self::swap(),
            'disk' => self::disk('/'),
            'net' => self::network(),
            'uptime_s' => self::uptime(),
        ];
    }

    private static function cpuUsage(): ?float
    {
        $stat = @file_get_contents('/proc/stat');
        if ($stat === false || !preg_match('/^cpu\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/', $stat, $m)) {
            return null;
        }

        $idle = (int) $m[4] + (int) $m[5];
        $total = 0;
        for ($i = 1; $i <= 7; $i++) {
            $total += (int) $m[$i];
        }

        $sample = ['idle' => $idle, 'total' => $total, 'ts' => microtime(true)];
        $last = self::$lastCpuSample;
        self::$lastCpuSample = $sample;

        if ($last === null) {
            return null;
        }

        $dTotal = $sample['total'] - $last['total'];
        $dIdle = $sample['idle'] - $last['idle'];

        if ($dTotal <= 0) {
            return null;
        }

        return round(max(0.0, min(100.0, 100 * (1 - $dIdle / $dTotal))), 1);
    }

    private static function cores(): int
    {
        $n = (int) @shell_exec('nproc 2>/dev/null');

        return $n > 0 ? $n : max(1, (int) ($_SERVER['NUMBER_OF_PROCESSORS'] ?? 1));
    }

    private static function loadAvg(): float
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();

            return round((float) ($load[0] ?? 0), 2);
        }

        return 0.0;
    }

    private static function memory(): array
    {
        $info = self::memInfo();
        $total = (int) round(($info['MemTotal'] ?? 0));
        $available = (int) round(($info['MemAvailable'] ?? $info['MemFree'] ?? 0));
        $used = max(0, $total - $available);

        return [
            'total' => $total,
            'used' => $used,
            'pct' => $total > 0 ? round($used / $total * 100, 1) : 0.0,
        ];
    }

    private static function swap(): array
    {
        $info = self::memInfo();
        $total = (int) round(($info['SwapTotal'] ?? 0));
        $free = (int) round(($info['SwapFree'] ?? 0));
        $used = max(0, $total - $free);

        return [
            'total' => $total,
            'used' => $used,
            'pct' => $total > 0 ? round($used / $total * 100, 1) : 0.0,
        ];
    }

    private static function memInfo(): array
    {
        static $cache = null;

        if ($cache !== null && (microtime(true) - $cache['ts']) < 1.0) {
            return $cache['data'];
        }

        $data = [];
        $raw = @file_get_contents('/proc/meminfo');

        if ($raw !== false) {
            foreach (explode("\n", $raw) as $line) {
                if (preg_match('/^(\w+):\s+(\d+) kB/', $line, $m)) {
                    $data[$m[1]] = (int) $m[2] * 1024;
                }
            }
        }

        $cache = ['ts' => microtime(true), 'data' => $data];

        return $data;
    }

    private static function disk(string $path): ?array
    {
        $total = @disk_total_space($path);
        $free = @disk_free_space($path);

        if ($total === false || $free === false || $total <= 0) {
            return null;
        }

        $used = max(0.0, $total - $free);

        return [
            'total' => (int) $total,
            'used' => (int) $used,
            'free' => (int) $free,
            'pct' => round($used / $total * 100, 1),
        ];
    }

    private static function network(): array
    {
        $raw = @file_get_contents('/proc/net/dev');

        if ($raw === false) {
            return ['rx_kbps' => 0.0, 'tx_kbps' => 0.0];
        }

        $rx = 0;
        $tx = 0;

        foreach (explode("\n", $raw) as $i => $line) {
            if ($i < 2 || !str_contains($line, ':')) {
                continue;
            }

            [$name, $rest] = explode(':', $line, 2);
            $name = trim($name);

            if ($name === 'lo' || str_starts_with($name, 'veth') || str_starts_with($name, 'docker') || str_starts_with($name, 'br-')) {
                continue;
            }

            $cols = preg_split('/\s+/', trim($rest));
            $rx += (int) ($cols[0] ?? 0);
            $tx += (int) ($cols[8] ?? 0);
        }

        $now = microtime(true);
        $last = self::$lastNetSample;
        self::$lastNetSample = ['rx' => $rx, 'tx' => $tx, 'ts' => $now];

        if ($last === null || $now - $last['ts'] <= 0 || $rx < $last['rx']) {
            return ['rx_kbps' => 0.0, 'tx_kbps' => 0.0];
        }

        $dt = $now - $last['ts'];

        return [
            'rx_kbps' => round(max(0, ($rx - $last['rx']) / $dt / 1024), 1),
            'tx_kbps' => round(max(0, ($tx - $last['tx']) / $dt / 1024), 1),
        ];
    }

    private static function uptime(): int
    {
        $raw = @file_get_contents('/proc/uptime');

        if ($raw !== false && preg_match('/^(\d+)/', $raw, $m)) {
            return (int) $m[1];
        }

        return 0;
    }
}
