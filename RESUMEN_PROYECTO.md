# COMEXCARE — Resumen del Proyecto

## Objetivo del Proyecto

**COMEXCARE** es un sistema de gestión empresarial diseñado para **Comex** (tiendas de pintura y recubrimientos en México). Su propósito es doble:

1. **Gestión remota de agentes de software** — Administrar agentes instalados en computadoras de tiendas para distribución de archivos, actualizaciones de software, recepción de archivos y monitoreo de inventario.
2. **Inteligencia de Negocio y Reportes** — Proporcionar reportes de ventas, seguimiento de vendedores, gestión de metas, vales, y tableros analíticos.

---

## Stack Tecnológico

| Tecnología | Versión |
|---|---|
| **PHP** | ^8.2 (8.3.30) |
| **Laravel Framework** | ^12.0 |
| **Base de datos** | PostgreSQL (producción), MySQL/MariaDB/SQLite/SQL Server disponibles |
| **Cache** | Database (por defecto), Redis, File, Memcached |
| **Colas (Queue)** | Redis + Laravel Horizon |
| **Tiempo real** | Redis + Socket.IO + Laravel Echo |
| **Frontend** | Blade + AdminLTE 3 (Bootstrap 5 + jQuery) + Vite |
| **CSS** | Tailwind CSS v4, Sass |
| **JS** | Vanilla JS, Axios, Laravel Echo, Socket.IO client |
| **PDF** | barryvdh/laravel-dompdf |
| **Excel/CSV** | maatwebsite/laravel-excel v3.1 |
| **RBAC** | spatie/laravel-permission v7.1 |
| **Panel Admin** | jeroennoten/laravel-adminlte v3.15 |
| **Testing** | Pest v4.3 + PHPUnit v12 |
| **Dev Tools** | Laravel Sail, Laravel Pint, Laravel Boost, Laravel Pail |
| **Asset Bundling** | Vite + laravel-vite-plugin |

---

## Dependencias Principales

### Producción (`composer.json`)
- `laravel/framework: ^12.0`
- `spatie/laravel-permission: ^7.1`
- `jeroennoten/laravel-adminlte: ^3.15`
- `maatwebsite/excel: ^3.1`
- `barryvdh/laravel-dompdf`
- `laravel/horizon`
- `laravel/tinker: ^2.10.1`
- `laravel/ui: ^4.6`

### Desarrollo (`composer.json`)
- `pestphp/pest: ^4.3`
- `pestphp/pest-plugin-laravel: ^4.0`
- `laravel/pint: ^1.24`
- `laravel/sail: ^1.41`
- `laravel/boost: ^1.8`
- `laravel/pail: ^1.2.2`
- `nunomaduro/collision: ^8.6`
- `mockery/mockery: ^1.6`
- `fakerphp/faker: ^1.23`

### Frontend (`package.json`)
- `laravel-echo: ^2.3.1`
- `pusher-js: ^8.4.3`
- `socket.io: ^4.8.3`
- `socket.io-client: ^4.8.3`
- `redis: ^5.11.0`
- `tailwindcss: ^4.0.0`
- `@tailwindcss/vite: ^4.0.0`
- `bootstrap: ^5.2.3`
- `vite: ^7.0.7`
- `@soketi/soketi: ^1.6.1`
- `concurrently: ^9.0.1`
- `sass: ^1.56.1`

---

## Arquitectura del Sistema

### Hub-and-Spoke (Centralizado)

```
┌───────────────────────────────────────────────────┐
│              SERVIDOR CENTRAL (Laravel)             │
│  ┌─────────┐  ┌──────────┐  ┌──────────────────┐  │
│  │ Laravel  │  │  Redis   │  │  PostgreSQL      │  │
│  │ App      │  │  Queue   │  │  (Principal)     │  │
│  ├─────────┤  │  +Cache  │  ├──────────────────┤  │
│  │Horizon  │  │  +Broad  │  │  Tablas del      │  │
│  │Workers  │  │  cast    │  │  ERP/Core BI     │  │
│  └─────────┘  └──────────┘  └──────────────────┘  │
│                                                     │
│  ┌─────────────────────────────────────────────┐   │
│  │           Socket.IO Server                   │   │
│  │  (websocket-server.js / socket-server.cjs)   │   │
│  └─────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────┘
              ▲                        │
              │ HTTP/API               │ HTTP/API
              │ (REST)                 │ (REST)
              │                        ▼
   ┌──────────────────┐    ┌──────────────────────┐
   │   CareAgent       │    │   ResurtidoAgent      │
   │   (Windows)       │    │   (Windows)           │
   │   - Distribución  │    │   - Inventario        │
   │   - Actualización │    │   - Reabastecimiento  │
   │   - Monitoreo     │    │   - Comandos          │
   └──────────────────┘    └──────────────────────┘
```

### Flujo de comunicación

1. Los **agentes remotos** (CareAgent y ResurtidoAgent) se ejecutan en las computadoras de las tiendas.
2. Se comunican con el servidor vía **API REST** (sin CSRF, con rate limiting).
3. El servidor almacena archivos, gestiona distribuciones, recibe reportes.
4. **Redis** maneja colas de trabajos (Horizon) y broadcasting en tiempo real.
5. **Socket.IO** transmite el progreso de distribuciones en vivo al navegador.
6. Los reportes de negocio se sirven al usuario via **Blade + AdminLTE** con datos cacheados.

---

## Funcionalidades Clave

### 1. Distribución Remota de Archivos
- Subir archivos al servidor y distribuirlos a grupos/computadoras específicas.
- Programación de distribuciones (una vez, diaria, semanal, mensual, por hora).
- Seguimiento de progreso en tiempo real vía WebSockets.
- Reintentos automáticos y gestión de errores por destino.
- Tipos de distribución: archivos, comandos remotos, actualizaciones de agente.

### 2. Recepción Remota de Archivos
- Agentes pueden subir archivos al servidor.
- Programación y frecuencia configurables.
- Seguimiento de estado por computadora destino.

### 3. Gestión de Agentes (CareAgent y ResurtidoAgent)
- Registro automático de agentes via heartbeat.
- Versionado y despliegue de actualizaciones.
- Comandos remotos (ejecutar scripts, actualizar, etc.).
- Monitoreo de estado (online/offline).

### 4. Inventario de Computadoras
- Sistema operativo, versión de Windows, espacio en disco.
- Estado de BitLocker, versión de PVSI.
- Paths de descarga/configuración.
- Logs de actividad por computadora.

### 5. Grupos de Computadoras
- Agrupación lógica de equipos para distribución segmentada.
- Claves cortas (short keys) para identificación de grupo.
- Import/export via Excel.

### 6. Sistema de Reportes de Negocio
| Reporte | Descripción |
|---|---|
| **Vendedores** | Rendimiento de vendedores por tienda/plaza |
| **Vendedores B2B/VDT** | Ventas business-to-business |
| **Vendedores Matricial** | Ventas en formato matricial |
| **Metas de Ventas** | Cumplimiento de metas vs real |
| **Metas Matricial** | Metas en formato matricial |
| **Cartera Abonos** | Cartera de abonos (3 variantes: optimizado, tiempo real, ultra-rápido) |
| **Notas Completas** | Detalle completo de notas de venta |
| **Club Comex** | Programa de lealtad Club Comex |
| **Redenciones Club** | Canjes de puntos realizados |
| **Compras Directo** | Compras directas realizadas |
| **DBF Files** | Archivos DBF generados por computadora |
| **Vales (Vouchers)** | Gestión de vales/descuentos/CUPON |
| **Desglose** | Desglose detallado de métricas |

### 7. Metas Mensuales
- Importación masiva via Excel.
- Generación automática de días por período.
- CRUD completo con asignación por tienda/plaza.

### 8. Control de Acceso (RBAC)
- Roles y permisos granulares con Spatie Laravel Permission.
- Protección por permisos en cada ruta y vista.
- Super Admin, Admin y roles personalizables.
- Asignación de usuarios a plazas/tiendas específicas.

### 9. Auditoría
- Registro de todas las peticiones API entrantes.
- Logs de actividad en archivos dedicados.

### 10. Sincronización Automática
- Sincronización de cachés para reportes (incremental y completa).
- Sincronización diaria de cartera abonos, notas completas, compras directo.
- Sincronización de Club Comex y reddenciones.

### 11. Tiempo Real
- Progreso de distribuciones via WebSockets (Socket.IO).
- Dashboard con estadísticas en vivo.
- Canal broadcasting con Laravel Echo.

---

## Estructura del Proyecto

```
app/
├── Channels/          → Canales de broadcasting
├── Console/Commands/  → 12 comandos Artisan personalizados
├── Events/            → Eventos (ej. DistributionProgressUpdated)
├── Exports/           → 8 clases de exportación Excel/CSV
├── Helpers/           → Helpers (RoleHelper)
├── Http/
│   ├── Controllers/   → 46 controladores
│   │   ├── Api/       → API para agentes remotos
│   │   ├── Auth/      → Autenticación
│   │   └── Reportes/  → Reportes de negocio
│   └── Middleware/     → 4 middlewares personalizados
├── Imports/           → Importaciones Excel
├── Jobs/              → 6 jobs de cola
├── Models/            → 23 modelos Eloquent
├── Notifications/     → 3 notificaciones
├── Providers/         → AppServiceProvider, HorizonServiceProvider
└── Services/          → 8 servicios de negocio

bootstrap/
├── app.php            → Configuración de middleware, excepciones, rutas
└── providers.php      → Service Providers registrados

config/                → 19 archivos de configuración
database/
├── factories/         → 8 factories
├── migrations/        → 55 migraciones
└── seeders/           → 7 seeders

resources/views/       → Vistas Blade organizadas por módulo
routes/                → 7 archivos de rutas
tests/                 → Tests con Pest v4
```

---

## Modelos de Datos Principales (23 modelos)

- **User** — Usuarios con roles, permisos, asignación plaza/tienda
- **Computer** — Computadoras remotas con info de sistema y agente
- **ComputerLog** — Logs de actividad por computadora
- **Distribution / DistributionFile / DistributionTarget** — Distribuciones de archivos
- **Reception / ReceptionFile / ReceptionTarget** — Recepciones de archivos
- **Group / GroupShortKey** — Grupos de computadoras
- **AgentVersion / ResurtidoAgentVersion** — Versiones de agentes
- **Vale** — Vales/CUPON desde POS
- **MetasMensual / MetaMensual** — Metas mensuales
- **AuditLog** — Registro de auditoría
- **UserPlazaTienda** — Asignación usuario-plaza-tienda
- **FileList** — Listados de archivos
- **AsesoresVvt / Canota** — Datos de ventas (vendedores y notas)

---

## Middleware Personalizados

| Middleware | Propósito |
|---|---|
| `ApiRateLimiter` | Rate limiting por endpoint para APIs de agentes |
| `AuditMiddleware` | Logea todas las peticiones API entrantes |
| `ReleaseDatabaseConnection` | Libera conexiones BD post-petición |
| `ReporteCacheMiddleware` | Cacheo de respuestas en reportes |

---

## Comandos Artisan (12)

| Comando | Función |
|---|---|
| `cartera-abonos:sync-cache` | Sincroniza caché de cartera abonos |
| `computers:check-status` | Verifica estado online de computadoras |
| `db:backup` | Respaldo de base de datos |
| `restart:distribution` | Reintenta distribuciones fallidas |
| `queue:start-workers` | Inicia workers de cola |
| `sync:cache-full` | Sincronización completa de cachés |
| `sync:cache-incremental` | Sincronización incremental (cada 12h) |
| `sync:cartera-abonos-cache` | Sincroniza caché de cartera abonos |
| `sync:club-comex` | Sincroniza datos de Club Comex |
| `sync:compras-directo-cache` | Sincroniza caché de compras directo |
| `sync:notas-completas-cache` | Sincroniza caché de notas completas |
| `sync:all-cache-tables` | Sincroniza todas las tablas de caché |

---

## Sistema de Colas (Horizon)

- **Redis** como driver de colas.
- **2 supervisores**: `distributions` (timeout 300s) y `default` (timeout 60s).
- Hasta **32 procesos** por supervisor en producción.
- Jobs programados cada minuto para distribuciones y recepciones programadas.

---

## URLs Clave (Rutas Web)

| Ruta | Propósito |
|---|---|
| `/home` | Dashboard principal |
| `/admin/usuarios` | Gestión de usuarios |
| `/admin/roles` | Gestión de roles |
| `/admin/permissions` | Gestión de permisos |
| `/admin/distributions` | Distribuciones de archivos |
| `/admin/computers` | Inventario de computadoras |
| `/admin/groups` | Grupos de computadoras |
| `/admin/reception` | Recepciones de archivos |
| `/admin/agent-versions` | Versiones de CareAgent |
| `/admin/resurtido-agent-versions` | Versiones de ResurtidoAgent |
| `/admin/tiendas` | Catálogo de tiendas |
| `/admin/file-lists` | Listados de archivos |
| `/metas-mensual` | Metas mensuales |
| `/reportes/vendedores` | Reportes de vendedores |
| `/reportes/cartera-abonos` | Reporte cartera abonos |
| `/reportes/vales` | Reporte de vales |
| `/reportes/club-comex` | Reporte Club Comex |

---

## APIs de Agentes (Endpoints Clave)

| Endpoint | Método | Propósito |
|---|---|---|
| `/api/register` | POST | Registro de agente |
| `/api/heartbeat` | POST | Heartbeat del agente |
| `/api/commands/{id}` | GET | Obtener comandos pendientes |
| `/api/report` | POST | Reporte de estado |
| `/api/download/{fileId}` | GET | Descargar archivo distribuido |
| `/api/update/{version}` | GET | Verificar actualización |
| `/api/inventory` | POST | Reporte de inventario |
| `/api/upload-reception` | POST | Subir archivos recibidos |
| `/api/resurtido/register` | POST | Registro agente resurtido |
| `/api/resurtido/heartbeat` | POST | Heartbeat agente resurtido |
| `/api/resurtido/commands/{id}` | GET | Comandos agente resurtido |
| `/api/vales` | GET/POST | CRUD de vales |
| `/api/metrics` | GET | Métricas del sistema |
| `/api/health` | GET | Health check |
| `/api/computers/online-status` | GET | Estado online de equipos |
| `/api/computer/{id}/config` | GET | Configuración de computadora |

---

## Habilidades / Skills del Proyecto

- **Laravel Boost** — MCP server con herramientas integradas para depuración (Tinker), consultas a BD, búsqueda en documentación, y logs del navegador.
- **Laravel Horizon** — Monitoreo visual de colas y trabajos.
- **Laravel Pint** — Formateo automático de código PSR-12.
- **Laravel Sail** — Entorno de desarrollo Dockerizado.
- **Laravel Echo** — Cliente JS para broadcasting en tiempo real.
- **Socket.IO** — Servidor WebSocket para comunicación bidireccional.
- **Soketi** — Servidor Socket.IO alternativo compatible con Laravel Echo.
- **MCP (Model Context Protocol)** — Integración con asistentes de IA para desarrollo asistido.
- **Pest 4** — Testing moderno con browser testing, smoke testing, y type coverage.
