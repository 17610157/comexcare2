# Contrato de la API de Cortes (`xcorte_api`)

Documento de referencia para el registro de cortes de tienda. Lo consumen **dos
aplicaciones**:

1. **Agente** (`XCORTE_API_KEY_AGENTE`): lee el `XCORTE.DBF` y registra los cortes.
   El servidor le agrega la `plaza` a partir de la configuración del agente
   (`computers.short_key` -> `computers.plaza`).
2. **Aplicación externa** (`XCORTE_API_KEY_APP`): registra cortes con la misma
   nomenclatura. **No** se le agrega plaza (queda `null`).

> **Nota:** Esta API escribe en la tabla `xcorte_api`. No afecta a la tabla legacy
> `xcorte` usada por los reportes de ventas del dashboard.

## 1. Endpoints

Base URL (ajustar al host real):

```
http://<host>/api
```

| Método | Ruta | Descripción | Origen |
|---|---|---|---|
| `POST` | `/api/cortes` | Registra/actualiza un corte individual. | Agente o aplicación |
| `POST` | `/api/cortes/lote` | Registra/actualiza un lote de cortes. | Agente o aplicación |
| `GET`  | `/api/cortes` | Consulta cortes con filtros y paginación. | Agente o aplicación |

Todos requieren autenticación por header.

## 2. Autenticación

Header obligatorio en todas las peticiones:

```
X-API-Key: <api_key>
```

Existen **dos keys**, una por aplicación:

| Aplicación | Variable de entorno | Efecto en `plaza` |
|---|---|---|
| Agente | `XCORTE_API_KEY_AGENTE` | Se deriva de `computers.short_key` (según `clave_tienda`). |
| Aplicación externa | `XCORTE_API_KEY_APP` | No se guarda (`null`). |

- Si el header falta o la key es inválida: `401`.
- La key se envía tal cual, sin `Bearer`.

## 3. Body individual (`POST /api/cortes`)

Contenido JSON:

```json
{
  "fecha_corte": "2026-10-07",
  "clave_tienda": "00021",
  "monto_contado": 1234.56,
  "monto_credito": 789.00
}
```

## 4. Body por lote (`POST /api/cortes/lote`)

Objeto con el arreglo `cortes`. También se acepta un arreglo raíz
(`[ { ... }, { ... } ]`) como atajo.

```json
{
  "cortes": [
    {
      "fecha_corte": "2026-10-07",
      "clave_tienda": "00021",
      "monto_contado": 1234.56,
      "monto_credito": 789.00
    },
    {
      "fecha_corte": "2026-10-07",
      "clave_tienda": "00022",
      "monto_contado": 540.00,
      "monto_credito": 120.50
    }
  ]
}
```

## 5. Descripción de los campos

| Campo | Tipo | Obligatorio | Descripción |
|---|---|---|---|
| `fecha_corte` | Cadena | Sí | Fecha del corte en formato `YYYY-MM-DD`. |
| `clave_tienda` | Cadena (máx. 50) | Sí | Clave de la tienda (`short_key`). |
| `monto_contado` | Número | Sí | Monto de contado. No puede ser negativo. |
| `monto_credito` | Número | Sí | Monto a crédito. No puede ser negativo. |

Campos que **genera el servidor** (no se envían):

| Campo | Tipo | Descripción |
|---|---|---|
| `fecha_registro` | Timestamp | Fecha/hora del registro (`now()` del servidor). |
| `plaza` | Cadena / `null` | Solo para origen agente, derivada de `computers.short_key`. |
| `computer_id` | Entero / `null` | Solo para origen agente, id del equipo que registra. |

### Regla de duplicados (upsert)

La clave única es `fecha_corte + clave_tienda`:

- Si **no existe**, se crea (respuesta `201`).
- Si **ya existe**, se actualizan `monto_contado`, `monto_credito` y `fecha_registro`.
  - Origen agente: además actualiza `plaza` y `computer_id`.
  - Origen aplicación: **no** modifica `plaza` ni `computer_id` existentes.

## 6. Respuestas

### 6.1 Individual

`201 Created` (nuevo) o `200 OK` (actualizado):

```json
{
  "message": "Corte registrado correctamente",
  "accion": "creado",
  "corte": {
    "id": 1,
    "fecha_corte": "2026-10-07",
    "clave_tienda": "00021",
    "monto_contado": "1234.56000",
    "monto_credito": "789.00000",
    "fecha_registro": "2026-10-08T15:36:11.000000Z",
    "plaza": "BAJAC",
    "computer_id": 10,
    "created_at": "2026-10-08T15:36:11.000000Z",
    "updated_at": "2026-10-08T15:36:11.000000Z"
  }
}
```

`accion` es `"creado"` o `"actualizado"`.

### 6.2 Lote

`201 Created` si no hay errores, o `207 Multi-Status` si alguno falló:

```json
{
  "message": "Operación por lote completada",
  "created_count": 1,
  "updated_count": 1,
  "error_count": 0,
  "cortes": [ { "...": "..." } ],
  "errors": []
}
```

Cada error del lote tiene la forma:

```json
{ "index": 0, "error": "DB Error: <detalle>" }
```

### 6.3 Consulta (`GET /api/cortes`)

Parámetros opcionales:

| Parámetro | Tipo | Descripción |
|---|---|---|
| `fecha_corte` | Cadena | Filtra por fecha exacta (`YYYY-MM-DD`). |
| `fecha_inicio` | Cadena | Fecha inicial del rango (`YYYY-MM-DD`). |
| `fecha_fin` | Cadena | Fecha final del rango (`YYYY-MM-DD`). |
| `clave_tienda` | Cadena | Filtra por tienda. |
| `plaza` | Cadena | Filtra por plaza. |
| `per_page` | Entero | Registros por página (default 50, máx. 500). |

Respuesta paginada estándar de Laravel (`data`, `current_page`, `last_page`,
`per_page`, `total`, ...), ordenada por `fecha_corte` y `id` descendente.

### 6.4 Códigos de error

| Código | Cuándo |
|---|---|
| `401` | Falta el header `X-API-Key` o la key es inválida. |
| `422` | Validación fallida (campos faltantes o con formato inválido). |
| `429` | Rate limit excedido. Incluye el texto `Reintente en X segundos`. |
| `500` | Error interno del servidor. |

Ejemplo `401`:

```json
{ "error": "No Autorizado", "message": "Se requiere el header X-API-Key" }
```

Ejemplo `422`:

```json
{
  "message": "El campo fecha_corte es obligatorio. (and 3 more errors)",
  "errors": {
    "fecha_corte": ["El campo fecha_corte es obligatorio."],
    "clave_tienda": ["El campo clave_tienda es obligatorio."]
  }
}
```

Ejemplo `429`:

```json
{
  "error": "Demasiadas peticiones",
  "message": "Reintente en 30 segundos",
  "retry_after": 30
}
```

## 7. Ejemplos `curl`

Registro individual (agente):

```bash
curl -X POST http://<host>/api/cortes \
  -H "X-API-Key: $XCORTE_API_KEY_AGENTE" \
  -H "Content-Type: application/json" \
  -d '{"fecha_corte":"2026-10-07","clave_tienda":"00021","monto_contado":1234.56,"monto_credito":789.00}'
```

Registro por lote (aplicación externa):

```bash
curl -X POST http://<host>/api/cortes/lote \
  -H "X-API-Key: $XCORTE_API_KEY_APP" \
  -H "Content-Type: application/json" \
  -d '{"cortes":[{"fecha_corte":"2026-10-07","clave_tienda":"00021","monto_contado":1234.56,"monto_credito":789.00}]}'
```

Consulta:

```bash
curl -G http://<host>/api/cortes \
  -H "X-API-Key: $XCORTE_API_KEY_AGENTE" \
  --data-urlencode "fecha_inicio=2026-10-01" \
  --data-urlencode "fecha_fin=2026-10-07" \
  --data-urlencode "clave_tienda=00021"
```

## 8. Requisitos de las aplicaciones consumidoras

1. Enviar siempre el header `X-API-Key` con la key correspondiente.
2. Enviar `Content-Type: application/json`.
3. Respetar los nombres de campo exactos (`snake_case`).
4. Enviar fechas de corte en formato `YYYY-MM-DD`.
5. Considerar éxito cualquier código `2xx` (incluye `207` en lote, que implica
   errores parciales que deben revisarse en `errors`).
6. Ante `429`, esperar los segundos indicados en `retry_after` y reintentar.
7. Ante `502`, `503`, `504` o error de red, reintentar con espera progresiva.
8. Ante `400`, `401`, `403`, `422`, `500`, no reintentar; registrar el error.
