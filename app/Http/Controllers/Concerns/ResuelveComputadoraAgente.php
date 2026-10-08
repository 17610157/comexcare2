<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Computer;

trait ResuelveComputadoraAgente
{
    /**
     * Resuelve el computer_id que envía un agente. Si el id no existe o quedó
     * eliminado (soft-deleted) porque el registro se fusionó/renombró, devuelve el
     * registro activo equivalente por identidad de hardware: primero por machine_key,
     * luego por mac_address. Nunca por computer_name (no es único). Si no hay
     * equivalente activo, restaura el registro eliminado.
     */
    private function resuelveComputadoraAgente($id): ?Computer
    {
        $computer = Computer::find($id);
        if ($computer) {
            return $computer;
        }

        $trashed = Computer::withTrashed()->find($id);
        if (! $trashed) {
            return null;
        }

        $active = null;

        if (! empty($trashed->machine_key)) {
            $active = Computer::where('machine_key', $trashed->machine_key)
                ->where('id', '!=', $trashed->id)
                ->first();
        }

        if (! $active && ! empty($trashed->mac_address) && ! str_starts_with($trashed->mac_address, 'PENDING-DEL-')) {
            $active = Computer::where('mac_address', $trashed->mac_address)
                ->where('id', '!=', $trashed->id)
                ->first();
        }

        if ($active) {
            $this->heredaMetadata($active, $trashed);

            return $active;
        }

        $trashed->restore();

        return $trashed;
    }

    /**
     * Copia al registro que sobrevive los datos que le falten (plaza, grupo, short_key,
     * nombre_instalacion) tomados de un registro que se va a descartar/eliminar, para no
     * perder la metadata del equipo al converger duplicados.
     */
    private function heredaMetadata(Computer $target, Computer $source): void
    {
        $update = [];

        if (! $target->plaza && $source->plaza) {
            $update['plaza'] = $source->plaza;
        }

        if (! $target->group_id && $source->group_id) {
            $update['group_id'] = $source->group_id;
        }

        if (! $target->nombre_instalacion && $source->nombre_instalacion) {
            $update['nombre_instalacion'] = $source->nombre_instalacion;
        }

        if (! $target->short_key && $source->short_key) {
            $conflicto = Computer::where('short_key', $source->short_key)
                ->where('id', '!=', $target->id)
                ->exists();
            if (! $conflicto) {
                $update['short_key'] = $source->short_key;
            }
        }

        if ($update) {
            $target->update($update);
        }
    }
}
