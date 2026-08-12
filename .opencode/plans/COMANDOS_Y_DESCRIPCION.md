# Comandos y Descripción del Proyecto

## Resumen

Sistema de gestión de tiendas, computadoras, distribuciones y reportes. Incluye módulos de agentes, monitoreo de archivos (RBF File Hashes), reportes de ventas, cartera, notas, compras y Club Comex.

---

## Comandos Artisan

### Comandos Personalizados

| Comando | Descripción | Uso |
|---------|-------------|-----|
| `rbf-file-hashes:sync` | Sincroniza hashes de archivos desde el endpoint RBF FileServices (cada 30 minutos) | `php artisan rbf-file-hashes:sync` |
| `monitored-files:seed-defaults {--group=}` | Asigna lista de archivos monitoreados por defecto a grupos | `php artisan monitored-files:seed-defaults --group=1` |
| `app:prune-computer-logs {--days=}` | Elimina logs de computadoras más antiguos que X días | `php artisan app:prune-computer-logs --days=3` |
| `computers:sync-csv {file?}` | Sincroniza computadoras desde CSV por IP/MAC/nombre | `php artisan computers:sync-csv archivo.csv` |
| `computers:update-paths {--plazas=} {--short-keys=}` | Actualización masiva de rutas de descarga y MAC | `php artisan computers:update-paths --plazas=BAJAC` |
| `computers:clean-duplicates {--dry-run}` | Limpia agentes duplicados: fusiona offline en online | `php artisan computers:clean-duplicates` |
| `computers:check-status {--minutes=5}` | Marca computadoras como offline si no hay heartbeat | `php artisan computers:check-status --minutes=5` |
| `distribution:restart {id} {--only-in-progress}` | Reinicia procesamiento de una distribución | `php artisan distribution:restart 123` |
| `workers:start {--workers=2} {--queues=}` | Inicia workers de cola para procesar distribuciones | `php artisan workers:start --workers=4` |
| `sync:all-cache-tables {--full} {--period=}` | Sincroniza todas las tablas de caché de reportes | `php artisan sync:all-cache-tables --full` |
| `sync:cache-full {--start=} {--end=}` | Sincronización completa (truncate + recrear) | `php artisan sync:cache-full --start=2026-01-01` |
| `sync:cache-incremental {--month=}` | Sincronización incremental sin borrar datos existentes | `php artisan sync:cache-incremental --month=2026-08` |
| `cartera-abonos:sync-cache {--last-days=60}` | Sincroniza caché de Cartera Abonos | `php artisan cartera-abonos:sync-cache --last-days=60` |
| `notas-completas:sync-cache {--last-days=60}` | Sincroniza caché de Notas Completas | `php artisan notas-completas:sync-cache --last-days=60` |
| `compras-directo:sync-cache {--last-days=60}` | Sincroniza caché de Compras Directo | `php artisan compras-directo:sync-cache --last-days=60` |
| `cartera-abonos:sync {--type=} {--force}` | Sincroniza Cartera Abonos con tabla materializada | `php artisan cartera-abonos:sync --type=full` |
| `clubcomex:sync {year?}` | Sincroniza datos de Club Comex (redenciones, acumulaciones) | `php artisan clubcomex:sync 2026` |
| `db:backup {--type=} {--compress} {--upload}` | Crea respaldo de base de datos | `php artisan db:backup --type=full --compress --upload` |

### Comandos del Framework Útiles

```bash
php artisan migrate:fresh --seed          # Recrear DB con seeders
php artisan migrate --force               # Ejecutar migraciones en producción
php artisan queue:work --stop-when-empty  # Procesar cola
php artisan optimize                      # Cachear config/rutas/vistas
php artisan cache:clear                   # Limpiar caché de aplicación
php artisan config:clear                  # Limpiar caché de configuración
php artisan route:clear                   # Limpiar caché de rutas
php artisan view:clear                    # Limpiar caché de vistas
php artisan test                          # Ejecutar tests
php artisan test --filter="nombre_test"   # Ejecutar test específico
```

---

## Tareas Programadas (Schedule)

Archivo: `routes/console.php`

| Frecuencia | Comando/Job | Descripción |
|------------|-------------|-------------|
| Cada minuto | `queue:work --stop-when-empty` | Procesar trabajos de la cola |
| Cada 5 minutos | `computers:check-status --minutes=5` | Marcar computadoras offline |
| Cada 3 días (00:00) | `app:prune-computer-logs --days=3` | Podar logs antiguos |
| Cada minuto | `ProcessScheduledDistributions` | Procesar distribuciones programadas |
| Cada minuto | `ProcessScheduledReceptions` | Procesar recepciones programadas |
| 2 veces al día (00:00, 11:00) | `sync:cache-incremental` | Sincronización incremental de cachés |
| Diario (11:00) | `cartera-abonos:sync-cache --last-days=60` | Sincronizar Cartera Abonos |
| Diario (11:00) | `notas-completas:sync-cache --last-days=60` | Sincronizar Notas Completas |
| Diario (11:00) | `compras-directo:sync-cache --last-days=60` | Sincronizar Compras Directo |
| Cada 30 minutos | `rbf-file-hashes:sync` | Sincronizar hashes RBF |

---

## Rutas Principales

### RBF File Hashes (Admin)

| Método | URI | Permiso | Descripción |
|--------|-----|---------|-------------|
| GET | `admin/rbf-file-hashes` | `rbf-file-hashes.ver` | Vista del módulo de subida |
| GET | `admin/rbf-file-hashes/data` | `rbf-file-hashes.ver` | Datatable JSON |
| POST | `admin/rbf-file-hashes` | `rbf-file-hashes.crear` | Subir archivos (hash MD5 últimos 5 chars) |
| DELETE | `admin/rbf-file-hashes/{id}` | `rbf-file-hashes.eliminar` | Eliminar registro |

### Reportes DBF Files

| Método | URI | Permiso | Descripción |
|--------|-----|---------|-------------|
| GET | `reportes/dbf-files` | `dbf-files.ver` | Reporte general de archivos (.EXE y .BAT) |
| GET | `reportes/dbf-files/data` | `dbf-files.ver` | DataTable con validación de hashes |
| GET | `reportes/dbf-files/export` | `dbf-files.ver` | Exportar a CSV |
| GET | `reportes/dbf-files-especificos` | `dbf-files-especificos.ver` | Reporte de archivos específicos (ARCERO, LISTA, OFERTAS, PCOMB, PDCOMB, PROMARTS, CABLISTA, CLIECATP) |
| GET | `reportes/dbf-files-especificos/data` | `dbf-files-especificos.ver` | DataTable específico |
| GET | `reportes/dbf-files-especificos/export` | `dbf-files-especificos.ver` | Exportar CSV |
| POST | `reportes/dbf-files-especificos/ejecutar/{tipo}` | `dbf-files-especificos.ejecutar` | Ejecutar BAT remoto (lista, promocion, oferta, combo) |
| GET | `reportes/dbf-files-especificos/ids` | `dbf-files-especificos.ver` | Obtener IDs de computadoras filtradas |
| GET | `reportes/dbf-files-especificos/historial` | `dbf-files-especificos.ver` | Historial de hash últimos 3 días |
| GET | `reportes/dbf-files-quickbck` | `dbf-files-quickbck.ver` | Conciliación QuickBCK |

### Reportes de Ventas y Cartera

| Método | URI | Permiso | Descripción |
|--------|-----|---------|-------------|
| GET | `reportes/vendedores` | `reportes.vendedores.ver` | Reporte de vendedores |
| GET | `reportes/cartera-abonos` | `reportes.cartera-abonos.ver` | Cartera y abonos |
| GET | `reportes/notas-completas` | `reportes.notas-completas.ver` | Notas completas |
| GET | `reportes/compras-directo` | `reportes.compras-directo.ver` | Compras directo |
| GET | `reportes/club-comex` | `reportes.club-comex.ver` | Club Comex |
| GET | `reportes/metas-ventas` | `reportes.metas-ventas.ver` | Metas de ventas |
| GET | `reportes/metas-matricial` | `reportes.metas-matricial.ver` | Metas matricial |
| GET | `reportes/desglose` | `reportes.metas-matricial.ver` | Desglose |
| GET | `reportes/distribuciones` | `reportes.distribuciones.ver` | Distribuciones |
| GET | `reportes/vales` | `reportes.vales.ver` | Vales |
| GET | `reportes/redenciones-club` | `reportes.redenciones_club.ver` | Redenciones Club |

---

## Permisos (Spatie Permission)

### Convención de Nombres

Formato: `recurso.accion`

Acciones comunes: `ver`, `crear`, `editar`, `eliminar`, `sincronizar`, `exportar`, `filtrar`, `autorizar`, `ejecutar`

### Permisos Clave del Módulo RBF / DBF

```
rbf-file-hashes.ver
rbf-file-hashes.crear
rbf-file-hashes.eliminar

dbf-files.ver
dbf-files-especificos.ver
dbf-files-especificos.ejecutar
dbf-files-quickbck.ver
```

### Roles Principales

| Rol | Descripción |
|-----|-------------|
| `super_admin` | Acceso total |
| `administrativo` | Administración general |
| `gerente_plaza` | Reportes y vista de su plaza |
| `coordinador` | Coordinación de operaciones |
| `gerente_tienda` | Gestión de tienda |
| `vendedor` | Acceso limitado a reportes |

### Seeders de Permisos

- `database/seeders/PermissionSeeder.php` - Crea todos los permisos (~100+)
- `database/seeders/AssignDefaultPermissionsSeeder.php` - Asigna permisos a roles

---

## Servicios Principales

| Servicio | Archivo | Descripción |
|----------|---------|-------------|
| `RbfFileHashService` | `app/Services/RbfFileHashService.php` | Consume endpoint externo `https://rbf.camposreyeros.com/api/queryHashFileServicesJson`, sincroniza hashes. Borra solo registros `manual=0`, preserva manuales. |
| `DistributionService` | `app/Services/DistributionService.php` | Crea distribuciones, asigna targets, valida blacklist/whitelist, maneja retries con backoff exponencial. |
| `ReportService` | `app/Services/ReportService.php` | Motor central de reportes: vendedores, B2B, matricial, metas. Usa caché y procesamiento por chunks. |
| `CarteraAbonosCacheService` | `app/Services/CarteraAbonosCacheService.php` | Consultas optimizadas con CTEs para Cartera Abonos. |
| `CarteraAbonosUltraFastService` | `app/Services/CarteraAbonosUltraFastService.php` | Arquitectura ultra-rápida con Redis para 500+ usuarios concurrentes. |
| `CarteraAbonosMaterializedService` | `app/Services/CarteraAbonosMaterializedService.php` | Gestiona tabla materializada `cartera_abonos_materialized` con sincronización en background. |
| `DashboardCacheService` | `app/Services/DashboardCacheService.php` | Caché de métricas del dashboard en Redis. |
| `AgentUpdateService` | `app/Services/AgentUpdateService.php` | Gestiona versiones del agente, checksums, despliegue y rollback. |

---

## Migraciones Relevantes

### RBF File Hashes

| Migración | Descripción |
|-----------|-------------|
| `2026_07_03_133003_create_rbf_file_hashes_table.php` | Crea tabla `rbf_file_hashes` con `servicio`, `plaza`, `zona`, `path` (unique), `name`, `hash`, `last_modified`, `last_sync`. |
| `2026_08_12_130333_add_manual_to_rbf_file_hashes_table.php` | Agrega columna `manual` (boolean/smallint) para distinguir subidas manuales de las de la API. |
| `2026_08_12_134845_change_manual_to_smallint_on_rbf_file_hashes.php` | Cambia `manual` a `smallint` para compatibilidad con PostgreSQL. |

### Computer Logs

| Migración | Descripción |
|-----------|-------------|
| `2026_02_27_000000_create_computer_logs_table.php` | Crea tabla `computer_logs` con `computer_id`, `level`, `message`. |
| `2026_03_19_000000_add_performance_indexes_v2.php` | Agrega índices de performance incluyendo `idx_cl_computer_created` en `computer_logs`. |
| `2026_08_12_151200_add_computer_created_index_to_computer_logs.php` | Índice compuesto `(computer_id, created_at)` para historial de hashes. |

---

## Controladores Principales

### `RbfFileHashesController`

- `index()` - Vista con filtros de plaza
- `store()` - Sube archivos, calcula MD5 (últimos 5 caracteres en mayúsculas), almacena como `manual=1`
- `data()` - Datatable JSON con búsqueda/ordenamiento
- `destroy()` - Elimina registro

### `ReporteDbfFilesController`

- `index()` - Vista con filtros de plaza, grupo, archivo, hash
- `data()` - DataTable principal: compara archivos de agente contra tabla RBF. Muestra solo `.EXE` y `.BAT`. Calcula stats por plaza, archivo y grupo.
- `export()` - Exporta CSV con detalles de match
- `api()` - Endpoint público JSON/CSV para consumo externo

### `ReporteDbfFilesEspecificosController`

- `index()` - Vista de archivos específicos (ARCERO, LISTA, OFERTAS, PCOMB, PDCOMB, PROMARTS, CABLISTA, CLIECATP)
- `data()` - DataTable con estados: `actualizado`, `cambio_manual`, `desactualizado`
- `ejecutar($tipo)` - Despacha comandos BAT (`DALISTA.BAT`, `DAPROMO.BAT`, `DAOFERTA.BAT`, `DACOMBO.BAT`) a computadoras desactualizadas
- `historial()` - Consulta logs de heartbeat últimos 3 días para mostrar historial de hash
- `bitacora()` - Bitácora de ejecución de comandos
- `ids()` - Retorna IDs de computadoras filtradas para acciones masivas

### `ReporteDbfFilesQuickbckController`

- `index()` - Vista de conciliación QuickBCK
- `data()` - Conciliación de archivos QuickBCK contra PVSI y RBF
- `export()` - Exporta CSV de conciliación

### `ComputersController`

- `index()` - Listado de computadoras con filtros
- `logs()` - Logs en tiempo real (initial 100 o polling)
- `fixDuplicates()` - Encuentra y fusiona duplicados
- `export()` - Exporta CSV con información completa

### `DistributionsController`

- `index()` - Listado de distribuciones
- `store()` - Crear distribución con archivos
- `start()` / `stop()` / `restart()` - Control de distribuciones
- `retryTarget()` - Reintentar target fallido
- `progress()` - Progreso en JSON

---

## Flujo de Datos: RBF File Hashes

```
API Externa (rbf.camposreyeros.com)
         │
         ▼
    ┌─────────────────┐
    │ rbf-file-hashes:sync  (cada 30 min)
    │ RbfFileHashService
    └─────────────────┘
         │
         ▼
    ┌─────────────────┐
    │  Borrar manual=0  │  <-- Preserva subidas manuales
    │  Insertar/Actualizar
    └─────────────────┘
         │
         ▼
    ┌─────────────────┐
    │ rbf_file_hashes │  (tabla PostgreSQL)
    │  manual: 0/1    │
    └─────────────────┘
         │
         ▼
    ┌─────────────────────────────┐
    │ ReporteDbfFilesController   │
    │ ReporteDbfFilesEspecificos  │  <-- Validación de hashes
    └─────────────────────────────┘
```

### Lógica de Hash

- **Agente envía:** `hash_md5` completo (ej: `8B7060`)
- **Almacenado en tabla:** últimos 5 caracteres en mayúsculas (ej: `B7060`)
- **Comparación:** `strtoupper(substr($hash_md5, -5))` == `hash` de tabla
- **Key de búsqueda:** `plaza|hash_5chars|nombre_archivo` (todo lowercase)

---

## Tests

### Ejecutar Tests

```bash
php artisan test                          # Todos los tests
php artisan test --compact                # Salida compacta
php artisan test --filter="nombre"        # Test específico
```

### Suites de Tests Relevantes

| Archivo | Descripción |
|---------|-------------|
| `tests/Feature/RbfFileHashesControllerTest.php` | CRUD del módulo de subida |
| `tests/Feature/RbfFileHashServiceTest.php` | Sincronización del servicio RBF |
| `tests/Feature/ReporteDbfFilesControllerTest.php` | Reporte general de archivos |
| `tests/Feature/ReporteDbfFilesEspecificosControllerTest.php` | Reporte específico y ejecutar BAT |
| `tests/Feature/ReporteDbfFilesQuickbckControllerTest.php` | Conciliación QuickBCK |

---

## Notas de Producción

### Límites de Subida

Archivo: `.user.ini`
```ini
upload_max_filesize = 20M
post_max_size = 24M
```

Validación Laravel: `max:20480` (20MB)

### Índices de Performance

- `idx_cl_computer_created` en `computer_logs` (computer_id, created_at)
- `computer_logs_computer_id_id_index` en `computer_logs` (computer_id, id)
- `rbf_file_hashes`: índices en `servicio`, `plaza`, `path` (unique)

### Opcache

Después de cada deploy:
```bash
service php8.3-fpm reload
```

### Formato de Código

```bash
vendor/bin/pint --dirty --format agent
```

---

## Stack Tecnológico

| Tecnología | Versión |
|------------|---------|
| PHP | 8.3 |
| Laravel | 11.x |
| PostgreSQL | 15+ |
| Redis | 7+ |
| Nginx | 1.24+ |
| Node.js | 20+ |
| Bootstrap | 5.x |
| jQuery | 3.x |
| Chart.js | 4.x |
| Spatie Permission | 6.x |
| Laravel Pint | 1.x |
| PHPUnit/Pest | 3.x |
