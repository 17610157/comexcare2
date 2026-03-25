<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DatabaseBackupCommand extends Command
{
    protected $signature = 'db:backup
                            {--type=full : Tipo de backup (full, incremental, schema)}
                            {--database= : Base de datos a respaldar}
                            {--compress : Comprimir el backup}
                            {--upload : Subir a almacenamiento}
                            {--keep=5 : Número de backups a mantener}';

    protected $description = 'Crear backup de la base de datos';

    protected string $backupPath;

    protected string $filename;

    public function __construct()
    {
        parent::__construct();
        $this->backupPath = storage_path('app/backups');
    }

    public function handle(): int
    {
        $this->info('Iniciando backup de base de datos...');

        try {
            $type = $this->option('type');
            $compress = $this->option('compress');
            $upload = $this->option('upload');
            $keep = (int) $this->option('keep');

            if (! is_dir($this->backupPath)) {
                mkdir($this->backupPath, 0755, true);
            }

            $this->filename = $this->generateFilename($type);
            $fullPath = $this->backupPath.'/'.$this->filename;

            $this->info("Tipo de backup: {$type}");
            $this->info("Archivo: {$this->filename}");

            $success = match ($type) {
                'full' => $this->backupFull($fullPath, $compress),
                'incremental' => $this->backupIncremental($fullPath, $compress),
                'schema' => $this->backupSchema($fullPath, $compress),
                default => $this->backupFull($fullPath, $compress),
            };

            if (! $success) {
                $this->error('El backup falló.');

                return Command::FAILURE;
            }

            $this->info('Backup completado exitosamente.');

            if ($upload) {
                $this->uploadBackup($fullPath);
            }

            $this->cleanupOldBackups($keep);

            $this->logBackup([
                'type' => $type,
                'filename' => $this->filename,
                'size' => filesize($fullPath),
                'compressed' => $compress,
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error durante el backup: '.$e->getMessage());
            Log::error('Backup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    protected function generateFilename(string $type): string
    {
        $timestamp = now()->format('Y-m-d_His');
        $dbName = config('database.connections.pgsql.database', 'database');
        $extension = $this->option('compress') ? 'sql.gz' : 'sql';

        return "{$dbName}_{$type}_{$timestamp}.{$extension}";
    }

    protected function backupFull(string $path, bool $compress): bool
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        $this->info("Driver de base de datos: {$driver}");

        return match ($driver) {
            'pgsql' => $this->backupPostgres($path, $compress),
            'mysql', 'mariadb' => $this->backupMysql($path, $compress),
            'sqlite' => $this->backupSqlite($path, $compress),
            default => $this->error("Driver {$driver} no soportado"),
        };
    }

    protected function backupIncremental(string $path, bool $compress): bool
    {
        $this->info('Backup incremental detectado - respaldando solo tablas de cache...');

        $cacheTables = [
            'vendedores_cache',
            'cartera_abonos_cache',
            'notas_completas_cache',
            'metas_cache',
        ];

        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        $sql = '-- Backup Incremental - '.now()."\n";
        $sql .= "-- Tablas de Cache\n\n";

        foreach ($cacheTables as $table) {
            try {
                $exists = DB::select("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = '{$table}')");
                if ($exists[0]->exists ?? false) {
                    $this->info("  Respaldando tabla: {$table}");
                    $data = DB::table($table)->get();
                    $sql .= $this->generateTableBackup($table, $data->toArray());
                }
            } catch (\Exception $e) {
                $this->warn("  Tabla {$table} no existe o no se puede respaldar");
            }
        }

        if ($compress) {
            return file_put_contents('compress.zlib://'.$path, $sql) !== false;
        }

        return file_put_contents($path, $sql) !== false;
    }

    protected function backupSchema(string $path, bool $compress): bool
    {
        $this->info('Backup de esquema (solo estructura)...');

        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        $tables = DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
        $sql = '-- Schema Backup - '.now()."\n\n";

        foreach ($tables as $table) {
            $tableName = $table->tablename;
            $this->info("  Procesando: {$tableName}");
            $create = DB::select("SELECT pg_get_tabledef('{$tableName}'::regclass)");
            if (! empty($create)) {
                $sql .= trim($create[0]->pg_get_tabledef ?? '').";\n\n";
            }
        }

        if ($compress) {
            return file_put_contents('compress.zlib://'.$path, $sql) !== false;
        }

        return file_put_contents($path, $sql) !== false;
    }

    protected function backupPostgres(string $path, bool $compress): bool
    {
        $host = config('database.connections.pgsql.host');
        $port = config('database.connections.pgsql.port', 5432);
        $database = config('database.connections.pgsql.database');
        $username = config('database.connections.pgsql.username');
        $password = config('database.connections.pgsql.password');

        $command = "PGPASSWORD='{$password}' pg_dump -h {$host} -p {$port} -U {$username} -d {$database} -Fc";

        if ($compress) {
            $command .= " | gzip > {$path}";
        } else {
            $command .= " -f {$path}";
        }

        $this->info('Ejecutando pg_dump...');

        $output = [];
        $result = 0;
        exec($command, $output, $result);

        if ($result !== 0) {
            $this->error('pg_dump failed: '.implode("\n", $output));

            return false;
        }

        return true;
    }

    protected function backupMysql(string $path, bool $compress): bool
    {
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port', 3306);
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        $command = "mysqldump -h {$host} -P {$port} -u {$username}";

        if ($password) {
            $command .= " -p'{$password}'";
        }

        $command .= " {$database}";

        if ($compress) {
            $command .= " | gzip > {$path}";
        } else {
            $command .= " > {$path}";
        }

        $this->info('Ejecutando mysqldump...');

        $output = [];
        $result = 0;
        exec($command, $output, $result);

        if ($result !== 0) {
            $this->error('mysqldump failed');

            return false;
        }

        return true;
    }

    protected function backupSqlite(string $path, bool $compress): bool
    {
        $database = config('database.connections.sqlite.database');

        if ($compress) {
            $command = "sqlite3 {$database} .dump | gzip > {$path}";
        } else {
            $command = "sqlite3 {$database} .dump > {$path}";
        }

        $output = [];
        $result = 0;
        exec($command, $output, $result);

        return $result === 0;
    }

    protected function generateTableBackup(string $table, array $data): string
    {
        if (empty($data)) {
            return "-- Table {$table} is empty\n\n";
        }

        $sql = "-- Table: {$table}\n";
        $sql .= "TRUNCATE TABLE {$table};\n\n";

        $firstRow = $data[0];
        $columns = array_keys((array) $firstRow);
        $columnList = implode(', ', $columns);

        foreach ($data as $row) {
            $values = [];
            foreach ((array) $row as $value) {
                if ($value === null) {
                    $values[] = 'NULL';
                } elseif (is_numeric($value)) {
                    $values[] = $value;
                } else {
                    $values[] = "'".addslashes($value)."'";
                }
            }
            $sql .= "INSERT INTO {$table} ({$columnList}) VALUES (".implode(', ', $values).");\n";
        }

        return $sql."\n";
    }

    protected function uploadBackup(string $path): void
    {
        $this->info('Subiendo backup a almacenamiento...');

        try {
            $disk = config('backup.disk', 's3');
            $filename = basename($path);

            Storage::disk($disk)->put('backups/'.$filename, file_get_contents($path));

            $this->info("Backup subido a: backups/{$filename}");
        } catch (\Exception $e) {
            $this->warn('No se pudo subir el backup: '.$e->getMessage());
        }
    }

    protected function cleanupOldBackups(int $keep): void
    {
        $this->info("Limpiando backups antiguos (mantener: {$keep})...");

        $files = glob($this->backupPath.'/*.sql*');

        if (count($files) <= $keep) {
            return;
        }

        usort($files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        $toDelete = array_slice($files, $keep);

        foreach ($toDelete as $file) {
            if (unlink($file)) {
                $this->info('  Eliminado: '.basename($file));
            }
        }
    }

    protected function logBackup(array $data): void
    {
        Log::channel('backup')->info('Backup completed', $data);
    }
}
