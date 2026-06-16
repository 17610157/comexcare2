# 📦 COMEXCARE

> **Sistema de Gestión Distribuida de Equipos y Reportes de Ventas**
> Laravel 12 · PHP 8.3 · PostgreSQL · Redis · AdminLTE 3

---

## 📋 Descripción

**COMEXCARE** es un sistema empresarial para **Comex** (empresa mexicana de recubrimientos) que permite:

- **Gestión remota de equipos** — Agentes instalados en tiendas a nivel nacional
- **Distribución de archivos** — Actualizaciones de agente, binarios, archivos PVSI, DBF
- **Recepción de archivos** — Recolección de datos de ventas, vales, inventarios
- **Reportes de ventas** — 12+ tipos de reportes con múltiples capas de cache
- **Soporte 500+ usuarios concurrentes** con tiempos de respuesta <3s

---

## ⚙️ Stack Tecnológico

| Tecnología | Versión | Propósito |
|---|---|---|
| **Laravel** | 12.x | Framework PHP |
| **PHP** | 8.3.30 | Lenguaje base |
| **PostgreSQL** | — | Base de datos primaria |
| **Redis** | — | Cache / Queue / Broadcast |
| **AdminLTE 3** | — | Panel administrativo |
| **Tailwind CSS** | 4.x | Estilos CSS |
| **Vite** | 7.x | Bundler frontend |
| **Laravel Horizon** | 5.x | Monitor de colas |
| **Laravel Echo** | 2.x | WebSockets (Socket.io) |
| **Pest** | 4.x | Testing |
| **DOMPDF** | — | Generación de PDF |
| **Maatwebsite/Laravel-Excel** | — | Import/Export Excel |

---

## 🏗️ Arquitectura

```
comexcare/
├── app/
│   ├── Channels/              # Autorización de broadcast
│   ├── Console/Commands/      # 12 comandos Artisan
│   ├── Events/                # DistributionProgressUpdated
│   ├── Exports/               # 8 clases Excel export
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/           # AgentController (996 líneas), ValeController, ResurtidoAgent
│   │   │   ├── Auth/          # Login, Register, Password, Verification
│   │   │   └── Reportes/      # CarteraAbonos*, NotasCompletas, ClubComex...
│   │   └── Middleware/        # ApiRateLimiter, AuditMiddleware
│   ├── Imports/               # MetasMensualImport
│   ├── Jobs/                  # ProcessDistribution, CarteraAbonosSync, etc.
│   ├── Models/                # 18 modelos Eloquent
│   ├── Notifications/         # AgentOffline, DistributionCompleted, SyncCompleted
│   ├── Services/              # ReportService (1026 líneas), DistributionService, CarteraAbonos*
├── bootstrap/app.php          # Middleware, routing
├── config/                    # 19 archivos de configuración
├── database/
│   ├── factories/             # 8 factories
│   ├── migrations/            # 65 migraciones
│   └── seeders/               # 7 seeders
├── resources/views/           # 70+ plantillas Blade
├── routes/
│   ├── web.php                # 361 líneas — rutas web
│   ├── api.php                # 122 líneas — rutas API
│   ├── channels.php           # Canales de broadcast
│   ├── console.php            # Tareas programadas
│   └── cartera_abonos_*.php   # 3 archivos de rutas optimizadas
├── tests/                     # 16 Feature + 10 Unit
└── websocket-server.js        # Servidor Node.js WebSocket (puerto 6001)
```

---

## 🗄️ Modelos (18)

| Modelo | Tabla | Propósito |
|---|---|---|
| `User` | users | Usuarios del sistema (name, email, plaza, tienda, activo) |
| `Computer` | computers | Inventario de equipos (40+ columnas) |
| `Group` | groups | Grupos de computadoras |
| `GroupShortKey` | group_short_keys | Short keys por grupo |
| `Distribution` | distributions | Distribuciones de archivos/actualizaciones |
| `DistributionFile` | distribution_files | Archivos en una distribución |
| `DistributionTarget` | distribution_targets | Estado por computadora objetivo |
| `Reception` | receptions | Trabajos de recepción |
| `ReceptionFile` | reception_files | Archivos a recibir |
| `ReceptionTarget` | reception_targets | Estado por computadora (recepción) |
| `Command` | commands | Comandos enviados a agentes |
| `ComputerLog` | computer_logs | Logs de agentes |
| `AgentVersion` | agent_versions | Versiones del agente distribución |
| `ResurtidoAgentVersion` | resurtido_agent_versions | Versiones del agente resurtido |
| `Vale` | vales | Registro de vales (25+ campos) |
| `MetaMensual` | metas_mensual | Metas mensuales por plaza/tienda/periodo |
| `AuditLog` | audit_logs | Bitácora de auditoría |
| `FileList` | file_lists | Listas blancas/negras |
| `UserPlazaTienda` | user_plaza_tiendas | Asignación usuario ↔ tienda |

### Tablas de Cache para Reportes

- `cartera_abonos_cache`
- `vendedores_cache`
- `metas_cache`
- `notas_completas_cache`
- `compras_directo_cache`

---

## 🎮 Controladores

### Web (19 controladores)

| Controlador | Métodos clave | Propósito |
|---|---|---|
| `HomeController` | `index()`, `stats()` | Dashboard |
| `UserController` | CRUD completo | Usuarios |
| `RoleController` | CRUD + `allPermissions()` | Roles |
| `PermissionController` | CRUD + `sync()` | Permisos ACL |
| `ComputersController` | CRUD + `logs()`, `status()`, `export()` | Inventario de equipos |
| `GroupsController` | CRUD + `importExcel()`, `export()` | Grupos |
| `DistributionsController` | CRUD + `stop()`, `start()`, `restart()`, `retryTarget()`, `progress()` | Distribuciones |
| `ReceptionController` | CRUD + `stop()`, `start()`, `retryTarget()` | Recepciones |
| `AgentVersionsController` | CRUD (restringido) | Versiones de agente |
| `ResurtidoAgentVersionsController` | CRUD + `deploy()` | Versiones resurtido |
| `FileReceptionController` | CRUD | Subida de archivos |
| `FileListsController` | CRUD + `validateFiles()` | Listas de archivos |
| `UserPlazaTiendaController` | `edit()`, `update()`, `getTiendas()` | Asignación tiendas |
| `TiendasController` | CRUD | Catálogo de tiendas |
| `MetasMensualController` | CRUD + `import()`, `generarMetas()`, `generateDias()` | Metas mensuales |
| `MetricsController` | `index()`, `health()` | Métricas del sistema |

### Reportes Web (10 controladores)

| Controlador | Propósito |
|---|---|
| `ReporteVendedoresController` | Rendimiento de vendedores |
| `ReporteVendedoresB2bController` | Reporte B2B |
| `ReporteVendedoresMatricialController` | Matricial vendedores |
| `ReporteMetasVentasController` | Metas de ventas |
| `ReporteMetasMatricialController` | Matricial metas |
| `ReporteDesgloseController` | Desglose |
| `ReporteComprasDirectoController` | Compras directo |
| `ReporteDbfFilesController` | Monitoreo DBF |
| `ReporteValesController` | Vales |
| `ReporteCarteraAbonosController` | Cartera abonos (legacy) |

### Reportes Avanzados (app/Http/Controllers/Reportes/)

| Controlador | Propósito |
|---|---|
| `CarteraAbonosController` | Cartera de abonos (nuevo) |
| `CarteraAbonosOptimizedController` | Versión con cache inteligente |
| `CarteraAbonosRealtimeController` | Versión con vistas materializadas |
| `CarteraAbonosUltraFastController` | Versión ultra-rápida (500+ usuarios) |
| `NotasCompletasController` | Notas completas |
| `ClubComexController` | Sincronización Club Comex |
| `ReporteRedencionesClubController` | Redenciones del club |

### API (3 controladores)

| Controlador | Rutas | Propósito |
|---|---|---|
| `AgentController` (996 líneas) | `/api/*` | Comunicación con agentes remotos: register, heartbeat, commands, report, download, inventory, upload, logs, pvsi |
| `ResurtidoAgentController` | `/api/resurtido/*` | API del agente de resurtido |
| `ValeController` (449 líneas) | `/api/vales` | CRUD de vales con soporte batch |

---

## 🔌 API Routes

### Agente (sin auth, sin CSRF)

```
GET    /api/register              → AgentController@register
POST   /api/heartbeat             → AgentController@heartbeat
GET    /api/commands/{id}         → AgentController@getCommands
POST   /api/report                → AgentController@report
GET    /api/download/{fileId}     → AgentController@download
GET    /api/update/{version}      → AgentController@checkUpdate
POST   /api/inventory             → AgentController@inventory
POST   /api/upload-reception      → AgentController@uploadReception
```

### API Pública

```
POST   /api/register              POST   /api/heartbeat
POST   /api/report                POST   /api/agent/report
POST   /api/inventory             POST   /api/logs
PATCH  /api/pvsi-update           GET    /api/computer/{id}/config
POST   /api/resurtido/*           GET    /api/vales
POST   /api/vales/batch           POST   /api/vales/reset-sync
GET    /api/metrics               GET    /api/health
GET    /api/computers/online-status
```

### Cartera Abonos Ultra-Fast API

```
GET    /api/v1/cartera-abonos-ultra-fast/preload
GET    /api/v1/cartera-abonos-ultra-fast/stats
GET    /api/v1/cartera-abonos-ultra-fast/health
GET    /api/v1/cartera-abonos-ultra-fast/export
```

---

## 🔧 Servicios

| Servicio | Líneas | Propósito |
|---|---|---|
| `ReportService` | 1026 | Centraliza reportes con SQL optimizado + cache Redis |
| `DistributionService` | — | Gestión de distribuciones de archivos |
| `AgentUpdateService` | — | Versiones y deploy del agente de distribución |
| `ResurtidoAgentUpdateService` | — | Versiones del agente de resurtido |
| `CarteraAbonosCacheService` | 318 | Cache optimizado para cartera de abonos |
| `CarteraAbonosMaterializedService` | — | Vistas materializadas PostgreSQL |
| `CarteraAbonosUltraFastService` | — | Pre-carga ultra-rápida |
| `DashboardCacheService` | — | Cache del dashboard |

---

## ⚡ Jobs (Colas)

| Job | Cola | Timeout | Reintentos |
|---|---|---|---|
| `ProcessDistributionJob` | distributions | 300s | 3 |
| `CarteraAbonosSyncJob` | default | — | — |
| `CarteraAbonosIncrementalUpdateJob` | default | — | — |
| `ProcessScheduledDistributions` | distributions | — | — |
| `ProcessScheduledReceptions` | receptions | — | — |
| `RetryDistribution` | retry | — | — |

---

## ⌨️ Comandos Artisan (12)

| Comando | Descripción |
|---|---|
| `computers:check-status {--minutes=5}` | Marca equipos offline sin heartbeat |
| `cartera-abonos:sync-cache` | Sincroniza cache de cartera |
| `db:backup` | Respaldo de base de datos |
| `distribution:restart` | Reinicia distribuciones |
| `queue:start-workers` | Inicia workers de cola |
| `sync:all-cache` | Sincroniza todas las tablas cache |
| `sync:cache-full` | Sincronización completa |
| `sync:cache-incremental` | Sincronización incremental |
| `sync:club-comex` | Sincroniza Club Comex |
| `sync:cartera-abonos-cache` | Cache cartera abonos |
| `sync:compras-directo-cache` | Cache compras directo |
| `sync:notas-completas-cache` | Cache notas completas |

### Tareas Programadas

| Frecuencia | Tarea |
|---|---|
| Cada minuto | `ProcessScheduledDistributions` |
| Cada minuto | `ProcessScheduledReceptions` |
| Cada 5 min | `computers:check-status --minutes=5` |
| 2×/día (00:00, 11:00) | `sync:cache-incremental` |
| Diario 11:00 | `cartera-abonos:sync-cache --last-days=60` |
| Diario 11:00 | `notas-completas:sync-cache --last-days=60` |
| Diario 11:00 | `compras-directo:sync-cache --last-days=60` |

---

## 📤 Exports / Imports

### Exports (8)

- `VendedoresExport` — FromCollection con estilos
- `VendedoresB2bExport` — FromCollection
- `VendedoresMatricialExport` — FromCollection (279 líneas)
- `MetasVentasExport` — FromArray (164 líneas)
- `MetasMatricialExport` — FromCollection (144 líneas)
- `CarteraAbonosExport` — FromQuery
- `NotasCompletasExport` — FromQuery
- `ComprasDirectoExport` — FromCollection (173 líneas)

### Imports (2)

- `MetasMensualImport` — ToCollection + WithHeadingRow
- `MetaMensualImport` — ToCollection

---

## 🖼️ Vistas (70+ Blade)

**Layout:** `layouts/app.blade.php` (AdminLTE 3)

**Admin:** computers/, groups/, distributions/, reception/, agent-versions/, resurtido-agent-versions/, file-receptions/, file-lists/, roles/, permissions/, tiendas/, user-plaza-tienda/, usuarios/, metas_mensual/

**Reportes:** vendedores/, vendedores_b2b/, vendedores_matricial/, metas_ventas/, metas_matricial/, desglose/, cartera_abonos/ (6 vistas), notas_completas/, club_comex/, redenciones_club/, compras/directo/, dbf-files/, vales/

**Auth:** login, register, verify, passwords/* (6 templates)

**Errores:** 401, 402, 403, 404, 419, 429, 500, 503

---

## 🛡️ Seguridad

| Middleware | Alias | Propósito |
|---|---|---|
| `ApiRateLimiter` | `api.rate_limit` | Rate limiting por tipo de request |
| `AuditMiddleware` | `audit` | Auditoría completa (DB + archivo) |
| `ReleaseDatabaseConnection` | web group | Libera conexión DB post-request |
| `ReporteCacheMiddleware` | — | Cachea respuestas de reportes |

**Autenticación:** Laravel UI scaffolding (login, register, password reset, email verification)
**Autorización:** Spatie Laravel Permission (roles + permisos ACL) + Gates personalizados

---

## ⚡ Tiempo Real

| Componente | Detalle |
|---|---|
| `Events/DistributionProgressUpdated` | Broadcast en canales privados + público |
| `Channels/DistributionChannel.php` | Autorización de canales |
| `websocket-server.js` | Servidor Node.js (ws + Redis pub/sub, puerto 6001) |
| `resources/js/bootstrap.js` | Laravel Echo + Socket.io |
| `soketi.json` | Config alternativa con Soketi |

---

## 🧪 Tests (26 total)

### Feature (16)

- `ExampleTest.php`
- `AllReportsTest.php`
- `CarteraAbonos*Test.php` (8 archivos)
- `MetasModuleFunctionalTest.php`
- `MetasModuleTest.php`
- `ReportesPerformanceTest.php`
- `ReportIntegrationTest.php`
- `DistributionsControllerTest.php`
- `AgentControllerTest.php`

### Unit (10)

- `Jobs/`: ProcessScheduledDistributionsJobTest, RetryDistributionJobTest
- `Middleware/`: ApiRateLimiterTest
- `Models/`: AuditLogTest, DistributionFileTest, DistributionTargetTest, DistributionTest
- `Services/`: DashboardCacheServiceTest, DistributionServiceTest

**Trait:** `RequiresExternalTables.php` — Skipea tests si faltan tablas externas

---

## 📊 Reportes de Ventas

| Reporte | Cache | Formatos |
|---|---|---|
| Vendedores | ✅ Redis | HTML, Excel, CSV, PDF |
| Vendedores B2B | ✅ Redis | HTML, Excel, CSV, PDF |
| Vendedores Matricial | ✅ Redis | HTML, Excel, CSV, PDF |
| Metas Ventas | ✅ Redis | HTML, Excel, CSV, PDF |
| Metas Matricial | ✅ Redis | HTML, Excel, PDF |
| Cartera Abonos | ✅ 3 capas | HTML, Excel, CSV, PDF |
| Notas Completas | ✅ Redis | HTML, Excel, CSV |
| Compras Directo | ✅ Redis | HTML, Excel, CSV, PDF |
| Club Comex | — | HTML, CSV |
| Redenciones Club | ✅ Redis | HTML, Excel, CSV |
| Vales | — | HTML, Excel |
| DBF Files | — | HTML, Excel |

---

## 🚀 Optimizaciones de Rendimiento

- **Cache escalonado:** Redis (consultas frecuentes) + tablas cache (pre-agregadas) + vistas materializadas
- **ReportService centralizado:** Consultas SQL optimizadas con índices compuestos
- **Procesamiento en chunks:** Para conjuntos de datos grandes (>7,000 registros)
- **Cache automático:** TTL de 15-60 min con invalidación por comando Artisan
- **Sincronización incremental:** Jobs diferidos para mantener cache actualizado
- **Rate limiting:** Protección contra abuso en endpoints públicos
- **Tiempos típicos:** 7,000 registros en ~2-3 segundos (vs 40s original)

---

## 📄 Documentación HTML

Existe una versión HTML navegable de esta documentación:

```
documentacion.html
```

Abrir en cualquier navegador para una experiencia mejorada con sidebar de navegación,
sintaxis coloreada de código, tablas estilizadas y diseño responsivo.

---

## 🔗 Enlaces Útiles

- **Laravel 12 Docs:** https://laravel.com/docs/12.x
- **AdminLTE 3:** https://adminlte.io/
- **Spatie Permissions:** https://spatie.be/docs/laravel-permission
- **Laravel Horizon:** https://laravel.com/docs/horizon
- **Laravel Echo:** https://laravel.com/docs/broadcasting
- **Pest Testing:** https://pestphp.com/
