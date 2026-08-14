<?php

namespace App\Console\Commands;

use App\Models\Command;
use Illuminate\Console\Command as ConsoleCommand;
use Illuminate\Support\Carbon;

class RecoverStuckCommands extends ConsoleCommand
{
    protected $signature = 'commands:recover-stuck {--minutes=60 : Minutes without updates to consider a command stuck}';

    protected $description = 'Mark commands stuck in pending/sent/running as failed when no update is received in the given window';

    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');

        if ($minutes < 1) {
            $this->error('The --minutes option must be at least 1.');

            return ConsoleCommand::FAILURE;
        }

        $cutoff = Carbon::now()->subMinutes($minutes);

        $stuck = Command::whereIn('status', ['pending', 'sent', 'running'])
            ->where('updated_at', '<', $cutoff)
            ->get();

        $count = 0;
        foreach ($stuck as $command) {
            $response = trim((string) $command->response);
            $note = '[ERROR]'.PHP_EOL.'Recuperado por timeout: el agente no reportó la finalización del comando.'.PHP_EOL.'[EXIT_CODE: -1]';
            $response = $response === '' ? $note : $response.PHP_EOL.PHP_EOL.$note;

            $command->update([
                'status' => 'failed',
                'response' => $response,
                'completed_at' => Carbon::now(),
            ]);
            $count++;
        }

        $this->info("Marked {$count} stuck commands as failed (no update since {$cutoff->toDateTimeString()}).");

        return ConsoleCommand::SUCCESS;
    }
}
