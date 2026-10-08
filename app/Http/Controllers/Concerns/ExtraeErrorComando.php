<?php

namespace App\Http\Controllers\Concerns;

trait ExtraeErrorComando
{
    /**
     * El agente devuelve el error del BAT envuelto entre marcas de inicio y fin, y el
     * payload mezcla lineas de progreso con el mensaje real. Se descartan las lineas
     * que son solo adornos (#, =, O, -) o porcentajes para dejar el error legible.
     */
    private function extraeErrorComando(?string $response, ?string $status): string
    {
        if ($status !== 'failed' || ! $response) {
            return '';
        }

        $error = '';

        if (preg_match('/\[ERROR\](.*?)\[EXIT_CODE/s', $response, $m)) {
            $clean = [];
            foreach (explode("\n", $m[1]) as $line) {
                $t = trim($line);

                if ($t === ''
                    || preg_match('/^[#=O\-]+$/', $t)
                    || preg_match('/^(#=#=|##O#|##O=|#=#=|-#O#|-=#=|-=O#|-=O=|-=O=-)/', $t)
                    || preg_match('/^\d+%$/', $t)
                    || preg_match('/^[#=\-O]+ *\d*%*$/', $t)) {
                    continue;
                }

                $clean[] = $t;
            }

            $error = implode("\n", $clean);
        } elseif (str_contains($response, 'Comando fallo')) {
            if (preg_match('/Comando fallo[^\n]+/s', $response, $m)) {
                $error = trim($m[0]);
            }
        }

        return strlen($error) > 300 ? mb_substr($error, 0, 300) : $error;
    }
}
