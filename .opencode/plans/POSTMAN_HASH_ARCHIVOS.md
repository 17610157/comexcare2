# API hash-archivos - Guia para probar en Postman

Endpoints de recepcion de conciliacion de archivos DBF (cliente `hash_dbf`).
Documento para compartir y probar con Postman o cURL.

## URL base

```
http://212.227.6.127:8000
```

> Si el sistema se publica en otro dominio/host, reemplazar la base por la URL real
> (ej. `https://comexcare.example.com`).

## Configuracion comun (ambos endpoints)

| Campo | Valor |
|---|---|
| Metodo HTTP | `POST` |
| Header | `X-API-Key: <tu_api_key>` |
| Header | `Content-Type: application/json` |
| Body | raw / JSON |

La API key que acepta el servidor es el valor de `CONCILIACION_HASH_ARCHIVOS_API_KEY`
del `.env` del servidor. El cliente `hash_dbf` la envia igualmente en el header
`X-API-Key`.

---

## 1) Registrar lote

```
POST http://212.227.6.127:8000/api/hash-archivos/registrar-lote
```

### Body de ejemplo

```json
{
  "Tiendas": [
    {
      "NombreCarpeta": "centr",
      "RutaBase": "D:\\dbf\\cre\\centr",
      "FechaEnvio": "2026-08-06T10:30:00Z",
      "Disparador": "serv",
      "Sucursal": "cre",
      "Archivos": [
        {
          "Nombre": "VALES.DBF",
          "Existe": true,
          "Md5": "d41d8cd98f00b204e9800998ecf8427e",
          "Peso": 123456,
          "FechaModificacion": "2026-08-05T14:22:11"
        }
      ]
    },
    {
      "NombreCarpeta": "cforj",
      "RutaBase": "D:\\dbf\\cre\\cforj",
      "FechaEnvio": "2026-08-06T10:30:01Z",
      "Disparador": "serv",
      "Sucursal": "cre",
      "Archivos": []
    }
  ]
}
```

### Respuesta de exito (200)

```json
{
  "success": true,
  "message": "Lote registrado correctamente",
  "tiendas": 2,
  "archivos": 1
}
```

---

## 2) Registrar individual (tienda suelta)

```
POST http://212.227.6.127:8000/api/hash-archivos/registrar
```

### Body de ejemplo (tienda suelta)

```json
{
  "NombreCarpeta": "centr",
  "RutaBase": "D:\\dbf\\cre\\centr",
  "FechaEnvio": "2026-08-06T10:30:00Z",
  "Disparador": "serv",
  "Sucursal": "cre",
  "Archivos": [
    {
      "Nombre": "CAJAS.DBF",
      "Existe": true,
      "Md5": "5d41402abc4b2a76b9719d911017c592",
      "Peso": 2048,
      "FechaModificacion": "2026-08-05T09:15:00"
    }
  ]
}
```

Tambien acepta el mismo formato con wrapper `{ "Tiendas": [ ... ] }`.

### Respuesta de exito (200)

```json
{
  "success": true,
  "message": "Lote registrado correctamente",
  "tiendas": 1,
  "archivos": 1
}
```

---

## Campos esperados

Cada tienda dentro de `Tiendas`:


| Campo | Tipo | Descripcion |
|---|---|---|
| `NombreCarpeta` | string | Nombre de la carpeta de la sucursal (ej. `centr`). |
| `RutaBase` | string | Ruta completa de la carpeta (ej. `D:\dbf\cre\centr`). |
| `FechaEnvio` | string | Fecha/hora ISO 8601 con `Z` (ej. `2026-08-06T10:30:00Z`). |
| `Disparador` | string | Origen de la ejecucion (ej. `serv`, `pvsi`, `rbf`). |
| `Sucursal` | string | Codigo de la sucursal (ej. `cre`). |
| `Archivos` | array | Lista de archivos (puede venir vacia `[]`). |

Cada elemento de `Archivos`:

| Campo | Tipo | Descripcion |
|---|---|---|
| `Nombre` | string | Nombre del archivo (ej. `VALES.DBF`). |
| `Existe` | boolean | Siempre `true`. |
| `Md5` | string | Hash MD5 en hexadecimal de 32 caracteres. |
| `Peso` | integer | Tamano del archivo en bytes (>= 0). |
| `FechaModificacion` | string | Fecha de modificacion ISO 8601 sin `Z` (ej. `2026-08-05T14:22:11`). |

---

## Casos de error

| Caso | Codigo | Respuesta |
|---|---|---|
| Sin header `X-API-Key` | `401` | `{ "error": "No Autorizado", ... }` |
| API key incorrecta | `401` | `{ "error": "No Autorizado", ... }` |
| JSON invalido | `422` | `{ "success": false, "message": "Validación fallida", "errors": {...} }` |
| Campos obligatorios faltantes | `422` | Igual que arriba con el detalle de cada campo |
| `Md5` que no sea hex de 32 (ej. `zzz`) | `422` | Igual que arriba |
| Body mayor a 10 MB | `413` | `{ "success": false, "message": "El cuerpo de la petición excede el tamaño máximo permitido." }` |
| Mas de 30 peticiones/min (misma key) | `429` | `{ "error": "Demasiadas peticiones", "message": "Reintente en X segundos", ... }` |

---

## Ejemplos con cURL

### Registrar lote

```bash
curl -X POST http://212.227.6.127:8000/api/hash-archivos/registrar-lote \
  -H "X-API-Key: TU_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "Tiendas": [
      {
        "NombreCarpeta": "centr",
        "RutaBase": "D:\\dbf\\cre\\centr",
        "FechaEnvio": "2026-08-06T10:30:00Z",
        "Disparador": "serv",
        "Sucursal": "cre",
        "Archivos": [
          {
            "Nombre": "VALES.DBF",
            "Existe": true,
            "Md5": "d41d8cd98f00b204e9800998ecf8427e",
            "Peso": 123456,
            "FechaModificacion": "2026-08-05T14:22:11"
          }
        ]
      }
    ]
  }'
```

### Registrar individual

```bash
curl -X POST http://212.227.6.127:8000/api/hash-archivos/registrar \
  -H "X-API-Key: TU_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "NombreCarpeta": "centr",
    "RutaBase": "D:\\dbf\\cre\\centr",
    "FechaEnvio": "2026-08-06T10:30:00Z",
    "Disparador": "serv",
    "Sucursal": "cre",
    "Archivos": [
      {
        "Nombre": "CAJAS.DBF",
        "Existe": true,
        "Md5": "5d41402abc4b2a76b9719d911017c592",
        "Peso": 2048,
        "FechaModificacion": "2026-08-05T09:15:00"
      }
    ]
  }'
```

---

## Requisitos previos

1. Ejecutar las migraciones en el servidor: `php artisan migrate`
   (crea la tabla `hash_archivos_lotes` y agrega `md5_completo` a `conciliacion_hash_archivos`).
2. La key del servidor debe coincidir con la del cliente: en `.env`,
   `CONCILIACION_HASH_ARCHIVOS_API_KEY` (usar comillas simples por los caracteres especiales).
