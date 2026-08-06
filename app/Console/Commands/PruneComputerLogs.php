<?php

namespace App\Console\Commands;

use App\Models\ComputerLog;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class PruneComputerLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:prune-computer-logs {--days= : Number of days to keep. Defaults to 1 (removes logs older than yesterday).}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove computer logs older than the specified number of days';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');

        if ($days < 1) {
            $this->error('The --days option must be at least 1.');

            return Command::FAILURE;
        }

        $cutoff = Carbon::now()->subDays($days)->endOfDay();

        $deletedCount = ComputerLog::where('created_at', '<', $cutoff)->delete();

        $this->info("Deleted {$deletedCount} computer log records older than {$cutoff->toDateTimeString()}.");

        return Command::SUCCESS;
    }
}
