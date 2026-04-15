# Documentación de Módulos - Sistema de Distribución y Recepción

## Tabla de Contenidos
1. [Tiendas](#tiendas)
2. [Computers](#computers)
3. [Groups (Reception Groups)](#groups)
4. [Distributions](#distributions)
5. [Reception](#reception)
6. [Agent Versions](#agent-versions)
7. [Reporte DBF Files](#reporte-dbf-files)

---

## Tiendas

### Descripción
Gestión de tiendas/plazas del sistema. Utiliza una tabla externa (`bi_sys_tiendas`) de la base de datos del sistema.

### Tabla Principal
- **Tabla**: `bi_sys_tiendas` (tabla externa, no gestionada por Laravel)

### Columnas
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | integer | ID interno |
| clave_tienda | string | Código único de la tienda |
| nombre | string | Nombre de la tienda |
| id_plaza | string | Código de la plaza |
| zona | string | Zona geográfica |
| clave_alterna | string | Código alternativo |
| estado | string | Estado (A=Activo, C=Cancelado) |
| id_tipo | integer | Tipo de tienda |

### Modelo
- **Modelo**: No existe modelo Eloquent (usa tabla externa)
- **Controlador**: `TiendasController`
- **Rutas**:
  - `GET /admin/tiendas` - Listado
  - `GET /admin/tiendas/data` - Datos DataTable (AJAX)
  - `POST /admin/tiendas` - Crear
  - `PUT /admin/tiendas/{id}` - Actualizar
  - `DELETE /admin/tiendas/{id}` - Eliminar (no permitido)

### Vistas
- `resources/views/admin/tiendas/index.blade.php`

### Métodos del Controlador
```php
// Listado principal
public function index() // Muestra la vista

// Datos para DataTable (AJAX)
public function data(Request $request)

// Mostrar tienda específica
public function show($tienda)

// Crear tienda
public function store(Request $request)

// Actualizar tienda
public function update(Request $request, $tienda)

// Eliminar tienda (bloqueado)
public function destroy($tienda)
```

---

## Computers

### Descripción
Gestión de computadoras/agentes que reciben distribuciones y envían recepciones.

### Tabla Principal
- **Tabla**: `computers`

### Columnas
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigInteger | ID |
| computer_name | string | Nombre de la computadora |
| short_key | string | Clave corta única |
| mac_address | string | Dirección MAC única |
| ip_address | string | Dirección IP |
| group_id | foreignId | FK a groups |
| agent_version | string | Versión del agente |
| pvsi_version | string | Versión de PVSI |
| pvsi_fecha | string | Fecha de PVSI |
| pvsi_hora | string | Hora de PVSI |
| pvsi_files | json | Archivos PVSI |
| windows_version | string | Versión de Windows |
| architecture | string | Arquitectura (x64/x86) |
| total_ram | bigInteger | RAM total en bytes |
| total_disk_space | bigInteger | Espacio en disco |
| bitlocker_status | json | Estado de BitLocker |
| last_seen | timestamp | Última conexión |
| status | enum | Estado (online, offline, error, updating) |
| system_info | jsonb | Información del sistema |
| agent_config | jsonb | Configuración del agente |
| receive_paths | json | Rutas de recepción |
| download_path | string | Ruta de descarga principal |
| download_path_1 a _10 | string | Rutas de descarga adicionales |

### Modelo: `app/Models/Computer.php`
```php
class Computer extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'computer_name', 'short_key', 'mac_address', 'ip_address',
        'group_id', 'agent_version', 'pvsi_version', 'pvsi_fecha',
        'pvsi_hora', 'pvsi_files', 'windows_version', 'architecture',
        'total_ram', 'total_disk_space', 'bitlocker_status',
        'last_seen', 'status', 'system_info', 'agent_config',
        'receive_paths', 'download_path', 'download_path_1', ...
    ];
    
    // Relaciones
    public function group()
    public function distributionTargets()
    public function commands()
    public function logs()
    
    // Métodos auxiliares
    public function getAllDownloadPaths(): array
    public function getDownloadPathsCount(): int
}
```

### Controlador: `app/Http/Controllers/ComputersController.php`

#### Métodos
| Método | Ruta | Descripción |
|--------|------|-------------|
| index | GET /admin/computers | Listado con DataTable |
| show | GET /admin/computers/{computer} | Ver detalles |
| edit | GET /admin/computers/{computer}/edit | Formulario edición |
| update | PUT /admin/computers/{computer} | Actualizar |
| destroy | DELETE /admin/computers/{computer} | Eliminar |
| logs | GET /admin/computers/{computer}/logs | Logs en tiempo real |
| status | GET /admin/computers/{computer}/status | Estado JSON |
| export | GET /admin/computers/export | Exportar CSV |

### Vistas
- `resources/views/admin/computers/index.blade.php` - Listado principal
- `resources/views/admin/computers/show.blade.php` - Detalles
- `resources/views/admin/computers/edit.blade.php` - Edición

### Migraciones
- `database/migrations/2026_01_23_000002_create_computers_table.php` (base)
- `database/migrations/2026_02_24_000001_add_download_path_to_computers_table.php`
- `database/migrations/2026_03_12_150000_add_short_key_to_computers_table.php`
- `database/migrations/2026_03_12_144347_add_download_paths_to_computers_table.php`
- `database/migrations/2026_03_12_160000_add_receive_paths_to_computers_table.php`
- `database/migrations/2026_03_19_192032_add_pvsi_columns_to_computers_table.php`
- `database/migrations/2026_03_20_000001_add_system_info_to_computers_table.php`
- `database/migrations/2026_03_23_233110_add_pvsi_files_to_computers_table.php`
- `database/migrations/2026_03_24_230833_add_bitlocker_status_to_computers_table.php`

---

## Groups (Reception Groups)

### Descripción
Agrupación lógica de computadoras para distribuciones y recepciones.

### Tabla Principal
- **Tabla**: `groups`

### Columnas
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigInteger | ID |
| name | string | Nombre del grupo |
| type | string | Tipo (plaza, región, etc.) |
| description | text | Descripción |

### Modelo: `app/Models/Group.php`
```php
class Group extends Model
{
    use HasFactory;
    
    protected $fillable = ['name', 'type', 'description'];
    
    // Relaciones
    public function computers()
    public function shortKeys()
    
    // Métodos
    public function getShortKeyListAttribute()
    public static function findByShortKey(string $shortKey): ?self
}
```

### Tabla Relacionada: `group_short_keys`
- Almacena claves cortas asociadas a grupos para identificación de equipos.

### Controlador: `app/Http/Controllers/GroupsController.php`

#### Métodos
| Método | Ruta | Descripción |
|--------|------|-------------|
| index | GET /admin/groups | Listado con paginación |
| create | GET /admin/groups/create | Formulario creación |
| store | POST /admin/groups | Crear grupo |
| show | GET /admin/groups/{group} | Ver grupo con computadoras |
| edit | GET /admin/groups/{group}/edit | Formulario edición |
| update | PUT /admin/groups/{group} | Actualizar |
| destroy | DELETE /admin/groups/{group} | Eliminar |
| importExcel | POST /admin/groups/import | Importar desde Excel |
| export | GET /admin/groups/export | Exportar CSV |

### Vistas
- `resources/views/admin/groups/index.blade.php`
- `resources/views/admin/groups/create.blade.php`
- `resources/views/admin/groups/edit.blade.php`
- `resources/views/admin/groups/show.blade.php`

### Migraciones
- `database/migrations/2026_01_23_000001_create_groups_table.php`
- `database/migrations/2026_03_14_000000_add_type_to_groups_table.php`
- `database/migrations/2026_04_04_224834_add_short_key_to_groups_table.php`
- `database/migrations/2026_04_04_225710_create_group_short_keys_table.php`

---

## Distributions

### Descripción
Sistema de distribución de archivos a computadoras. Soporta distribución inmediata, programada y recurrente.

### Tabla Principal
- **Tabla**: `distributions`

### Columnas
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigInteger | ID |
| name | string | Nombre de la distribución |
| type | enum | Tipo (immediate, scheduled, recurring) |
| distribution_type | string | Tipo de archivo (file, update) |
| subfolder | string | Subcarpeta destino |
| schedule | json | Configuración de schedule |
| description | text | Descripción |
| created_by | foreignId | FK a users |
| status | enum | Estado (pending, in_progress, completed, failed, stopped) |
| scheduled_at | timestamp | Fecha programada |
| scheduled_time | string | Hora programada |
| recurrence | string | Recurrencia (daily, weekly, monthly, hourly, minutes) |
| frequency_type | string | Tipo de frecuencia |
| frequency_interval | integer | Intervalo de frecuencia |
| week_days | json | Días de la semana |
| last_run_at | timestamp | Última ejecución |

### Modelo: `app/Models/Distribution.php`
```php
class Distribution extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name', 'type', 'distribution_type', 'subfolder', 'schedule',
        'description', 'created_by', 'status', 'scheduled_at',
        'scheduled_time', 'recurrence', 'frequency_type',
        'frequency_interval', 'week_days', 'last_run_at'
    ];
    
    // Relaciones
    public function creator()
    public function files()
    public function targets()
}
```

### Tablas Relacionadas

#### distribution_files
| Campo | Tipo |
|-------|------|
| id | bigInteger |
| distribution_id | foreignId |
| file_name | string |
| file_path | string |
| checksum | string (64) |
| file_size | bigInteger |

#### distribution_targets
| Campo | Tipo |
|-------|------|
| id | bigInteger |
| distribution_id | foreignId |
| computer_id | foreignId |
| status | enum |
| progress | integer |
| attempts | integer |
| error_message | text |
| completed_at | timestamp |
| next_retry_at | timestamp |

### Controlador: `app/Http/Controllers/DistributionsController.php`

#### Métodos
| Método | Ruta | Descripción |
|--------|------|-------------|
| index | GET /admin/distributions | Listado |
| create | GET /admin/distributions/create | Formulario |
| store | POST /admin/distributions | Crear y ejecutar |
| show | GET /admin/distributions/{distribution} | Ver detalles |
| destroy | DELETE /admin/distributions/{distribution} | Eliminar |
| stop | POST /admin/distributions/{distribution}/stop | Detener |
| start | POST /admin/distributions/{distribution}/start | Iniciar |
| retryTarget | POST /admin/distributions/retry-target/{target} | Reintentar objetivo |
| update | PUT /admin/distributions/{distribution} | Actualizar |
| progress | GET /admin/distributions/{id}/progress | Progreso JSON |

### Vistas
- `resources/views/admin/distributions/index.blade.php`
- `resources/views/admin/distributions/create.blade.php`
- `resources/views/admin/distributions/show.blade.php`

### Migraciones
- `database/migrations/2026_01_23_000003_create_distributions_table.php`
- `database/migrations/2026_01_23_000004_create_distribution_files_table.php`
- `database/migrations/2026_01_23_000005_create_distribution_targets_table.php`
- `database/migrations/2026_03_11_173658_create_permission_tables.php` (índice)
- `database/migrations/2026_03_14_110000_add_scheduling_fields_to_distributions_table.php`
- `database/migrations/2026_03_26_000000_add_update_type_to_distributions_table.php`

### Servicio: `app/Services/DistributionService.php`
- `createDistribution(array $data, int $userId)` - Crea distribución
- `startDistribution(Distribution $distribution)` - Inicia distribución
- `sendDownloadCommand(DistributionTarget $target)` - Envia comando de descarga
- `processScheduledDistributions()` - Job para distribuciones programadas

---

## Reception

### Descripción
Sistema de recepción de archivos desde computadoras. Los agentes envían archivos al servidor según configuración.

### Tabla Principal
- **Tabla**: `receptions`

### Columnas
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigInteger | ID |
| name | string | Nombre de la recepción |
| description | text | Descripción |
| type | enum | Tipo (immediate, scheduled, recurring) |
| scheduled_at | timestamp | Fecha programada |
| scheduled_time | string | Hora programada |
| recurrence | string | Recurrencia |
| frequency_type | string | Tipo de frecuencia |
| frequency_interval | integer | Intervalo |
| week_days | json | Días de semana |
| file_types | json | Tipos de archivo a recibir |
| specific_files | json | Archivos específicos |
| all_files | boolean | Recibir todos los archivos |
| status | enum | Estado |
| group_id | foreignId | Grupo objetivo |
| last_run_at | timestamp | Última ejecución |
| next_run_at | timestamp | Próxima ejecución |

### Modelo: `app/Models/Reception.php`
```php
class Reception extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name', 'description', 'type', 'scheduled_at', 'scheduled_time',
        'recurrence', 'frequency_type', 'frequency_interval', 'week_days',
        'file_types', 'specific_files', 'all_files', 'status', 'group_id',
        'last_run_at', 'next_run_at'
    ];
    
    // Relaciones
    public function computers()
    public function targets()
    public function group()
    public function files()
}
```

### Tablas Relacionadas

#### reception_targets
| Campo | Tipo |
|-------|------|
| id | bigInteger |
| reception_id | foreignId |
| computer_id | foreignId |
| status | enum |
| progress | integer |
| error_message | text |
| attempts | integer |
| completed_at | timestamp |

#### reception_files
| Campo | Tipo |
|-------|------|
| id | bigInteger |
| reception_id | foreignId |
| file_name | string |
| file_path | string |
| file_size | bigInteger |

### Controlador: `app/Http/Controllers/ReceptionController.php`

#### Métodos
| Método | Ruta | Descripción |
|--------|------|-------------|
| index | GET /admin/reception | Listado con recepciones |
| create | GET /admin/reception/create | Formulario |
| store | POST /admin/reception | Crear |
| show | GET /admin/reception/{reception} | Ver detalles |
| showComputer | GET /admin/reception/computer/{computer} | Archivos en agente |
| destroy | DELETE /admin/reception/{reception} | Eliminar |
| stop | POST /admin/reception/{reception}/stop | Detener |
| start | POST /admin/reception/{reception}/start | Iniciar |
| retryTarget | POST /admin/reception/retry-target/{target} | Reintentar |

### Vistas
- `resources/views/admin/reception/index.blade.php`
- `resources/views/admin/reception/create.blade.php`
- `resources/views/admin/reception/show.blade.php`
- `resources/views/admin/reception/computer.blade.php`

### Migraciones
- `database/migrations/2026_03_12_200000_create_receptions_tables.php` (incluye targets y files)
- `database/migrations/2026_03_13_211904_add_schedule_fields_to_receptions_and_distributions_tables.php`
- `database/migrations/2026_03_13_213913_add_specific_files_to_receptions_table.php`
- `database/migrations/2026_03_13_220459_add_frequency_fields_to_receptions_table.php`
- `database/migrations/2026_03_13_230000_add_run_tracking_to_receptions_table.php`

---

## Agent Versions

### Descripción
Gestión de versiones del agente para actualizaciones.

### Tabla Principal
- **Tabla**: `agent_versions`

### Columnas
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigInteger | ID |
| version | string | Número de versión |
| channel | enum | Canal (stable, beta, alpha) |
| file_path | string | Ruta del archivo |
| checksum | string (64) | SHA256 del archivo |
| changelog | text | Notas de版本 |
| is_active | boolean | Versión activa |

### Modelo: `app/Models/AgentVersion.php`
```php
class AgentVersion extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'version', 'channel', 'file_path', 'checksum', 'changelog', 'is_active'
    ];
}
```

### Controlador: `app/Http/Controllers/AgentVersionsController.php`

#### Métodos
| Método | Ruta | Descripción |
|--------|------|-------------|
| index | GET /admin/agent-versions | Listado |
| create | GET /admin/agent-versions/create | Formulario |
| store | POST /admin/agent-versions | Crear versión |
| destroy | DELETE /admin/agent-versions/{agentVersion} | Desactivar |

### Vistas
- `resources/views/admin/agent-versions/index.blade.php`
- `resources/views/admin/agent-versions/create.blade.php`

### Migraciones
- `database/migrations/2026_01_23_000006_create_agent_versions_table.php`

### Servicio: `app/Services/AgentUpdateService.php`
- `createVersion(array $data)` - Crea nueva versión
- `deactivateVersion(AgentVersion $version)` - Desactiva versión

---

## Reporte DBF Files

### Descripción
Reporte de archivos DBF monitoreados en cada computadora. Lee la configuración del agente (`agent_config.dbf_files`).

### Fuente de Datos
- Tabla `computers` - columna `agent_config` (JSON)
- Campo: `agent_config.dbf_files[]`

### Estructura de `agent_config.dbf_files`
```json
[
  {
    "name": "ARTICULOS.DBF",
    "path": "C:\\path\\to\\file\\ARTICULOS.DBF",
    "size": 123456,
    "modified": "2026-04-01 10:30:00"
  }
]
```

### Controlador: `app/Http/Controllers/ReporteDbfFilesController.php`

#### Métodos
| Método | Ruta | Descripción |
|--------|------|-------------|
| index | GET /reportes/dbf-files | Vista principal |
| data | GET /reportes/dbf-files/data | Datos DataTable |
| export | GET /reportes/dbf-files/export | Exportar CSV |

#### Filtros Disponibles
- Plaza (basado en nombre del grupo)
- Grupo
- Archivo DBF específico

#### Métodos Privados
- `getUniqueFiles()` - Obtiene lista de archivos DBF únicos
- `formatAgentModifiedTime($modified)` - Formatea fecha del agente
- `excelTextValue($value)` - Prepara valor para Excel

### Vista
- `resources/views/reportes/dbf-files/index.blade.php`

---

## Relaciones entre Modelos

```
Group (1) ──────< Computer (N)
     │
     └────< Reception (N)

Computer (1) ──────< DistributionTarget (N) <── Distribution (N)
     │
     └────< ReceptionTarget (N) <── Reception (N)
     │
     └────< Command (N)
     │
     └────< ComputerLog (N)

Distribution (1) ──────< DistributionFile (N)
                        └─────< DistributionTarget (N) ──────< Computer

Reception (1) ──────< ReceptionFile (N)
                    └─────< ReceptionTarget (N) ──────< Computer
```

---

## Rutas API/Web

### Tiendas
```
GET    /admin/tiendas              -> TiendasController@index
GET    /admin/tiendas/data         -> TiendasController@data
POST   /admin/tiendas              -> TiendasController@store
GET    /admin/tiendas/{tienda}    -> TiendasController@show
PUT    /admin/tiendas/{tienda}     -> TiendasController@update
DELETE /admin/tiendas/{tienda}     -> TiendasController@destroy
```

### Computers
```
GET    /admin/computers                      -> ComputersController@index
GET    /admin/computers/export               -> ComputersController@export
GET    /admin/computers/{computer}           -> ComputersController@show
GET    /admin/computers/{computer}/edit      -> ComputersController@edit
PUT    /admin/computers/{computer}           -> ComputersController@update
DELETE /admin/computers/{computer}           -> ComputersController@destroy
GET    /admin/computers/{computer}/logs      -> ComputersController@logs
GET    /admin/computers/{computer}/status   -> ComputersController@status
```

### Groups
```
GET    /admin/groups                  -> GroupsController@index
GET    /admin/groups/create          -> GroupsController@create
POST   /admin/groups                 -> GroupsController@store
GET    /admin/groups/{group}         -> GroupsController@show
GET    /admin/groups/{group}/edit    -> GroupsController@edit
PUT    /admin/groups/{group}         -> GroupsController@update
DELETE /admin/groups/{group}         -> GroupsController@destroy
POST   /admin/groups/import          -> GroupsController@importExcel
GET    /admin/groups/export          -> GroupsController@export
```

### Distributions
```
GET    /admin/distributions                           -> DistributionsController@index
GET    /admin/distributions/create                   -> DistributionsController@create
POST   /admin/distributions                           -> DistributionsController@store
GET    /admin/distributions/{distribution}           -> DistributionsController@show
PUT    /admin/distributions/{distribution}           -> DistributionsController@update
DELETE /admin/distributions/{distribution}           -> DistributionsController@destroy
POST   /admin/distributions/{distribution}/stop      -> DistributionsController@stop
POST   /admin/distributions/{distribution}/start    -> DistributionsController@start
POST   /admin/distributions/retry-target/{target}   -> DistributionsController@retryTarget
GET    /admin/distributions/{id}/progress           -> DistributionsController@progress
```

### Reception
```
GET    /admin/reception                           -> ReceptionController@index
GET    /admin/reception/create                    -> ReceptionController@create
POST   /admin/reception                           -> ReceptionController@store
GET    /admin/reception/{reception}               -> ReceptionController@show
DELETE /admin/reception/{reception}               -> ReceptionController@destroy
POST   /admin/reception/{reception}/stop          -> ReceptionController@stop
POST   /admin/reception/{reception}/start         -> ReceptionController@start
GET    /admin/reception/computer/{computer}       -> ReceptionController@showComputer
POST   /admin/reception/retry-target/{target}     -> ReceptionController@retryTarget
```

### Agent Versions
```
GET    /admin/agent-versions                 -> AgentVersionsController@index
GET    /admin/agent-versions/create         -> AgentVersionsController@create
POST   /admin/agent-versions                -> AgentVersionsController@store
DELETE /admin/agent-versions/{agentVersion}  -> AgentVersionsController@destroy
```

### Reporte DBF Files
```
GET    /reportes/dbf-files               -> ReporteDbfFilesController@index
GET    /reportes/dbf-files/data         -> ReporteDbfFilesController@data
GET    /reportes/dbf-files/export       -> ReporteDbfFilesController@export
```

---

## Notas de Implementación

1. **Tiendas**: Tabla externa `bi_sys_tiendas` - NO es una tabla Laravel, viene de base de datos externa del sistema original.

2. **Computers**: Usa `SoftDeletes` - los registros no se eliminan físicamente.

3. **Distributions y Reception**: Soportan tres tipos de ejecución:
   - `immediate`: Ejecución inmediata
   - `scheduled`: Programado una vez
   - `recurring`: Recurrente (diario, semanal, mensual, etc.)

4. **BitLocker**: Almacenado como JSON en la columna `bitlocker_status`.

5. **Download Paths**: Soporta hasta 10 rutas de descarga más la principal, más rutas adicionales en `agent_config`.

6. **Receive Paths**: Configuración de rutas donde el agente debe buscar archivos para enviar.

7. **DBF Files**: Se monitorean a través de `agent_config.dbf_files` en cada computadora.