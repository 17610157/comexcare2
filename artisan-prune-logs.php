<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$cutoffDate = $argv[1] ?? null;

if (! $cutoffDate) {
    echo "Usage: php artisan-prune-logs.php <cutoff-date>\n";
    echo "Example: php artisan-prune-logs.php 2026-07-24\n";
    exit(1);
}

// First find the max ID at the cutoff date
echo "Finding max ID at cutoff {$cutoffDate}...\n";

$result = DB::select('SELECT MAX(id) as max_id FROM computer_logs WHERE created_at < ?', [$cutoffDate]);
$maxId = $result[0]->max_id;

if (! $maxId) {
    echo "No records found before {$cutoffDate}. Nothing to delete.\n";
    exit(0);
}

echo "Max ID to delete: {$maxId}\n";

// Delete in chunks using sequential ID ranges for efficiency
$deletedTotal = 0;
$chunkSize = 50000;
$currentId = 0;

while ($currentId < $maxId) {
    $endId = min($currentId + $chunkSize, $maxId);

    $deleted = DB::delete(
        'DELETE FROM computer_logs WHERE id > ? AND id <= ? AND created_at < ?',
        [$currentId, $endId, $cutoffDate]
    );

    $deletedTotal += $deleted;
    echo "ID range {$currentId}-{$endId}: deleted {$deleted} | Total: {$deletedTotal}\n";

    $currentId = $endId;

    // Brief pause to avoid overwhelming the DB
    usleep(100000);
}

echo "Done. Total deleted: {$deletedTotal}\n";
