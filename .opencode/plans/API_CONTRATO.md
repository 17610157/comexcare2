# Contrato de la API - hash_dbf

Documento de referencia para el consumo del endpoint de conciliacion por lotes.
Sirve para comunicar al proveedor que desarrolle (o reemplace) el servicio central
exactamente que envia el cliente `hash_dbf` y que respuesta espera.

> **Importante:** Con la configuracion actual (`config.ini` trae `endpoint_url_lote`)
> el cliente **solo consume el endpoint por lotes** (`registrar-lote`).

## 1. Endpoint por lotes

Ruta completa:

```
POST http://a561ebc317a38bf2a238baeb5c53e185.servicios.care/api_conciliaciones/index.php/hash-archivos/registrar-lote
```

Headers:

```
X-API-Key: piP+"KV#8K+bA,LPP4rm\v..U4sR21
```

| Aspecto | Valor |
|---|---|
| Metodo HTTP | `POST` |
| Autenticacion | Header `X-API-Key: <api_key>` |
| Contenido del body | JSON |
| Timeout de conexion | 10 segundos |
| Timeout de envio | 10 segundos |
| Timeout de recepcion | 60 segundos |
| Proxy | Proxy del sistema (WinHTTP) |
| HTTPS | Soportado (auto-detecta `https://`) |
| User-Agent | `hash_dbf_lote/1.0` |

## 2. Body (JSON)

El body es un objeto con un arreglo `Tiendas`, donde cada tienda describe una
sucursal con sus archivos. El lote se envia con un unico POST.

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

El buffer maximo para el JSON del lote es de 10 MB.

## 3. Descripcion de los campos

Campos de cada tienda dentro de `Tiendas`:

| Campo | Tipo | Descripcion |
|---|---|---|
| `NombreCarpeta` | Cadena | Nombre de la carpeta de la sucursal (ej. `centr`). |
| `RutaBase` | Cadena | Ruta completa de la carpeta (ej. `D:\dbf\cre\centr`). |
| `FechaEnvio` | Cadena | Fecha/hora de envio en formato ISO 8601 con `Z` (ej. `2026-08-06T10:30:00Z`). |
| `Disparador` | Cadena | Origen de la ejecucion (proviene del argumento de la linea de comandos). |
| `Sucursal` | Cadena | Codigo de la sucursal (ej. `cre`). |
| `Archivos` | Arreglo | Lista de archivos encontrados. |

Cada elemento de `Archivos`:

| Campo | Tipo | Descripcion |
|---|---|---|
| `Nombre` | Cadena | Nombre del archivo (ej. `VALES.DBF`). |
| `Existe` | Booleano | Siempre `true`; el cliente solo envia archivos que existen. |
| `Md5` | Cadena | Hash MD5 del archivo en hexadecimal minusculas (32 caracteres). |
| `Peso` | Entero | Tamano del archivo en bytes. |
| `FechaModificacion` | Cadena | Fecha de modificacion del archivo en formato ISO 8601 sin `Z` (ej. `2026-08-05T14:22:11`). |

## 4. Respuesta esperada del servidor

El cliente **solo valida el codigo HTTP** de la respuesta. No lee ni interpreta
el contenido del body en caso de exito.

| Codigo HTTP | Comportamiento del cliente |
|---|---|
| `200` a `299` | Exito. Se da por enviado, se ignora el body. |
| `429` | Rate limit. El cliente busca el texto `Reintente en X segundos` en el body y espera los `X` segundos indicados (por defecto 10, maximo 120). Reintenta hasta 3 veces. |
| `502`, `503`, `504` | Error temporal. Reintenta cada 5 segundos hasta 3 veces. |
| Error de red o timeout | Error temporal. Reintenta cada 5 segundos hasta 3 veces. |
| Cualquier otro codigo (`400`, `401`, `403`, `500`, etc.) | Error definitivo. **No** reintenta; registra el codigo y el body, y continua con la siguiente sucursal o lote. |

### Mensaje de rate limit esperado (429)

El cliente extrae los segundos de espera del body con el patron exacto:

```
Reintente en 30 segundos
```

Si el body no contiene ese patron, espera 10 segundos por defecto.

## 5. Configuracion del cliente (config.ini)

Para migrar a otro proveedor basta con actualizar este archivo que se coloca
junto al ejecutable `hash_dbf.exe`:

```ini
[http]
endpoint_url=http://a561ebc317a38bf2a238baeb5c53e185.servicios.care/api_conciliaciones/index.php/hash-archivos/registrar
endpoint_url_lote=http://a561ebc317a38bf2a238baeb5c53e185.servicios.care/api_conciliaciones/index.php/hash-archivos/registrar-lote
api_key=piP+"KV#8K+bA,LPP4rm\v..U4sR21
```

| Clave | Uso |
|---|---|
| `endpoint_url` | URL del endpoint individual (respaldo, se usa solo si el lote esta vacio). |
| `endpoint_url_lote` | URL del endpoint por lotes (el principal, con la configuracion actual). |
| `api_key` | Valor que se envia en el header `X-API-Key`. |

## 6. Requisitos del nuevo proveedor (para un cambio transparente)

El **contrato principal** es el endpoint por lotes `registrar-lote`. Para que el
cambio de proveedor solo implique modificar `config.ini` y no el codigo del
cliente, el nuevo servicio debe:

1. Mantener el header de autenticacion `X-API-Key`.
2. Mantener el mismo esquema JSON (nombres PascalCase, arreglo `Archivos` y `Tiendas`).
3. Considerar exito cualquier codigo `2xx`.
4. Comunicar el rate limit con codigo `429` y el texto `Reintente en X segundos` en el body.
5. Indicar si requiere el header `Content-Type: application/json` (hoy el cliente **no** lo envia).
6. Mantener el formato de fechas ISO 8601 descrito.

Si alguno de estos puntos difiere, se requiere un ajuste en el codigo del cliente
(archivos `src/http.c`, `src/json.c` y `src/config.c`).
