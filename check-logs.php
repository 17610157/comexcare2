<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$count = DB::select('SELECT COUNT(*) as c FROM computer_logs');
echo 'Total records: '.$count[0]->c.PHP_EOL;

if ($count[0]->c > 0) {
    $oldest = DB::select('SELECT MIN(created_at) as oldest FROM computer_logs');
    echo 'Oldest: '.$oldest[0]->oldest.PHP_EOL;
    $newest = DB::select('SELECT MAX(created_at) as newest FROM computer_logs');
    echo 'Newest: '.$newest[0]->newest.PHP_EOL;
}
