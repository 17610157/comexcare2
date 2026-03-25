<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class StartQueueWorkers extends Command
{
    protected $signature = 'workers:start 
                            {--workers=2 : Number of workers per queue}
                            {--queues=distributions,default : Queues to process}';

    protected $description = 'Start queue workers for distribution processing';

    public function handle(): int
    {
        $workers = (int) $this->option('workers');
        $queues = explode(',', $this->option('queues'));

        $this->info('Starting queue workers...');
        $this->info("Workers per queue: $workers");
        $this->info('Queues: '.implode(', ', $queues));
        $this->info('');
        $this->warn('Use the queue-workers.sh script instead for production:');
        $this->line('  ./queue-workers.sh start');
        $this->info('');
        $this->info('Or start Horizon for a web-based dashboard:');
        $this->line('  php artisan horizon');

        return Command::SUCCESS;
    }
}
