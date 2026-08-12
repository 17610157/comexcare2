<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DefaultMonitoredFilesSeeder extends Seeder
{
    /**
     * Lista completa de archivos que hoy reporta el agente de distribución.
     *
     * Se usa para no perder cobertura en la transición: mientras el agente
     * deja de traer la lista hardcodeada, el servidor le entrega exactamente
     * lo mismo desde /api/computer/{id}/config.
     *
     *  - Las rutas absolutas (D:\...) apuntan fuera del download_path.
     *  - Las rutas relativas (comex_id, MODEM/ATM, quickbck, .) son relativas
     *    al download_path del agente.
     */
    public static function defaultEntries(): array
    {
        return [
            ['path' => 'D:\\PVSI\\AJTFLU_RESUMEN', 'file_names' => ['ConciliacionApp.exe'], 'recursive' => false],
            ['path' => 'comex_id', 'file_names' => ['pvsi_bepartners.exe'], 'recursive' => false],
            ['path' => 'ResurCARE/CareAgentResurtido', 'file_names' => ['*.EXE'], 'recursive' => true],
            ['path' => '.', 'file_names' => ['*.DBF', '*.EXE', '*.CDX'], 'recursive' => false],
            ['path' => 'MODEM/ATM', 'file_names' => [], 'recursive' => false],
            ['path' => 'quickbck', 'file_names' => ['*.DBF'], 'recursive' => false],
        ];
    }
}
