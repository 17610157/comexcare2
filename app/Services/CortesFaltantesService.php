<?php

namespace App\Services;

use App\Mail\CortesFaltantesPorPlaza;
use App\Models\Computer;
use App\Models\CortePlazaEmailConfig;
use App\Models\XcorteApi;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CortesFaltantesService
{
    public function onlineWindowMinutes(): int
    {
        return (int) config('cortes.online_window_minutes', config('dashboard.online_window_minutes', 5));
    }

    /**
     * Plazas disponibles: las vistas en xcorte_api (sin vacíos) más las configuradas.
     *
     * @return Collection<int, string>
     */
    public function plazasDisponibles(): Collection
    {
        return XcorteApi::query()
            ->whereNotNull('plaza')
            ->where('plaza', '!=', '')
            ->distinct()
            ->orderBy('plaza')
            ->pluck('plaza')
            ->merge(
                CortePlazaEmailConfig::query()->orderBy('plaza')->pluck('plaza')
            )
            ->map(fn ($plaza) => trim((string) $plaza))
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }

    /**
     * Destinatarios de una plaza: fijos + configurados, correos válidos y únicos.
     *
     * @return array<int, string>
     */
    public function destinatarios(string $plaza): array
    {
        $config = CortePlazaEmailConfig::query()
            ->where('plaza', $plaza)
            ->first();

        return collect(config('cortes.destinatarios_fijos', []))
            ->merge($config?->emails ?? [])
            ->map(fn ($email) => trim((string) $email))
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Clasifica, por plaza, las tiendas sin corte para el día indicado.
     *
     * - "sin_corte": no existe fila con fecha_corte = D (motivo "Sin corte" o
     *   "Agente apagado" cuando el agente no está en comunicación).
     * - "regla": existe fila del día pero DATE(fecha_registro) != D.
     * - "cero": excepción sábado→domingo. Corte del domingo registrado el
     *   sábado con venta 0: se considera hecho en 0, no es faltante.
     *
     * Los registros sin plaza se omiten.
     *
     * @param  array<int, string>|null  $plazas
     * @return Collection<string, array{plaza: string, sin_corte: array, regla: array, cero: array}>
     */
    public function porPlaza(Carbon $dia, ?array $plazas = null): Collection
    {
        $fecha = $dia->copy()->toDateString();
        $threshold = now()->subMinutes($this->onlineWindowMinutes());
        $esDomingo = $dia->copy()->isSunday();
        $sabado = $dia->copy()->subDay()->toDateString();

        $plazasFiltro = $plazas
            ? collect($plazas)->map(fn ($p) => trim((string) $p))->filter()->unique()->values()->all()
            : null;

        $tiendas = XcorteApi::query()
            ->whereNotNull('clave_tienda')
            ->whereNotNull('plaza')
            ->where('plaza', '!=', '')
            ->orderBy('clave_tienda')
            ->orderByDesc('fecha_corte')
            ->orderByDesc('id')
            ->get(['clave_tienda', 'plaza', 'computer_id'])
            ->unique('clave_tienda')
            ->values();

        $cortesDia = XcorteApi::query()
            ->whereDate('fecha_corte', $fecha)
            ->get(['clave_tienda', 'fecha_registro', 'monto_contado', 'monto_credito'])
            ->keyBy('clave_tienda');

        $computerIds = $tiendas->pluck('computer_id')->filter()->unique()->values()->all();
        $shortKeys = $tiendas->pluck('clave_tienda')->filter()->unique()->values()->all();

        $byId = [];
        $byKey = [];

        if (! empty($computerIds) || ! empty($shortKeys)) {
            $computers = Computer::query()
                ->where(function ($query) use ($computerIds, $shortKeys) {
                    if (! empty($computerIds)) {
                        $query->whereIn('id', $computerIds);
                    }
                    if (! empty($shortKeys)) {
                        empty($computerIds)
                            ? $query->whereIn('short_key', $shortKeys)
                            : $query->orWhereIn('short_key', $shortKeys);
                    }
                })
                ->get(['id', 'short_key', 'computer_name', 'nombre_instalacion', 'last_seen']);

            foreach ($computers as $computer) {
                $byId[$computer->id] = $computer;
                if ($computer->short_key) {
                    $byKey[$computer->short_key] = $computer;
                }
            }
        }

        $nombres = DB::table('bi_sys_tiendas')
            ->whereIn('clave_tienda', $shortKeys)
            ->pluck('nombre', 'clave_tienda');

        $resultado = [];

        foreach ($tiendas as $tienda) {
            $plaza = trim((string) $tienda->plaza);

            if ($plaza === '' || ($plazasFiltro !== null && ! in_array($plaza, $plazasFiltro, true))) {
                continue;
            }

            if (! isset($resultado[$plaza])) {
                $resultado[$plaza] = ['plaza' => $plaza, 'sin_corte' => [], 'regla' => [], 'cero' => []];
            }

            $computer = $byId[$tienda->computer_id] ?? $byKey[$tienda->clave_tienda] ?? null;
            $nombreTienda = $nombres[$tienda->clave_tienda] ?? null;
            $instalacion = $computer?->nombre_instalacion ?: $computer?->computer_name;

            $base = [
                'clave_tienda' => $tienda->clave_tienda,
                'nombre' => $nombreTienda ?: $instalacion,
                'nombre_tienda' => $nombreTienda,
                'instalacion' => $instalacion,
            ];

            $corte = $cortesDia->get($tienda->clave_tienda);

            if ($corte === null) {
                $online = $computer !== null
                    && $computer->last_seen !== null
                    && $computer->last_seen->gte($threshold);

                $resultado[$plaza]['sin_corte'][] = $base + [
                    'motivo' => $online ? 'Sin corte' : 'Agente apagado',
                    'detalle' => $online
                        ? 'No se registró corte para el día.'
                        : 'Sin comunicación con el agente; no se pudieron obtener datos (posible problema de internet o luz).',
                    'ultimo_contacto' => $computer?->last_seen?->format('Y-m-d H:i:s'),
                ];

                continue;
            }

            $registroFecha = $corte->fecha_registro?->toDateString();

            if ($registroFecha === $fecha) {
                // Corte válido del día: no se lista.
                continue;
            }

            $total = (float) $corte->monto_contado + (float) $corte->monto_credito;
            $esSabadoDomingo = $esDomingo && $registroFecha === $sabado;

            if ($esSabadoDomingo && abs($total) < 0.00001) {
                // Corte del domingo registrado el sábado con venta 0: hecho en 0.
                $resultado[$plaza]['cero'][] = $base + [
                    'fecha_corte' => $fecha,
                    'fecha_registro' => $corte->fecha_registro?->format('Y-m-d H:i:s'),
                    'total' => 0.0,
                ];

                continue;
            }

            $resultado[$plaza]['regla'][] = $base + [
                'fecha_corte' => $fecha,
                'fecha_registro' => $corte->fecha_registro?->format('Y-m-d H:i:s'),
            ];
        }

        ksort($resultado);

        return collect($resultado)->map(function (array $plaza) {
            foreach (['sin_corte', 'regla', 'cero'] as $bucket) {
                usort($plaza[$bucket], fn ($a, $b) => strcmp($a['clave_tienda'], $b['clave_tienda']));
            }

            return $plaza;
        });
    }

    /**
     * Envía el correo de una plaza (manual o automático). Marca ultimo_envio_at.
     *
     * @return array{plaza: string, enviado: bool, motivo?: string, destinatarios: array, sin_corte: int, regla: int, cero: int}
     */
    public function enviar(Carbon $dia, string $plaza, bool $forzarSinFaltantes = false): array
    {
        $plaza = trim($plaza);
        $data = $this->porPlaza($dia, [$plaza])->get($plaza)
            ?? ['plaza' => $plaza, 'sin_corte' => [], 'regla' => [], 'cero' => []];

        $destinatarios = $this->destinatarios($plaza);
        $totalSinCorte = count($data['sin_corte']);
        $totalRegla = count($data['regla']);
        $totalCero = count($data['cero']);

        if (empty($destinatarios)) {
            return [
                'plaza' => $plaza,
                'enviado' => false,
                'motivo' => 'Sin destinatarios configurados.',
                'destinatarios' => [],
                'sin_corte' => $totalSinCorte,
                'regla' => $totalRegla,
                'cero' => $totalCero,
            ];
        }

        if (! $forzarSinFaltantes && $totalSinCorte === 0 && $totalRegla === 0) {
            return [
                'plaza' => $plaza,
                'enviado' => false,
                'motivo' => 'La plaza no tiene tiendas sin corte.',
                'destinatarios' => $destinatarios,
                'sin_corte' => 0,
                'regla' => 0,
                'cero' => $totalCero,
            ];
        }

        Mail::to($destinatarios)->send(new CortesFaltantesPorPlaza(
            $plaza,
            $dia->copy(),
            $data['sin_corte'],
            $data['regla'],
            $destinatarios,
        ));

        CortePlazaEmailConfig::query()
            ->where('plaza', $plaza)
            ->update(['ultimo_envio_at' => now()]);

        return [
            'plaza' => $plaza,
            'enviado' => true,
            'destinatarios' => $destinatarios,
            'sin_corte' => $totalSinCorte,
            'regla' => $totalRegla,
            'cero' => $totalCero,
        ];
    }
}
