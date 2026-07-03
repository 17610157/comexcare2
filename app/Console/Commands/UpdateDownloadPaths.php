<?php

namespace App\Console\Commands;

use App\Models\Computer;
use Illuminate\Console\Command;

class UpdateDownloadPaths extends Command
{
    protected $signature = 'computers:update-paths
        {--plazas= : Comma-separated plaza codes}
        {--short-keys= : Comma-separated short keys}
        {--file= : Pipe-delimited file with short_key|computer_name|mac|ip}
        {--download-path= : New download path (default: D:\\\PVSI)}';

    protected $description = 'Mass update download_path and MAC for computers';

    public function handle(): int
    {
        $newPath = $this->option('download-path') ?: 'D:\\PVSI';
        $updated = 0;
        $macUpdated = 0;
        $macPattern = '/^([0-9A-Fa-f]{2}[:]){5}([0-9A-Fa-f]{2})$/';

        if ($plazas = $this->option('plazas')) {
            $plazaList = array_map('trim', explode(',', $plazas));
            foreach ($plazaList as $plaza) {
                $computers = Computer::where('plaza', $plaza)->get();
                $count = 0;
                foreach ($computers as $computer) {
                    $computer->update(['download_path' => $newPath]);
                    $count++;
                }
                $this->info("Plaza {$plaza}: {$count} computers updated");
                $updated += $count;
            }
        }

        if ($shortKeys = $this->option('short-keys')) {
            $shortKeyList = array_map('trim', explode(',', $shortKeys));
            foreach ($shortKeyList as $shortKey) {
                $computer = $this->findComputerByShortKey($shortKey);
                if ($computer) {
                    $computer->update(['download_path' => $newPath]);
                    $updated++;
                    $this->line("  OK: {$shortKey} -> {$computer->computer_name}");
                } else {
                    $this->warn("  NOT FOUND: {$shortKey}");
                }
            }
        }

        if ($file = $this->option('file')) {
            if (! file_exists($file)) {
                $this->error("File not found: {$file}");

                return self::FAILURE;
            }
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $parts = str_getcsv($line, '|');
                if (count($parts) < 4) {
                    continue;
                }
                [$shortKey, $computerName, $mac, $ip] = $parts;

                $computer = $this->findComputerByShortKey($shortKey);
                if (! $computer) {
                    $this->warn("  NOT FOUND: {$shortKey}");

                    continue;
                }

                $computer->update(['download_path' => $newPath]);

                if ($mac && preg_match($macPattern, $mac)) {
                    $computer->update(['mac_address' => $mac]);
                    $macUpdated++;
                }

                $updated++;
                $this->line("  OK: {$shortKey} -> {$computer->computer_name}".($mac && preg_match($macPattern, $mac) ? ' (MAC updated)' : ''));
            }
        }

        $this->newLine();
        $this->info('=== Summary ===');
        $this->info("Computers updated: {$updated}");
        $this->info("MAC addresses updated: {$macUpdated}");

        return self::SUCCESS;
    }

    private function findComputerByShortKey(string $shortKey): ?Computer
    {
        $computer = Computer::where('short_key', $shortKey)->first();
        if ($computer) {
            return $computer;
        }

        if (is_numeric($shortKey)) {
            $padded = str_pad($shortKey, 5, '0', STR_PAD_LEFT);
            $computer = Computer::where('short_key', $padded)->first();
        }

        return $computer;
    }
}
