<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonitoredFile extends Model
{
    protected $fillable = [
        'general',
        'computer_id',
        'group_id',
        'path',
        'file_names',
        'recursive',
        'sort_order',
    ];

    protected $casts = [
        'general' => 'boolean',
        'recursive' => 'boolean',
        'sort_order' => 'integer',
        'file_names' => 'array',
    ];

    public function computer()
    {
        return $this->belongsTo(Computer::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Expande el registro en una o más entradas de configuración para el agente,
     * una por cada archivo/pattón de file_names. Una lista vacía significa
     * "todos los archivos de la ruta" (file_name null).
     */
    public function toConfigEntries(): array
    {
        $names = $this->file_names ?: [null];

        return array_map(
            fn ($name) => [
                'path' => $this->path,
                'file_name' => $name,
                'recursive' => (bool) $this->recursive,
            ],
            $names
        );
    }
}
