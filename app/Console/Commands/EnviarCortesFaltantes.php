<?php

namespace App\Console\Commands;

use App\Models\CortePlazaEmailConfig;
use App\Services\CortesFaltantesService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class EnviarCortesFaltantes extends Command
{
    protected $signature = 'cortes:enviar-faltantes
                            {--plaza=* : Plazas específicas (envío manual, ignora hora y control diario)}
                            {--fecha= : Fecha a evaluar (Y-m-d), por defecto el día anterior}
                            {--forzar : Envía aunque la hora no coincida o ya se haya enviado hoy}';

    protected $description = 'Envía por plaza el correo de tiendas sin corte / con corte fuera de la regla';

    public function handle(CortesFaltantesService $service): int
    {
        $fecha = $this->option('fecha')
            ? Carbon::parse((string) $this->option('fecha'))
            : now()->subDay();

        $plazasExplicitas = collect($this->option('plaza'))->filter()->unique()->values();
        $manual = $plazasExplicitas->isNotEmpty();
        $forzar = (bool) $this->option('forzar');

        $query = CortePlazaEmailConfig::query();

        if ($manual) {
            $query->whereIn('plaza', $plazasExplicitas->all());
        } else {
            $query->where('envio_activo', true);
        }

        $configs = $query->orderBy('plaza')->get();

        if ($configs->isEmpty()) {
            $this->info($manual
                ? 'No hay configuraciones para las plazas indicadas.'
                : 'No hay plazas con envío automático activo.');

            return self::SUCCESS;
        }

        $horaActual = now()->hour;
        $enviados = 0;

        foreach ($configs as $config) {
            if (! $manual && ! $forzar) {
                if ((int) $config->hora_envio !== $horaActual) {
                    continue;
                }

                if ($config->ultimo_envio_at && $config->ultimo_envio_at->isToday()) {
                    $horaEnvio = $config->ultimo_envio_at->format('H:i');
                    $this->line("[OMITIDA] {$config->plaza}: ya se envió hoy ({$horaEnvio}).");

                    continue;
                }
            }

            $result = $service->enviar($fecha, $config->plaza);

            if ($result['enviado']) {
                $enviados++;
                $this->info("[OK] {$config->plaza}: {$result['sin_corte']} sin corte, {$result['regla']} de la regla. Destinatarios: ".implode(', ', $result['destinatarios']));
            } else {
                $this->warn("[OMITIDA] {$config->plaza}: ".($result['motivo'] ?? 'sin faltantes'));
            }
        }

        $this->info("Envío completado: {$enviados} correo(s) enviado(s).");

        return self::SUCCESS;
    }
}
