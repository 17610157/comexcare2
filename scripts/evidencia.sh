#!/usr/bin/env bash
# ------------------------------------------------------------
# Envía una actividad a la bitácora remota:
#   1) Saca la captura de pantalla de la(s) vista(s) con Puppeteer
#   2) Guarda el PNG en storage/app/evidencias
#   3) Sube la evidencia (imagen) + la data (empleado/categoria/descripcion) al endpoint
#
# Uso:
#   bash scripts/evidencia.sh <ruta1> [ruta2 ...] [opciones]
#
# Ejemplo:
#   bash scripts/evidencia.sh /reportes/dbf-files --categoria="Desarrollo" --descripcion="Cambios en reporte"
#
# Opciones (se pasan a bitacora:enviar-nuevas):
#   --categoria=...   --descripcion=...   --fecha=YYYY-MM-DD   --empleado_id=...
# ------------------------------------------------------------
set -euo pipefail

APP_DIR="/var/www/comexcare"
EVIDENCIA_DIR="${APP_DIR}/storage/app/evidencias"

# Separar rutas de vistas (sin --) de las opciones (con --)
RUTAS=()
OPCIONES=()
for a in "$@"; do
    if [[ "$a" == --* ]]; then
        OPCIONES+=("$a")
    else
        RUTAS+=("$a")
    fi
done

if [[ ${#RUTAS[@]} -eq 0 ]]; then
    echo "Uso: bash scripts/evidencia.sh <ruta_vista> [rutas...] [--categoria=..] [--descripcion=..] [--fecha=..]"
    exit 1
fi

echo "==> Capturando vista(s): ${RUTAS[*]}"
cd /tmp/opencode
node captura_bitacora.js "${RUTAS[@]}"

echo ""
echo "==> Enviando evidencia(s) al endpoint con la data"
cd "$APP_DIR"
php artisan bitacora:enviar-nuevas "${OPCIONES[@]}"

echo ""
echo "==> Listo. Evidencias capturadas en este lote:"
ls -1t "${EVIDENCIA_DIR}"/*.png 2>/dev/null | head || echo "(sin capturas nuevas)"
