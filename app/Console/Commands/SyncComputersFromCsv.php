<?php

namespace App\Console\Commands;

use App\Models\Computer;
use App\Models\Group;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class SyncComputersFromCsv extends Command
{
    protected $signature = 'computers:sync-csv {file? : Path to CSV file}';

    protected $description = 'Match computers from CSV by IP, MAC or name and update download_path, group, plaza, short_key';

    public function handle(): int
    {
        $file = $this->argument('file') ?? base_path('computadoras_2026-06-26_073919.csv');

        if (! file_exists($file)) {
            $this->error("File not found: {$file}");

            return self::FAILURE;
        }

        $rows = $this->parseCsv($file);
        $this->info("Loaded {$rows->count()} rows from CSV");

        $updated = 0;
        $skipped = 0;
        $errors = 0;

        $groupsCache = [];

        foreach ($rows as $index => $row) {
            if (empty($row['computer_name'])) {
                continue;
            }

            $computer = $this->findComputer($row);

            if (! $computer) {
                $this->line(" [SKIP] Row {$index}: no match for {$row['computer_name']} ({$row['ip']})");
                $skipped++;

                continue;
            }

            try {
                $updateData = [];

                if (! empty($row['download_path'])) {
                    $updateData['download_path'] = $row['download_path'];
                }

                if (! empty($row['short_key'])) {
                    $updateData['short_key'] = strtoupper($row['short_key']);
                }

                if (! empty($row['plaza'])) {
                    $updateData['plaza'] = $row['plaza'];
                }

                if (! empty($row['group_name'])) {
                    if (! isset($groupsCache[$row['group_name']])) {
                        $groupsCache[$row['group_name']] = Group::where('name', $row['group_name'])->first();
                    }
                    $group = $groupsCache[$row['group_name']];
                    if ($group) {
                        $updateData['group_id'] = $group->id;
                    }
                }

                if (! empty($updateData)) {
                    $computer->update($updateData);
                    $this->line(" [OK]   Row {$index}: {$computer->computer_name} (ID:{$computer->id}) updated");
                    $updated++;
                } else {
                    $this->line(" [SKIP] Row {$index}: {$computer->computer_name} - nothing to update");
                    $skipped++;
                }
            } catch (\Exception $e) {
                $this->error(" [ERR]  Row {$index}: {$e->getMessage()}");
                $errors++;
            }
        }

        $this->newLine();
        $this->info("Done. Updated: {$updated}, Skipped: {$skipped}, Errors: {$errors}");

        return self::SUCCESS;
    }

    protected function parseCsv(string $path): Collection
    {
        $handle = fopen($path, 'r');
        $rawHeaders = fgetcsv($handle);
        $headers = array_map(fn ($h) => trim(preg_replace('/^\xEF\xBB\xBF/', '', $h), '"'), $rawHeaders);
        $rows = collect();

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) === count($headers)) {
                $row = array_combine($headers, $data);
                $rows->push([
                    'short_key' => trim($row['Short Key'] ?? ''),
                    'computer_name' => trim($row['Nombre'] ?? ''),
                    'mac' => trim($row['MAC'] ?? ''),
                    'ip' => trim($row['IP'] ?? ''),
                    'plaza' => trim($row['Plaza'] ?? ''),
                    'group_name' => trim($row['Grupo'] ?? ''),
                    'download_path' => trim($row['Download Path'] ?? ''),
                ]);
            }
        }

        fclose($handle);

        return $rows;
    }

    protected function isPlaceholderMac(string $mac): bool
    {
        return str_starts_with($mac, 'AUTO-REC')
            || str_starts_with($mac, 'PENDING-DEL')
            || str_starts_with($mac, 'AUTO-');
    }

    protected function findComputer(array $row): ?Computer
    {
        $mac = strtolower($row['mac']);
        $ip = $row['ip'];
        $name = $row['computer_name'];

        if (! empty($ip)) {
            $byIp = Computer::where('ip_address', $ip)->first();
            if ($byIp) {
                return $byIp;
            }
        }

        if (! empty($mac) && ! $this->isPlaceholderMac($mac)) {
            $byMac = Computer::where('mac_address', $mac)->first();
            if ($byMac) {
                return $byMac;
            }
        }

        if (! empty($ip)) {
            $byNameAndIp = Computer::where('computer_name', $name)
                ->where('ip_address', $ip)
                ->first();
            if ($byNameAndIp) {
                return $byNameAndIp;
            }
        }

        if (! empty($name)) {
            $byName = Computer::where('computer_name', $name)->first();
            if ($byName) {
                return $byName;
            }
        }

        return null;
    }
}
