<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('queue:work --stop-when-empty')->everyMinute();
Schedule::job(new \App\Jobs\ProcessScheduledDistributions)->everyMinute();

Schedule::command('cartera-abonos:sync-cache --last-days=60')->dailyAt('11:00');
Schedule::command('notas-completas:sync-cache --last-days=60')->dailyAt('11:00');
Schedule::command('compras-directo:sync-cache --last-days=60')->dailyAt('11:00');
