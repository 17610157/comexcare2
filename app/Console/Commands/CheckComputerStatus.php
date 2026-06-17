<?php

namespace App\Console\Commands;

use App\Models\Computer;
use Illuminate\Console\Command;

class CheckComputerStatus extends Command
{
    protected $signature = 'computers:check-status {--minutes=5 : Minutes without heartbeat to consider offline}';

    protected $description = 'Mark computers as offline if no heartbeat received in specified minutes';

    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');

        $this->info("Checking computer status (offline after {$minutes} minutes of inactivity)...");

        $offlineThreshold = now()->subMinutes($minutes);

        $computersStillOnline = Computer::where('status', 'online')
            ->where('last_seen', '<', $offlineThreshold)
            ->get();

        $count = 0;
        foreach ($computersStillOnline as $computer) {
            $computer->update(['status' => 'offline']);
            $this->line("Marked as offline: {$computer->computer_name}");
            $count++;
        }

        $this->info("Marked {$count} computers as offline");

        $onlineCount = Computer::where('status', 'online')->count();
        $offlineCount = Computer::where('status', 'offline')->count();

        $this->info("Total - Online: {$onlineCount}, Offline: {$offlineCount}");

        return Command::SUCCESS;
    }
}
