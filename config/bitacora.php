<?php

return [

    'endpoint' => env('BITACORA_ENDPOINT', 'http://diario.camposreyeros.com/api_bitacora.php'),
    'api_key' => env('BITACORA_API_KEY'),
    'evidencias_dir' => env('BITACORA_EVIDENCIAS_DIR', 'app/evidencias'),
    'empleado_id' => env('BITACORA_EMPLEADO_ID'),
    'categoria' => env('BITACORA_CATEGORIA', 'Evidencia diaria'),
    'descripcion' => env('BITACORA_DESCRIPCION', 'Captura diaria de evidencias'),

];
