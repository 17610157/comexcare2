<?php

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Models\Computer;
use App\Models\Group;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

echo "Leyendo COMPUTADORAS.xlsx...\n";

$spreadsheet = IOFactory::load(__DIR__.'/COMPUTADORAS.xlsx');
$sheet = $spreadsheet->getActiveSheet();
$rows = $sheet->toArray();
$header = array_shift($rows);

// 1. Crear grupos
$groupsByName = [];
foreach ($rows as $row) {
    $groupName = trim($row[6] ?? '');
    if (empty($groupName)) {
        continue;
    }
    $groupsByName[$groupName] = true;
}

echo "\n--- Creando grupos ---\n";
foreach (array_keys($groupsByName) as $name) {
    $shortKey = match ($name) {
        'CHETUMAL' => 'CHETU',
        'VALLARTA' => 'VALLA',
        'HERMOSILLO' => 'HERMO',
        'MANZANILLO' => 'MANZA',
        'TAPACHULA' => 'TAPAC',
        'XALAPA TIENDAS' => 'XALAP',
        'BAJA CALIFORNIA' => 'BAJAC',
        'PENINSULA' => 'PENLA',
        'GUADALAJARA' => 'GUADA',
        'GUATEMALA' => 'GUATE',
        'NICARAGUA' => 'NICAR',
        'REYES' => 'REYES',
        default => null,
    };

    $group = Group::firstOrCreate(
        ['name' => $name],
        ['type' => 'tienda', 'description' => '']
    );
    if ($shortKey) {
        $group->short_key = $shortKey;
        $group->save();
    }
    echo "  Grupo: {$group->name} (ID: {$group->id})\n";
}

// Reload groups
$groups = Group::all()->keyBy('name');
$groupsByShortKey = Group::all()->keyBy('short_key');

// 2. Crear plazas en bi_sys_tiendas (solo si no existen)
echo "\n--- Restaurando plazas en bi_sys_tiendas ---\n";
$plazasUnicos = [];
foreach ($rows as $row) {
    $plaza = trim($row[5] ?? '');
    if (empty($plaza)) {
        continue;
    }
    $plazasUnicos[$plaza] = true;
}

foreach (array_keys($plazasUnicos) as $plaza) {
    DB::table('bi_sys_tiendas')->updateOrInsert(
        ['clave_tienda' => $plaza],
        [
            'nombre' => match ($plaza) {
                'CHETU' => 'CHETUMAL',
                'VALLA' => 'VALLARTA',
                'HERMO' => 'HERMOSILLO',
                'MANZA' => 'MANZANILLO',
                'TAPAC' => 'TAPACHULA',
                'XALAP' => 'XALAPA',
                'BAJAC' => 'BAJA CALIFORNIA',
                'PENLA' => 'PENINSULA',
                'GUADA' => 'GUADALAJARA',
                'GUATE' => 'GUATEMALA',
                'NICAR' => 'NICARAGUA',
                'REYES' => 'REYES',
                default => $plaza,
            },
            'id_plaza' => $plaza,
            'grupo' => match ($plaza) {
                'CHETU' => 'CHETUMAL',
                'VALLA' => 'VALLARTA',
                'HERMO' => 'HERMOSILLO',
                'MANZA' => 'MANZANILLO',
                'TAPAC' => 'TAPACHULA',
                'XALAP' => 'XALAPA TIENDAS',
                'BAJAC' => 'BAJA CALIFORNIA',
                'PENLA' => 'PENINSULA',
                'GUADA' => 'GUADALAJARA',
                'GUATE' => 'GUATEMALA',
                'NICAR' => 'NICARAGUA',
                'REYES' => 'REYES',
                default => $plaza,
            },
            'estado' => 'A',
            'created_at' => now(),
            'updated_at' => now(),
        ]
    );
    echo "  Plaza: {$plaza}\n";
}

// 3. Restaurar computadoras
echo "\n--- Restaurando computadoras ---\n";
$created = 0;
$updated = 0;
$errors = 0;
$skipped = 0;

foreach ($rows as $i => $row) {
    $shortKey = trim($row[0] ?? '');
    $name = trim($row[1] ?? '');
    $mac = trim($row[2] ?? '');
    $ip = trim($row[3] ?? '');
    $status = trim($row[4] ?? '');
    $plaza = trim($row[5] ?? '');
    $groupName = trim($row[6] ?? '');
    $agentVersion = trim($row[7] ?? '');
    $pvsiVersion = trim($row[8] ?? '');
    $pvsiFecha = trim($row[9] ?? '');
    $pvsiHora = trim($row[10] ?? '');
    $resurtidoVersion = trim($row[11] ?? '');
    $resurtidoFecha = trim($row[12] ?? '');
    $windows = trim($row[13] ?? '');
    $arch = trim($row[14] ?? '');
    $ram = trim($row[15] ?? '');
    $disk = trim($row[16] ?? '');
    $downloadPath = trim($row[18] ?? '');
    $lastSeen = trim($row[19] ?? '');

    if (empty($mac)) {
        $skipped++;

        continue;
    }

    try {
        $groupId = null;
        if (! empty($groupName) && isset($groups[$groupName])) {
            $groupId = $groups[$groupName]->id;
        }

        Computer::updateOrCreate(
            ['mac_address' => $mac],
            array_filter([
                'computer_name' => $name ?: ('PC-'.$shortKey),
                'short_key' => $shortKey ?: null,
                'plaza' => $plaza ?: null,
                'mac_address' => $mac,
                'ip_address' => $ip ?: null,
                'group_id' => $groupId,
                'agent_version' => $agentVersion ?: '3.0.0',
                'resurtido_agent_version' => $resurtidoVersion ?: null,
                'pvsi_version' => $pvsiVersion ?: null,
                'pvsi_fecha' => $pvsiFecha ?: null,
                'pvsi_hora' => $pvsiHora ?: null,
                'resurtido_version' => $resurtidoVersion ?: null,
                'resurtido_fecha' => $resurtidoFecha ?: null,
                'windows_version' => $windows ?: null,
                'architecture' => $arch ?: null,
                'total_ram' => is_numeric($ram) ? (int) $ram : null,
                'total_disk_space' => is_numeric($disk) ? (int) $disk : null,
                'status' => in_array($status, ['online', 'offline', 'error', 'updating']) ? $status : 'offline',
                'download_path' => $downloadPath ?: null,
                'last_seen' => $lastSeen ?: null,
            ], fn ($v) => $v !== null && $v !== '' && $v !== []
            )
        );
        $created++;
    } catch (Exception $e) {
        echo '  Error fila '.($i + 2)." ({$name}): {$e->getMessage()}\n";
        $errors++;
    }
}

echo "\n--- Resumen ---\n";
echo "Computadoras procesadas: {$created}\n";
echo "Errores: {$errors}\n";
echo "Saltadas (sin MAC): {$skipped}\n";

// Verificar
$computerCount = Computer::count();
$groupCount = Group::count();
echo "\nTotal en DB: {$computerCount} computadoras, {$groupCount} grupos\n";
