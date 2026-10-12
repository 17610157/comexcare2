<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Destinatarios fijos de las notificaciones de cortes faltantes
    |--------------------------------------------------------------------------
    |
    | Estos correos se agregan SIEMPRE a cada envío por plaza, además de los
    | destinatarios configurados por plaza en la tabla corte_plaza_email_configs.
    |
    */

    'destinatarios_fijos' => [
        'jorgerivera@camposreyeros.com',
        'bryan.vazquez@camposreyeros.com',
        'francisco.hernandez@camposreyeros.com'
    ],

    /*
    |--------------------------------------------------------------------------
    | Ventana de comunicación del agente
    |--------------------------------------------------------------------------
    |
    | Minutos sin heartbeat para considerar que un agente dejó de estar en
    | comunicación. Si el agente no está en comunicación, la tienda se reporta
    | como "Agente apagado" (no se pudieron obtener datos: internet o luz).
    |
    */

    'online_window_minutes' => env('CORTES_ONLINE_WINDOW_MINUTES', 5),

];
