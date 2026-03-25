# ANALISIS COMPLETO DEL PROYECTO COMEXCARE2

## Tabla de Contenidos
1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Stack Tecnológico](#stack-tecnológico)
3. [Estructura del Proyecto](#estructura-del-proyecto)
4. [Modulos y Funcionalidades](#modulos-y-funcionalidades)
5. [Sidebar y Modulos Activos](#sidebar-y-modulos-activos)
6. [Base de Datos](#base-de-datos)
7. [API Endpoints](#api-endpoints)
8. [Servicios Principales](#servicios-principales)
9. [Jobs y Tareas en Segundo Plano](#jobs-y-tareas-en-segundo-plano)
10. [Comandos Artisan](#comandos-artisan)
11. [Sistema de Permisos y Roles](#sistema-de-permisos-y-roles)
12. [Limitaciones Conocidas](#limitaciones-conocidas)
13. [Skills Requeridas para Desarrollo](#skills-requeridas-para-desarrollo)
14. [Areas de Mejora Identificadas](#areas-de-mejora-identificadas)
15. [Tablas de Cache](#tablas-de-cache)

---

## RESUMEN EJECUTIVO

**ComexCare2** es una aplicación Laravel 12 de gestión empresarial que combina:

1. **Sistema de distribución de archivos** - Gestiona la distribución y recepción de archivos entre equipos/remotas (agentes .NET)
2. **Modulo de reportes empresariales** - Vendedores, metas, cartera, club de fidelización
3. **Sistema de usuarios con permisos** - Roles y asignaciones por plaza/tienda
4. **Dashboard con metricas** - Ventas, devoluciones, objetivos

---

## STACK TECNOLOGICO

### Backend
- **Framework:** Laravel 12
- **PHP:** 8.2+
- **Base de datos:** PostgreSQL (principal), MySQL, MariaDB, SQLite, SQL Server (soporte)

### Frontend
- **Plantilla Admin:** AdminLTE 3
- **CSS:** TailwindCSS 4, Bootstrap 5.2, SCSS
- **JS:** Vite 7, Vue (opcional), jQuery
- **Datatables:** Para tablas interactivas

### Librerias/Packages
```json
{
  "laravel/framework": "^12.0",
  "jeroennoten/laravel-adminlte": "^3.15",
  "spatie/laravel-permission": "^7.1",
  "maatwebsite/excel": "^3.1",
  "barryvdh/laravel-dompdf": "*",
  "laravel/ui": "^4.6"
}
```

---

## ESTRUCTURA DEL PROYECTO

```
comexcare2/
├── app/
│   ├── Console/Commands/        # Comandos Artisan personalizados
│   ├── Exports/                 # Clases de exportación Excel
│   ├── Helpers/                  # Helpers (RoleHelper)
│   ├── Http/Controllers/
│   │   ├── Api/                 # Controladores API
│   │   ├── Auth/                # Controladores Auth
│   │   ├── Reportes/            # Controladores de Reportes
│   │   └── [root]               # Controladores principales
│   ├── Imports/                  # Clases de importación Excel
│   ├── Jobs/                     # Jobs para colas
│   ├── Models/                   # Modelos Eloquent
│   ├── Providers/               # Service Providers
│   └── Services/                # Servicios de negocio
├── config/                      # Configuraciones Laravel
├── database/
│   ├── factories/               # Factories para testing
│   ├── migrations/              # Migraciones de BD
│   └── seeders/                 # Seeders iniciales
├── public/                      # Assets públicos
├── resources/
│   ├── js/                      # JavaScript
│   ├── sass/                    # SCSS
│   └── views/                   # Vistas Blade
├── routes/                      # Archivos de rutas
├── sql/                         # Scripts SQL
├── storage/                     # Almacenamiento
└── tests/                       # Pruebas
```

---

## MODULOS Y FUNCIONALIDADES

### 1. MODULO DE AUTENTICACION

**Archivos:**
- `app/Http/Controllers/Auth/LoginController.php`
- `app/Http/Controllers/Auth/RegisterController.php`
- `app/Http/Controllers/Auth/ResetPasswordController.php`

**Funciones:**
- `showLoginForm()` - Muestra formulario de login
- `login()` - Autentica usuario
- `logout()` - Cierra sesión
- `register()` - Registra nuevo usuario
- `resetPassword()` - Resetea contraseña

**Permisos:** No requiere permisos específicos (accesible públicamente)

---

### 2. MODULO DASHBOARD (HOME)

**Archivo:** `app/Http/Controllers/HomeController.php`

**Funciones:**

| Funcion | Descripcion |
|---------|-------------|
| `index()` | Muestra dashboard con metricas |
| `calcularMetricas()` | Calcula ventas, devoluciones, neto, meta, objetivo, alcance |
| `getVendedoresData()` | Obtiene datos de vendedores por periodo |
| `getVentasPlazaData()` | Agrupa ventas por plaza |
| `getVentasTiendaData()` | Agrupa ventas por tienda (top 20) |
| `getCarteraAbonosData()` | Obtiene datos de cartera y abonos |

**Metricas Calculadas:**
- Ventas brutas (contado + crédito)
- Devoluciones
- Venta neta
- Tickets totales
- Ticket promedio
- % Devoluciones
- Meta mensual
- Objetivo acumulado
- Alcance (%)

**Permisos:** Requiere `auth`

---

### 3. MODULO USUARIOS

**Archivo:** `app/Http/Controllers/UserController.php`

**Funciones:**

| Funcion | Descripcion |
|---------|-------------|
| `index()` | Lista todos los usuarios |
| `data()` | Retorna datos para DataTable (con filtros por rol y estado) |
| `store()` | Crea nuevo usuario |
| `update(User $user)` | Actualiza usuario existente |
| `destroy(User $user)` | Elimina usuario |
| `show(User $user)` | Muestra detalle de usuario |

**Validaciones:**
- Email único
- Contraseña mínima 8 caracteres
- Rol requerido
- Plaza/Tienda opcionales

**Permisos:** Requiere `admin.usuarios.ver`

---

### 4. MODULO ROLES

**Archivo:** `app/Http/Controllers/RoleController.php`

**Funciones:**
- `index()` - Lista roles
- `data()` - Datos para DataTable
- `store()` - Crea rol
- `show()` - Muestra rol con permisos
- `update()` - Actualiza rol
- `destroy()` - Elimina rol
- `allPermissions()` - Lista todos los permisos disponibles

**Permisos:** Requiere `admin.roles.ver`

---

### 5. MODULO PERMISOS

**Archivo:** `app/Http/Controllers/PermissionController.php`

**Funciones:**
- `index()` - Lista permisos
- `data()` - Datos para DataTable
- `store()` - Crea permiso
- `update()` - Actualiza permiso
- `destroy()` - Elimina permiso
- `sync()` - Sincroniza permisos

**Permisos:** Requiere `admin.permissions.ver`

---

### 6. MODULO TIENDAS

**Archivo:** `app/Http/Controllers/TiendasController.php`

**Funciones:**
- `index()` - Lista tiendas
- `data()` - Datos para DataTable
- `show()` - Muestra tienda
- `store()` - Crea tienda
- `update()` - Actualiza tienda
- `destroy()` - Elimina tienda

**Permisos:** Requiere `tiendas.ver`, `tiendas.crear`, `tiendas.editar`, `tiendas.eliminar`

---

### 7. MODULO ASIGNACION USUARIO-PLAZA-TIENDA

**Archivo:** `app/Http/Controllers/UserPlazaTiendaController.php`

**Funciones:**
- `index()` - Lista asignaciones
- `edit()` - Formulario de edición
- `update()` - Actualiza asignaciones
- `getTiendas()` - Obtiene tiendas disponibles

**Permisos:** Requiere `user-plaza-tienda.ver`

---

### 8. MODULO METAS MENSUALES

**Archivo:** `app/Http/Controllers/MetasMensualController.php`

**Funciones:**

| Funcion | Descripcion |
|---------|-------------|
| `index()` | Muestra vista principal con lazy loading |
| `import()` | Importa metas desde Excel |
| `store()` | Crea meta mensual |
| `update()` | Actualiza meta mensual |
| `destroy()` | Elimina meta mensual |
| `generarMetas()` | Genera metas diarias desde mensuales |
| `generateDias()` | Genera días del periodo (con soporte feriados) |
| `getDiasPeriodo()` | Obtiene días para modal de feriados |
| `performanceTest()` | Prueba velocidad de consultas |

**Logica de Generacion de Dias:**
- Lunes a Viernes = valor 1.0
- Sábado = valor 0.5
- Domingo = valor 0.0
- Feriados = valor configurable

**Permisos:** Requiere `metas.ver`, `metas.importar`, `metas.crear`, `metas.editar`, `metas.eliminar`

---

### 9. MODULO DISTRIBUCIONES (Distribution Agent)

**Archivo:** `app/Http/Controllers/DistributionsController.php`

**Funciones:**

| Funcion | Descripcion |
|---------|-------------|
| `index()` | Lista distribuciones |
| `create()` | Formulario de creación |
| `store()` | Crea distribución (inmediata/programada/recurrente) |
| `show()` | Muestra detalle |
| `update()` | Actualiza distribución |
| `destroy()` | Elimina distribución |
| `stop()` | Detiene distribución |
| `start()` | Inicia/reanuda distribución |
| `retryTarget()` | Reintenta envío a un target |
| `progress()` | Retorna progreso actual |

**Tipos de Distribucion:**
- `immediate` - Ejecución inmediata
- `scheduled` - Programada para fecha/hora específica
- `recurring` - Recurrente (diaria/semanal/mensual/horaria/minutos)

**Permisos:** Requiere `distribution.ver`

---

### 10. MODULO RECEPCION (Reception Agent)

**Archivo:** `app/Http/Controllers/ReceptionController.php`

**Funciones:**

| Funcion | Descripcion |
|---------|-------------|
| `index()` | Lista recepciones |
| `create()` | Formulario de creación |
| `store()` | Crea recepción |
| `show()` | Muestra detalle |
| `showComputer()` | Muestra archivos recibidos por computadora |
| `destroy()` | Elimina recepción |
| `stop()` | Detiene recepción |
| `start()` | Inicia/reanuda recepción |
| `retryTarget()` | Reintenta recepción |

**Opciones de Recepcion:**
- Por tipo de archivo
- Por archivos específicos
- Por todos los archivos
- Frecuencia: inmediata/programada/recurrente

**Permisos:** Requiere `distribution.ver`

---

### 11. MODULO COMPUTADORAS (Agents)

**Archivo:** `app/Http/Controllers/ComputersController.php`

**Funciones:**

| Funcion | Descripcion |
|---------|-------------|
| `index()` | Lista computadoras con DataTable |
| `show()` | Muestra detalle con comandos y logs |
| `edit()` | Formulario de edición |
| `update()` | Actualiza computadora |
| `destroy()` | Elimina computadora |
| `logs()` | Obtiene logs (soporta polling) |
| `status()` | Retorna status online/offline |

**Metodos del Modelo Computer:**
- `getAllDownloadPaths()` - Retorna todos los paths de descarga (hasta 10 + config)
- `getDownloadPathsCount()` - Cuenta paths configurados

**Permisos:** Requiere `distribution.ver`

---

### 12. MODULO GRUPOS

**Archivo:** `app/Http/Controllers/GroupsController.php`

**Funciones:**
- `index()` - Lista grupos con conteo de computadoras
- `create()` - Formulario de creación
- `store()` - Crea grupo
- `edit()` - Formulario de edición
- `update()` - Actualiza grupo
- `destroy()` - Elimina grupo

**Permisos:** Requiere `distribution.ver`

---

### 13. MODULO VERSIONES DE AGENTES

**Archivo:** `app/Http/Controllers/AgentVersionsController.php`

**Funciones:**
- `index()` - Lista versiones
- `create()` - Formulario de creación
- `store()` - Crea nueva versión
- `destroy()` - Desactiva versión

**Canales:** stable, beta, alpha

**Permisos:** Requiere `distribution.ver`

---

### 14. MODULO REPORTES - VENDEDORES

**Archivo:** `app/Http/Controllers/ReporteVendedoresController.php`

**Funciones:**

| Funcion | Descripcion |
|---------|-------------|
| `index()` | Vista principal del reporte |
| `data()` | Datos para DataTable con filtros |
| `export()` | Exporta a Excel |
| `exportCsv()` | Exporta a CSV |
| `exportPdf()` | Exporta a PDF |
| `sync()` | Sincroniza datos a cache |

**Origen de Datos:** Tabla `vendedores_cache` (sincronizado desde `canota`)

**Filtros Disponibles:**
- Periodo (fecha_inicio, fecha_fin)
- Plaza
- Tienda
- Vendedor

**Permisos:** Requiere `reportes.vendedores.ver`, `reportes.vendedores.editar`

---

### 15. MODULO REPORTES - VENDEDORES MATRICIAL

**Archivo:** `app/Http/Controllers/ReporteVendedoresMatricialController.php`

**Funciones:**
- `index()` - Vista principal
- `exportExcel()` - Exporta a Excel
- `exportPdf()` - Exporta a PDF
- `exportCsv()` - Exporta a CSV

**Caracteristicas:** Vista matricial por vendedor x día

**Permisos:** Requiere `reportes.vendedores.matricial.ver`, `reportes.vendedores.matricial.editar`

---

### 16. MODULO REPORTES - METAS VENTAS

**Archivo:** `app/Http/Controllers/ReporteMetasVentasController.php`

**Funciones:**
- `index()` - Vista principal
- `export()` - Exporta a Excel
- `exportPdf()` - Exporta a PDF
- `exportCsv()` - Exporta a CSV
- `consultarDatosPersonalizados()` - API para consulta custom

**Permisos:** Requiere `reportes.metas-ventas.ver`, `reportes.metas-ventas.editar`

---

### 17. MODULO REPORTES - METAS MATRICIAL

**Archivo:** `app/Http/Controllers/ReporteMetasMatricialController.php`

**Funciones:**
- `index()` - Vista principal
- `exportExcel()` - Exporta a Excel
- `exportPdf()` - Exporta a PDF

**Caracteristicas:** Matriz Plaza > Zona > Tienda > Fecha

**Permisos:** Requiere `reportes.metas-matricial.ver`, `reportes.metas-matricial.editar`

---

### 18. MODULO REPORTES - CARTERA ABONOS

**Archivos:**
- `app/Http/Controllers/Reportes/CarteraAbonosController.php`
- `app/Http/Controllers/Reportes/CarteraAbonosOptimizedController.php`
- `app/Http/Controllers/Reportes/CarteraAbonosRealtimeController.php`
- `app/Http/Controllers/Reportes/CarteraAbonosUltraFastController.php`

**Funciones (Controlador Principal):**

| Funcion | Descripcion |
|---------|-------------|
| `index()` | Vista principal |
| `data()` | Datos para DataTable |
| `pdf()` | Genera PDF |
| `exportExcel()` | Exporta a Excel |
| `exportCsv()` | Exporta a CSV |
| `sync()` | Sincroniza a cache |

**Origen de Datos:** Tabla `cartera_abonos_cache` (sincronizado desde `cobranza`, `cliente_depurado`)

**Filtros Disponibles:**
- Periodo
- Plaza
- Tienda
- Campos: plaza, tienda, fecha, fecha_vta, concepto, tipo, factura, clave, rfc, nombre, vend_clave, monto_fa, monto_dv, monto_cd

**Permisos:** Requiere `reportes.cartera-abonos.ver`, `reportes.cartera-abonos.editar`, `reportes.cartera-abonos.sincronizar`

---

### 19. MODULO REPORTES - COMPRAS DIRECTO

**Archivo:** `app/Http/Controllers/ReporteComprasDirectoController.php`

**Funciones:**
- `index()` - Vista principal
- `data()` - Datos para DataTable
- `export()` - Exporta a Excel
- `exportExcel()` - Exporta a Excel
- `exportCsv()` - Exporta a CSV
- `exportPdf()` - Exporta a PDF
- `sync()` - Sincroniza cache

**Permisos:** Requiere `reportes.compras-directo.ver`, `reportes.compras-directo.editar`, `reportes.compras-directo.sincronizar`

---

### 20. MODULO REPORTES - NOTAS COMPLETAS

**Archivo:** `app/Http/Controllers/Reportes/NotasCompletasController.php`

**Funciones:**
- `index()` - Vista principal
- `data()` - Datos para DataTable
- `exportExcel()` - Exporta a Excel
- `exportCsv()` - Exporta a CSV
- `sync()` - Sincroniza cache

**Origen de Datos:** Tabla `notas_completas_cache` (desde `canota`, `cunota`, `canotaex`)

**Campos:** Plaza, Tienda, Num Referencia, Vendedor, Factura, Nota Club, Club TR, Club ID, Fecha, Producto, Descripción, Piezas, Descuento, Precio, Costo, Total

**Permisos:** Requiere `reportes.notas-completas.ver`, `reportes.notas-completas.editar`, `reportes.notas-completas.sincronizar`

---

### 21. MODULO REPORTES - CLUB COMEX

**Archivo:** `app/Http/Controllers/Reportes/ClubComexController.php`

**Funciones:**

| Funcion | Descripcion |
|---------|-------------|
| `index()` | Vista principal |
| `sync()` | Sincroniza redenciones y acumulaciones |
| `syncStaggered()` | Sincronización por año |
| `syncRedenciones()` | Inserta en `redenciones_clubcomex` |
| `syncAcumulaciones()` | Inserta en `acumulaciones_clubcomex` |
| `syncAcumulacionesIa()` | Inserta en `acumulaciones_clubcomex_ia` |
| `search()` | Busca por ccampo3 |
| `exportCsv()` | Exporta CSV completo |

**Origen de Datos:** `flujores`, `canota`, `cunota`, `canotaex`

**Permisos:** Requiere `reportes.club-comex.ver`, `reportes.club-comex.sincronizar`

---

### 22. MODULO REPORTES - REDENCIONES CLUB

**Archivo:** `app/Http/Controllers/Reportes/ReporteRedencionesClubController.php`

**Funciones:**
- `index()` - Vista principal
- `data()` - Datos para DataTable
- `exportExcel()` - Exporta a Excel
- `exportCsv()` - Exporta a CSV
- `sync()` - Sincroniza

**Permisos:** Requiere `reportes.redenciones_club.ver`, `reportes.redenciones_club.editar`, `reportes.redenciones_club.sincronizar`

---

### 23. API PARA AGENTES (Agentes .NET)

**Archivo:** `app/Http/Controllers/Api/AgentController.php`

**Endpoints:**

| Endpoint | Metodo | Descripcion |
|----------|--------|-------------|
| `/api/register` | GET | Registra nueva computadora |
| `/api/heartbeat` | POST | Heartbeat del agente (logs, status) |
| `/api/commands/{id}` | GET | Obtiene comandos pendientes |
| `/api/report` | POST | Reporta resultado de comando |
| `/api/download/{fileId}` | GET | Descarga archivo de distribución |
| `/api/update/{version}` | GET | Verifica actualización de agente |
| `/api/check-update/{computer_id}` | GET | Verifica actualización por computer_id |
| `/api/inventory` | POST | Envía inventario de hardware |
| `/api/logs` | POST | Envía logs del agente |
| `/api/upload-reception` | POST | Sube archivo de recepción |

**Funciones del Controlador:**

| Funcion | Descripcion |
|---------|-------------|
| `register()` | Registra computadora (MAC, nombre, versión) |
| `heartbeat()` | Procesa heartbeat, retorna comandos pendientes y configuración |
| `getCommands()` | Retorna comandos pendientes (distribute, receive, download, update) |
| `report()` | Procesa reportes de distribución/recepción |
| `download()` | Descarga archivo |
| `checkUpdate()` | Verifica actualización |
| `checkUpdateByComputerId()` | Verifica actualización por ID |
| `inventory()` | Recibe inventario |
| `logs()` | Recibe logs |
| `uploadReception()` | Recibe archivos |

**Seguridad:** Endpoints sin CSRF, abiertos para agentes

---

## SIDEBAR Y MODULOS ACTIVOS

**Configuracion:** `config/adminlte.php`

```
Sidebar Principal:
├── Dashboard (/) - icon: fas fa-tachometer-alt
├── Usuarios (/admin/usuarios) - icon: fas fa-users
├── Roles (/admin/roles) - icon: fas fa-user-tag
├── Permisos (/admin/permissions) - icon: fas fa-shield-alt
├── Tiendas (/admin/tiendas) - icon: fas fa-store
├── Asignación Usuarios-Plaza-Tienda (/admin/user-plaza-tienda) - icon: fas fa-user-friends
├── Metas (/metas-mensual) - icon: fas fa-chart-area
├── Distributions (/admin/distributions) - icon: fas fa-upload
├── Recepción (/admin/reception) - icon: fas fa-download
├── Computers (/admin/computers) - icon: fas fa-desktop
├── Groups (/admin/groups) - icon: fas fa-users
├── Agent Versions (/admin/agent-versions) - icon: fas fa-code-branch
└── Reportes (dropdown) - icon: fas fa-chart-bar
    ├── Reporte Vendedores (/reportes/vendedores)
    ├── Reporte Vendedores Matriz (/reportes/vendedores-matricial)
    ├── Reporte Metas Diario (/reportes/metas-ventas)
    ├── Reporte Metas Mensual (/reportes/metas-matricial)
    ├── Reporte Compras (/reportes/compras-directo)
    ├── Reporte Cargos y Abonos (/reportes/cartera-abonos)
    ├── Reporte Notas completas (/reportes/notas-completas)
    ├── Club Comex (/reportes/club-comex)
    └── Reporte Redenciones Club (/reportes/redenciones-club)
```

### Verificacion de Modulos vs Sidebar

| Modulo Sidebar | Controlador | Estado | Notas |
|---------------|------------|--------|-------|
| Dashboard | HomeController | ✅ Activo | - |
| Usuarios | UserController | ✅ Activo | CRUD completo |
| Roles | RoleController | ✅ Activo | CRUD + permisos |
| Permisos | PermissionController | ✅ Activo | CRUD + sync |
| Tiendas | TiendasController | ✅ Activo | CRUD completo |
| Asignacion Plaza/Tienda | UserPlazaTiendaController | ✅ Activo | Edit + list |
| Metas | MetasMensualController | ✅ Activo | Import + CRUD + generar dias |
| Distributions | DistributionsController | ✅ Activo | CRUD + start/stop/retry |
| Recepcion | ReceptionController | ✅ Activo | CRUD + start/stop/retry |
| Computers | ComputersController | ✅ Activo | CRUD + logs + status |
| Groups | GroupsController | ✅ Activo | CRUD completo |
| Agent Versions | AgentVersionsController | ✅ Activo | CRUD |
| Reporte Vendedores | ReporteVendedoresController | ✅ Activo | Export Excel/CSV/PDF + sync |
| Reporte Vendedores Matriz | ReporteVendedoresMatricialController | ✅ Activo | Export Excel/PDF/CSV |
| Reporte Metas Diario | ReporteMetasVentasController | ✅ Activo | Export + consulta custom |
| Reporte Metas Mensual | ReporteMetasMatricialController | ✅ Activo | Export Excel/PDF |
| Reporte Compras | ReporteComprasDirectoController | ✅ Activo | Export + sync |
| Reporte Cargos y Abonos | CarteraAbonosController | ✅ Activo | Export + sync |
| Reporte Notas completas | NotasCompletasController | ✅ Activo | Export + sync |
| Club Comex | ClubComexController | ✅ Activo | Sync + search + export |
| Reporte Redenciones Club | ReporteRedencionesClubController | ✅ Activo | Export + sync |

**Conclusion:** Todos los módulos del sidebar tienen su controlador y están funcionales.

---

## BASE DE DATOS

### Tablas Principales (Aplicacion)

| Tabla | Descripcion |
|-------|-------------|
| `users` | Usuarios del sistema |
| `roles` | Roles (Spatie) |
| `permissions` | Permisos (Spatie) |
| `role_has_permissions` | Relación roles-permisos |
| `model_has_roles` | Relación modelos-roles |
| `model_has_permissions` | Relación modelos-permisos |
| `groups` | Grupos de computadoras |
| `computers` | Computadoras/agentes |
| `computer_logs` | Logs de computadoras |
| `distributions` | Distribuciones de archivos |
| `distribution_files` | Archivos de distribución |
| `distribution_targets` | Targets de distribución |
| `receptions` | Recepciones de archivos |
| `reception_files` | Archivos de recepción |
| `reception_targets` | Targets de recepción |
| `agent_versions` | Versiones de agentes |
| `commands` | Comandos para agentes |
| `metas_mensual` | Metas mensuales |
| `metas_dias` | Metas diarias (con valores) |
| `metas` | Metas generadas |
| `user_plaza_tiendas` | Asignación usuario-plaza-tienda |

### Tablas de Cache (Reportes)

| Tabla | Descripcion | Sincronizado Desde |
|-------|-------------|-------------------|
| `vendedores_cache` | Datos de vendedores | `canota` |
| `metas_cache` | Metas cacheadas | `metas_mensual` |
| `cartera_abonos_cache` | Cartera y abonos | `cobranza`, `cliente_depurado` |
| `notas_completas_cache` | Notas completas | `canota`, `cunota`, `canotaex` |
| `redenciones_clubcomex` | Redenciones Club | `flujores`, `canota` |
| `acumulaciones_clubcomex` | Acumulaciones Club | `canota`, `cunota` |
| `acumulaciones_clubcomex_ia` | Acumulaciones IA | `canota`, `canotaex` |

### Tablas Externas (Solo lectura, no gestionadas)

| Tabla | Descripcion |
|-------|-------------|
| `xcorte` | Cortes diarios (ventas) |
| `canota` | Notas de venta |
| `cunota` | Detalles de notas |
| `canotaex` | Notas extendidas |
| `venta` | Ventas (devoluciones) |
| `cobranza` | Cobranza |
| `cliente_depurado` | Clientes |
| `flujores` | Flujo de efectivo |
| `bi_sys_tiendas` | Catálogo de tiendas |

---

## API ENDPOINTS

### API Publica (Agentes)

```
GET  /api/register                 - Registrar computadora
POST /api/heartbeat                - Heartbeat
GET  /api/commands/{id}           - Obtener comandos
POST /api/report                   - Reportar resultado
GET  /api/download/{fileId}       - Descargar archivo
GET  /api/update/{version}         - Verificar actualización
GET  /api/check-update/{computer_id} - Verificar por ID
POST /api/inventory                - Enviar inventario
POST /api/logs                     - Enviar logs
POST /api/upload-reception         - Subir archivo
```

### API Autenticada

```
GET  /api/dias-periodo             - Obtener días del periodo (metas)
POST /metas-dias/generate          - Generar días
```

---

## SERVICIOS PRINCIPALES

### 1. DistributionService
**Archivo:** `app/Services/DistributionService.php`

**Metodos:**

| Metodo | Descripcion |
|--------|-------------|
| `createDistribution()` | Crea distribución con archivos y targets |
| `startDistribution()` | Inicia proceso de distribución (Job) |
| `sendDownloadCommand()` | Envía comando de descarga a target |
| `handleRetry()` | Maneja reintentos con backoff exponencial |
| `validateFileSpace()` | Valida espacio en disco |

---

### 2. ReportService
**Archivo:** `app/Services/ReportService.php`

**Metodos:**

| Metodo | Descripcion |
|--------|-------------|
| `getVendedoresReport()` | Reporte vendedores con cache |
| `getVendedoresMatricialReport()` | Reporte matricial vendedores |
| `calcularEstadisticasVendedores()` | Calcula estadísticas |
| `getMetasVentasReport()` | Reporte metas ventas |
| `getMetasMatricialReport()` | Reporte metas matricial |
| `getVentaAcumulada()` | Venta acumulada del mes |
| `limpiarCacheReportes()` | Limpia cache |
| `optimizarConfiguracion()` | Ajusta límites PHP |

---

### 3. AgentUpdateService
**Archivo:** `app/Services/AgentUpdateService.php`

**Metodos:**
- `createVersion()` - Crea nueva versión
- `deactivateVersion()` - Desactiva versión

---

### 4. CarteraAbonosCacheService
**Archivo:** `app/Services/CarteraAbonosCacheService.php`

---

### 5. CarteraAbonosUltraFastService
**Archivo:** `app/Services/CarteraAbonosUltraFastService.php`

---

## JOBS Y TAREAS EN SEGUNDO PLANO

### 1. ProcessDistributionJob
**Archivo:** `app/Jobs/ProcessDistributionJob.php`
- Procesa distribución de archivos a targets

### 2. ProcessScheduledDistributions
**Archivo:** `app/Jobs/ProcessScheduledDistributions.php`

**Funciones:**
- `processScheduledDistributions()` - Procesa distribuciones programadas
- `processRecurringDistributions()` - Procesa distribuciones recurrentes
- `shouldRunDaily()` - Evalúa si debe ejecutarse diariamente
- `shouldRunWeekly()` - Evalúa ejecución semanal
- `shouldRunMonthly()` - Evalúa ejecución mensual
- `shouldRunHourly()` - Evalúa ejecución por hora
- `shouldRunMinutes()` - Evalúa ejecución por minutos

### 3. ProcessScheduledReceptions
**Archivo:** `app/Jobs/ProcessScheduledReceptions.php`
- Procesa recepciones programadas/recurrentes

### 4. RetryDistribution
**Archivo:** `app/Jobs/RetryDistribution.php`
- Reintenta distribución fallida con backoff

### 5. CarteraAbonosSyncJob
**Archivo:** `app/Jobs/CarteraAbonosSyncJob.php`
- Sincroniza cartera abonos

### 6. CarteraAbonosIncrementalUpdateJob
**Archivo:** `app/Jobs/CarteraAbonosIncrementalUpdateJob.php`
- Actualización incremental de cartera

---

## COMANDOS ARTISAN

### Comandos de Sincronizacion

| Comando | Descripcion |
|---------|-------------|
| `cartera-abonos:sync-cache` | Sincroniza cartera abonos a cache |
| `sync-vendedores` | Sincroniza vendedores a cache |
| `sync-compras-directo` | Sincroniza compras directo |
| `sync-notas-completas` | Sincroniza notas completas |
| `sync-club-comex` | Sincroniza Club Comex |
| `sync-all-cache-tables` | Sincroniza todas las tablas cache |
| `sync-cache-full` | Sincronización completa |
| `sync-cache-incremental` | Sincronización incremental |

### Opciones de CarteraAbonosSyncCache

```bash
# Sincronizar mes anterior
php artisan cartera-abonos:sync-cache

# Sincronizar periodo especifico
php artisan cartera-abonos:sync-cache --period=2024-01-01,2024-01-31

# Sincronizar dia especifico
php artisan cartera-abonos:sync-cache --day=2024-01-15

# Ultimos 30 dias
php artisan cartera-abonos:sync-cache --last-days=30

# Sincronizacion completa
php artisan cartera-abonos:sync-cache --full

# Modo append (agregar sin truncar)
php artisan cartera-abonos:sync-cache --append
```

---

## SISTEMA DE PERMISOS Y ROLES

### Roles Estandar

| Rol | Descripcion |
|-----|-------------|
| `super_admin` | Administrador total |
| `admin` | Administrador |
| `gerente` | Gerente de tienda |
| `vendedor` | Vendedor |
| `viewer` | Solo lectura |

### Permisos del Sistema

**Administrativos:**
- `admin.ver`
- `admin.usuarios.ver`
- `admin.usuarios.crear`
- `admin.usuarios.editar`
- `admin.usuarios.eliminar`
- `admin.roles.ver`
- `admin.permissions.ver`

**Tiendas:**
- `tiendas.ver`
- `tiendas.crear`
- `tiendas.editar`
- `tiendas.eliminar`

**Asignaciones:**
- `user-plaza-tienda.ver`

**Metas:**
- `metas.ver`
- `metas.importar`
- `metas.crear`
- `metas.editar`
- `metas.eliminar`

**Distribucion:**
- `distribution.ver`

**Reportes:**
- `reportes.ver`
- `reportes.vendedores.ver`
- `reportes.vendedores.editar`
- `reportes.vendedores.matricial.ver`
- `reportes.vendedores.matricial.editar`
- `reportes.metas-ventas.ver`
- `reportes.metas-ventas.editar`
- `reportes.metas-matricial.ver`
- `reportes.metas-matricial.editar`
- `reportes.compras-directo.ver`
- `reportes.compras-directo.editar`
- `reportes.compras-directo.sincronizar`
- `reportes.cartera-abonos.ver`
- `reportes.cartera-abonos.editar`
- `reportes.cartera-abonos.sincronizar`
- `reportes.notas-completas.ver`
- `reportes.notas-completas.editar`
- `reportes.notas-completas.sincronizar`
- `reportes.club-comex.ver`
- `reportes.club-comex.sincronizar`
- `reportes.redenciones_club.ver`
- `reportes.redenciones_club.editar`
- `reportes.redenciones_club.sincronizar`

### Helper RoleHelper
**Archivo:** `app/Helpers/RoleHelper.php`

**Funciones:**

| Funcion | Descripcion |
|---------|-------------|
| `getUserFilter()` | Obtiene filtros basados en asignaciones plaza/tienda |
| `getTiendasAcceso()` | Lista tiendas permitidas |
| `hasAccessToPlaza()` | Verifica acceso a plaza |
| `hasAccessToTienda()` | Verifica acceso a tienda |
| `getUserPermissions()` | Lista permisos del usuario |
| `hasPermission()` | Verifica permiso especifico |
| `getFiltrosReporte()` | Genera filtros para reportes |
| `getListasParaFiltros()` | Listas para dropdowns de filtros |

---

## LIMITACIONES CONOCIDAS

### 1. Base de Datos
- **Indices:** Hay índices pero no están optimizados para todas las consultas
- **Cache:** Los reportes usan cache en memoria que puede crecer mucho
- **Consultas SQL:** Algunas consultas son complejas y pueden ser lentas

### 2. Agentes
- **Polling:** Los agentes dependen de heartbeat para recibir comandos
- **Sin WebSocket:** No hay comunicación en tiempo real
- **Logs:** Los logs se almacenan en BD, puede crecer mucho

### 3. Reportes
- **Sin paginacion real:** DataTables carga todos los datos
- **Exportaciones grandes:** Puede haber timeout con datasets grandes
- **Sincronizacion manual:** Los reportes dependen de sync manual

### 4. Frontend
- **AdminLTE:** UI limitada a la plantilla
- **Sin SPA:** Cada pagina es una request nueva
- **Responsive:** No optimizado para móviles

### 5. Concurrencia
- **Jobs:** No hay control de concurrencia en jobs
- **Distribuciones:** No hay cola de prioridad

---

## SKILLS REQUERIDAS PARA DESARROLLO

### Obligatorias

1. **PHP 8.2+**
   - Programación orientada a objetos
   - Traits y namespaces
   - Type hints y return types
   - Attributes

2. **Laravel 12**
   - Eloquent ORM
   - Blade templates
   - Routing y middleware
   - Artisan commands
   - Jobs y queues
   - Form requests y validation
   - API resources

3. **PostgreSQL**
   - SQL avanzado
   - Índices y optimizacion
   - JOINs complejos
   - Funciones window
   - CTEs (Common Table Expressions)

4. **HTML/CSS/JS**
   - HTML5 semántico
   - CSS3 y Flexbox/Grid
   - JavaScript vanilla
   - jQuery
   - DataTables

5. **AdminLTE 3**
   - Configuración de temas
   - Componentes de interfaz
   - Personalización de sidebar

### Recomendadas

6. **TailwindCSS 4**
   - Clases utilitarias
   - Configuración

7. **Maatwebsite Excel**
   - Imports/Exports
   - Configuración de columnas

8. **DomPDF**
   - Generación de PDFs
   - Plantillas

9. **Spatie Permission**
   - Roles y permisos
   - Gates y policies

10. **Docker**
    - Contenedores
    - docker-compose

### Deseables

11. **Vue.js 3**
    - Componentes
    - Composition API

12. **Vite**
    - Bundling
    - Plugins

13. **Linux**
    - Administración de servidores
    - Cron jobs

14. **Redis**
    - Cache
    - Colas

15. **Testing**
    - PHPUnit
    - Feature tests

---

## AREAS DE MEJORA IDENTIFICADAS

### 1. Rendimiento

- [ ] Agregar índices en tablas de cache para consultas frecuentes
- [ ] Implementar paginación real en DataTables
- [ ] Usar Redis para cache de reportes
- [ ] Lazy loading de datos en dashboard
- [ ] Optimizar queries del HomeController

### 2. Frontend

- [ ] Migrar a Vue.js 3 para componentes reactivos
- [ ] Implementar WebSocket para tiempo real
- [ ] Crear API REST completa
- [ ] PWA para agentes

### 3. Funcionalidad

- [ ] Notificaciones en tiempo real
- [ ] Historial de cambios en distribuciones
- [ ] Programación visual de distribuciones
- [ ] Dashboard personalizado por rol
- [ ] Exportación programada

### 4. Seguridad

- [ ] Implementar rate limiting en API de agentes
- [ ] Auditoria de accesos
- [ ] Encriptación de datos sensibles
- [ ] 2FA para administradores

### 5. Testing

- [ ] Tests unitarios para servicios
- [ ] Tests de integración
- [ ] Tests de API
- [ ] Code coverage

### 6. DevOps

- [ ] CI/CD pipeline
- [ ] Monitoreo (Laravel Telescope)
- [ ] Logs centralizados
- [ ] Backup automatizado

---

## TABLAS DE CACHE

### Proposito

Las tablas de cache almacenan datos pre-procesados de las tablas externas para:

1. **Mejorar rendimiento** - Consultas sobre datos ya procesados
2. **Filtrado rápido** - Índices optimizados
3. **Reportes offline** - No dependen del sistema externo
4. **Consistencia** - Datos en un punto en el tiempo

### Sincronizacion

| Tabla | Metodo | Frecuencia Sugerida |
|-------|--------|---------------------|
| `vendedores_cache` | Manual/API | Diario |
| `cartera_abonos_cache` | Comando Artisan | Diario |
| `notas_completas_cache` | Comando Artisan | Diario |
| `redenciones_clubcomex` | Sync API | Mensual |
| `acumulaciones_clubcomex` | Sync API | Mensual |

### Mantenimiento

```bash
# Limpiar y recargar cache
php artisan cartera-abonos:sync-cache --full

# Actualización incremental
php artisan cartera-abonos:sync-cache --last-days=7 --append
```

---

## CONEXIONES DE BASE DE DATOS

```php
// config/database.php

'connections' => [
    'sqlite' => [...],      // Desarrollo local
    'mysql' => [...],       // MySQL
    'mariadb' => [...],     // MariaDB
    'pgsql' => [            // PRODUCCION (default)
        'driver' => 'pgsql',
        'host' => '192.168.10.200',
        'port' => '5432',
        'database' => 'pgdm-Index',
        ...
    ],
    'sqlsrv' => [...],      // SQL Server
]
```

---

## VARIABLES DE ENTORNO IMPORTANTES

```env
APP_NAME=ConexCare2
APP_ENV=local|production
APP_DEBUG=true|false
APP_URL=http://localhost

DB_CONNECTION=pgsql
DB_HOST=192.168.10.200
DB_PORT=5432
DB_DATABASE=pgdm-Index
DB_USERNAME=
DB_PASSWORD=

QUEUE_CONNECTION=database|sync|redis
CACHE_DRIVER=file|database|redis

MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=
MAIL_USERNAME=
MAIL_PASSWORD=
```

---

## RESUMEN DE ESTADO DEL PROYECTO

| Aspecto | Estado | Notas |
|---------|--------|-------|
| Modulos Sidebar | ✅ 100% | Todos funcionales |
| CRUD Usuarios/Roles | ✅ Completo | Con permisos |
| Modulo Metas | ✅ Completo | Import Excel, generación días |
| Distribution Agent | ✅ Completo | Programado/recurrente |
| Reception Agent | ✅ Completo | Recepción archivos |
| Computers | ✅ Completo | Logs, status, heartbeat |
| Reportes | ✅ 100% | 9 reportes con export |
| API Agentes | ✅ Completo | 10 endpoints |
| Sistema Permisos | ✅ Completo | Spatie |
| Dashboard | ✅ Completo | Métricas empresariales |
| Cache Reportes | ✅ Implementado | 5 tablas de cache |
| Jobs/Colas | ✅ Implementado | Distribuciones programadas |
| Testing | ⚠️ Parcial | Tests basicos |

---

*Documento generado: 19 de Marzo 2026*
*Proyecto: ComexCare2*
*Versión Laravel: 12.x*
