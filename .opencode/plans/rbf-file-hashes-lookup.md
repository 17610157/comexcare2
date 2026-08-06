# Plan: Integrar rbf_file_hashes en Reporte DBF Files

## Objetivo
En el reporte DBF Files (`/reportes/dbf-files`), buscar cada archivo DBF de las computadoras en la tabla `rbf_file_hashes` usando `plaza + hash_md5 + name` como criterio de coincidencia. Si se encuentra, agregar `rbf_path` y `rbf_hash` como columnas adicionales en:
- Modal de detalle
- Exportación CSV
- API pública

## Archivos a modificar

### 1. `app/Http/Controllers/ReporteDbfFilesController.php`

**Cambios:**

a) Agregar import:
```php
use App\Models\RbfFileHash;
```

b) Agregar método privado helper para construir el lookup map:
```php
private function getRbfHashLookup(): array
{
    $map = [];
    $records = RbfFileHash::all();
    foreach ($records as $r) {
        $key = strtolower($r->plaza ?? '') . '|' . ($r->hash ?? '') . '|' . ($r->name ?? '');
        $map[$key] = $r;
    }
    return $map;
}
```

c) Modificar `data()` — dentro del `map` de cada computadora, después de obtener `$dbfFiles`, buscar en el lookup y agregar `rbf_path` y `rbf_hash` a cada entry:
```php
// Al inicio del método, después de los filtros:
$rbfLookup = $this->getRbfHashLookup();

// Dentro del map(), después de $dbfFiles = ..., antes de return:
$enrichedDbfFiles = array_map(function ($file) use ($computer, $rbfLookup) {
    $key = strtolower($computer->plaza ?? '') . '|' . ($file['hash_md5'] ?? '') . '|' . ($file['name'] ?? '');
    $rbfRecord = $rbfLookup[$key] ?? null;
    $file['rbf_path'] = $rbfRecord ? $rbfRecord->path : null;
    $file['rbf_hash'] = $rbfRecord ? $rbfRecord->hash : null;
    return $file;
}, $dbfFiles);
// Reemplazar dbf_files con los enriquecidos
// ...
```

d) Modificar `export()` — mismo lookup, agregar `Ruta RBF` y `Hash RBF` al CSV:
   - En el header: agregar `'Ruta RBF', 'Hash RBF'` al final
   - En cada fila: agregar `$dbfFile['rbf_path'] ?? ''` y `$dbfFile['rbf_hash'] ?? ''`

e) Modificar `api()` — mismo lookup, agregar `ruta_rbf` y `hash_rbf` al array de cada fila

---

### 2. `resources/views/reportes/dbf-files/index.blade.php`

**Cambios:**

a) **Modal (detalle)** — Agregar 2 columnas a `#dbfFilesTable`:
```html
<thead class="table-light">
  <tr>
    <th>Nombre</th>
    <th>Ruta</th>
    <th>Tamaño</th>
    <th>Última Modificación</th>
    <th>SHA-256</th>
    <th>MD5</th>
    <th>Ruta RBF</th>       <!-- NUEVA -->
    <th>Hash RBF</th>       <!-- NUEVA -->
  </tr>
</thead>
```

En el JS del modal, al hacer `tbody.append`, agregar:
```javascript
const rbfPath = file.rbf_path || '';
const rbfHash = file.rbf_hash || '';
// En el append:
'<td style="...">' + rbfPath + '</td>' +
'<td><code>' + rbfHash + '</code></td>' +
```

Actualizar el colspan del "No hay archivos" de `6` a `8`.

b) **Filtros** (opcional) — Agregar campo de texto para buscar por hash:
```html
<div class="col-6 col-md-2">
  <label class="form-label small mb-1">Hash MD5</label>
  <input type="text" id="hash_filter" class="form-control form-control-sm" placeholder="Ej. 8B7060">
</div>
```

En el JS, agregar `d.hash = $('#hash_filter').val();` en la función `data` del DataTable ajax.

En el `btn_reset_filters`, agregar `$('#hash_filter').val('');`.

En el `btn_export`, agregar `if (...) params.append('hash', hash);`.

c) **Controlador** — En `data()`, agregar filtro por hash (como el de archivo pero buscando en agent_config por el hash_md5):
```php
$hashInput = $request->query('hash') ?? $request->input('hash');
if (! empty($hashInput)) {
    $query->where('agent_config', 'ILIKE', '%' . $hashInput . '%');
}
```

---

### 3. Consideraciones técnicas

- **Case-insensitive**: Las plazas en computers están en mayúsculas (GUADA), en rbf_file_hashes en minúsculas (guada). Se usa `strtolower()` en ambos lados.
- **Rendimiento**: `rbf_file_hashes` tiene ~2013 registros, se carga completo en memoria una vez por request. Esto es eficiente porque evita N+1 queries.
- **Hash format**: Ambos lados usan el mismo formato de 6 caracteres hex (ej. "8B7060", "1744E5").

---

### 4. Pruebas

Actualizar `tests/Feature/RbfFileHashServiceTest.php` o crear un test nuevo `tests/Feature/ReporteDbfFilesTest.php`:
- Probar que el lookup en controlador funciona con datos mock
- Probar que `data()` retorna `rbf_path` y `rbf_hash` cuando hay match
- Probar que `export()` incluye las nuevas columnas

---

### 5. Ejecución

```bash
# Formatear código
vendor/bin/pint --format agent

# Correr tests
php artisan test --compact tests/Feature/RbfFileHashServiceTest.php

# Sincronizar datos
php artisan rbf-file-hashes:sync
```
