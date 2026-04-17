<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$content = file_get_contents('database/seeders/BiSysTiendasSeeder.php');

preg_match_all('/\[\s*[\']clave_tienda[\']\s*=>\s*[\']([^\'\"]+)[\']\s*,\s*[\']nombre[\']\s*=>\s*[\']([^\'\"]*)[\']\s*,\s*[\']id_plaza[\']\s*=>\s*[\']([^\'\"]+)[\']\s*,\s*[\']zona[\']\s*=>\s*[\']([^\'\"]*)[\']\s*,\s*[\']clave_alterna[\']\s*=>\s*[\']([^\'\"]+)[\']\s*,\s*[\']id_tipo[\']\s*=>\s*[\']([^\'\"]*)[\']\s*,\s*[\']estado[\']\s*=>\s*[\']([^\'\"]+)[\']\s*,\s*[\']grupo[\']\s*=>\s*[\']([^\'\"]+)[\']/', $content, $matches, PREG_SET_ORDER);

$count = 0;
foreach ($matches as $m) {
    $tipo = $m[6] ?: '1';
    $grupo = $m[8] == 'VENDEDOR' ? 'VENDEDOR' : ($m[6] == '6' ? 'ALMACEN' : 'TIENDA');
    DB::table('bi_sys_tiendas')->updateOrInsert(
        ['clave_tienda' => $m[1]],
        [
            'nombre' => $m[2],
            'id_plaza' => $m[3],
            'zona' => $m[4],
            'clave_alterna' => $m[5],
            'id_tipo' => $tipo,
            'estado' => $m[7],
            'grupo' => $grupo,
        ]
    );
    $count++;
}
echo "Processed $count tiendas\n";
