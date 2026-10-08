@extends('adminlte::page')
@section('title', 'Reporte de Archivos de Stock')

@section('content_header')
<h1>Reporte de Archivos de Stock</h1>
@stop

@section('content')
<div class="container-fluid">
  <div class="card bg-light mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">
        <i class="fas fa-filter"></i> Filtros
      </h5>
      <button type="button" class="btn-card-minimize" title="Minimizar">
        <i class="fas fa-minus"></i>
      </button>
    </div>
    <div class="card-body">
      <div class="row g-2">
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Tipos de Grupo</label>
          <input type="text" class="form-control form-control-sm mb-1 filter-search"
                 placeholder="Buscar tipo..." aria-label="Buscar tipo de grupo"
                 data-target="#group_type_list" autocomplete="off">
          <div class="border rounded p-2" id="group_type_list" style="max-height: 100px; overflow-y: auto;">
            <div class="form-check">
              <input type="checkbox" id="select_all_types" class="form-check-input">
              <label for="select_all_types" class="form-check-label font-weight-bold"><strong>Todos</strong></label>
            </div>
            @php
            $groupTypes = $groups->pluck('type')->filter()->unique()->sort()->values();
            @endphp
            @foreach($groupTypes as $type)
            <div class="form-check group-type-item" data-label="{{ strtolower($type) }}">
              <input type="checkbox" name="type[]" value="{{ $type }}" id="type_{{ $type }}" class="form-check-input group-type-checkbox">
              <label for="type_{{ $type }}" class="form-check-label">{{ ucfirst($type) }}</label>
            </div>
            @endforeach
            <div class="form-check d-none no-filter-results" data-empty-for="#group_type_list">
              <span class="text-muted small">Sin coincidencias</span>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Plazas</label>
          <input type="text" class="form-control form-control-sm mb-1 filter-search"
                 placeholder="Buscar plaza..." aria-label="Buscar plaza"
                 data-target="#plaza_list" autocomplete="off">
          <div class="border rounded p-2" id="plaza_list" style="max-height: 100px; overflow-y: auto;">
            <div class="form-check">
              <input type="checkbox" id="select_all_plazas" class="form-check-input">
              <label for="select_all_plazas" class="form-check-label font-weight-bold"><strong>Todas</strong></label>
            </div>
            @foreach($plazas as $plaza)
            <div class="form-check plaza-item" data-label="{{ strtolower($plaza) }}">
              <input type="checkbox" name="plaza[]" value="{{ $plaza }}" id="plaza_{{ $plaza }}" class="form-check-input plaza-checkbox">
              <label for="plaza_{{ $plaza }}" class="form-check-label">{{ $plaza }}</label>
            </div>
            @endforeach
            <div class="form-check d-none no-filter-results" data-empty-for="#plaza_list">
              <span class="text-muted small">Sin coincidencias</span>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Archivos de Stock</label>
          <input type="text" class="form-control form-control-sm mb-1 filter-search"
                 placeholder="Buscar archivo..." aria-label="Buscar archivo de stock"
                 data-target="#archivo_list" autocomplete="off">
          <div class="border rounded p-2" id="archivo_list" style="max-height: 100px; overflow-y: auto;">
            <div class="form-check">
              <input type="checkbox" id="select_all_archivos" class="form-check-input">
              <label for="select_all_archivos" class="form-check-label font-weight-bold"><strong>Todos</strong></label>
            </div>
            @foreach($archivos as $archivo)
            <div class="form-check archivo-item" data-label="{{ strtolower($archivo) }}">
              <input type="checkbox" name="archivo[]" value="{{ $archivo }}" id="archivo_{{ $loop->index }}"
                     class="form-check-input archivo-checkbox">
              <label for="archivo_{{ $loop->index }}" class="form-check-label">{{ $archivo }}</label>
            </div>
            @endforeach
            <div class="form-check d-none no-filter-results" data-empty-for="#archivo_list">
              <span class="text-muted small">Sin coincidencias</span>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Buscar Agente</label>
          <input type="text" id="agent_search" class="form-control form-control-sm mb-1"
                 placeholder="Nombre, clave o IP" autocomplete="off">
          <div class="border rounded p-1" id="agent_list" style="max-height: 130px; overflow-y: auto;">
            <div class="text-muted small px-1 py-1" id="agent_list_empty">Escribe para buscar agentes</div>
          </div>
          <div class="d-flex align-items-center justify-content-between mt-1">
            <div class="form-check mb-0">
              <input type="checkbox" id="agent_select_all" class="form-check-input">
              <label for="agent_select_all" class="form-check-label small mb-0">Todos</label>
            </div>
            <small class="text-muted"><span id="agent_selected_count">0</span> sel.</small>
          </div>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Conexión</label>
          <select id="conexion_filter" class="form-control form-control-sm">
            <option value="">Todas</option>
            <option value="online">Online</option>
            <option value="offline">Offline</option>
          </select>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Estado</label>
          <select id="estado_filter" class="form-control form-control-sm">
            <option value="">Todos</option>
            <option value="verde">Verde</option>
            <option value="amarillo">Amarillo</option>
            <option value="rojo">Rojo</option>
            <option value="no_cuenta">No cuenta</option>
            <option value="no_aplica">No aplica</option>
          </select>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1" title="Días desde la última modificación más antigua del archivo">Días</label>
          <div class="d-flex gap-1">
            <input type="number" min="0" step="1" id="dias_min" class="form-control form-control-sm"
                   placeholder="Desde" aria-label="Días desde la última modificación (mínimo)">
            <input type="number" min="0" step="1" id="dias_max" class="form-control form-control-sm"
                   placeholder="Hasta" aria-label="Días desde la última modificación (máximo)">
          </div>
        </div>
      </div>
      <div class="row mt-2">
        <div class="col-12 d-flex flex-wrap gap-2 align-items-center justify-content-between">
          <div class="d-flex gap-2 flex-wrap">
            <span id="total_records" class="badge bg-info"></span>
          </div>
          <div class="d-flex gap-1 flex-wrap">
            <button id="btn_search" class="btn btn-success btn-sm">
              <i class="fas fa-search"></i> Buscar
            </button>
            <button id="btn_refresh" class="btn btn-primary btn-sm" title="Actualizar">
              <i class="fas fa-sync-alt"></i>
            </button>
            <button id="btn_reset_filters" class="btn btn-secondary btn-sm" title="Limpiar">
              <i class="fas fa-undo"></i>
            </button>
            <button id="btn_export" class="btn btn-info btn-sm" title="Descargar CSV con los filtros actuales">
              <i class="fas fa-file-csv"></i> CSV
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-3" id="chartsCard">
    <div class="card-header py-2 d-flex align-items-center gap-2">
      <i class="fas fa-chart-bar text-info"></i>
      <small class="fw-bold">Graficas</small>
      <button type="button" class="btn-card-minimize ms-auto" title="Minimizar"><i class="fas fa-minus"></i></button>
    </div>
    <div class="card-body py-2">
      <div class="row g-2 mb-3">
        <div class="col-md col-sm-6">
          <div class="card text-bg-light h-100">
            <div class="card-body py-2 px-3 text-center">
              <span class="d-block fs-4 fw-bold" id="statContempladas">0</span>
              <small class="text-muted d-block">Contempladas</small>
              <small class="text-muted d-block" id="statFuera">0 fuera del calculo</small>
            </div>
          </div>
        </div>
        <div class="col-md col-sm-6">
          <div class="card text-bg-success h-100">
            <div class="card-body py-2 px-3 text-center">
              <span class="d-block fs-4 fw-bold" id="statListas">0</span>
              <small>Listas</small>
            </div>
          </div>
        </div>
        <div class="col-md col-sm-6">
          <div class="card text-bg-danger h-100">
            <div class="card-body py-2 px-3 text-center">
              <span class="d-block fs-4 fw-bold" id="statPendientes">0</span>
              <small>Pendientes</small>
            </div>
          </div>
        </div>
        <div class="col-md col-sm-6">
          <div class="card text-bg-info h-100">
            <div class="card-body py-2 px-3 text-center">
              <span class="d-block fs-4 fw-bold" id="statPercent">0%</span>
              <small>Instalaciones listas</small>
            </div>
          </div>
        </div>
      </div>
      <div id="chartsSection" class="row g-3 mb-0 d-none">
        <div class="col-lg-4 col-md-6">
          <div class="card h-100">
            <div class="card-header py-2 d-flex align-items-center gap-2">
              <i class="fas fa-chart-pie text-info"></i>
              <small class="fw-bold">Instalaciones</small>
            </div>
            <div class="card-body py-3 text-center">
              <canvas id="pieFilesChart"></canvas>
            </div>
          </div>
        </div>
        <div class="col-lg-8 col-md-6">
          <div class="card h-100">
            <div class="card-header py-2 d-flex align-items-center gap-2">
              <i class="fas fa-map-marker-alt text-warning"></i>
              <small class="fw-bold">Instalaciones por Plaza</small>
            </div>
            <div class="card-body py-3">
              <canvas id="barPlazaChart"></canvas>
            </div>
          </div>
        </div>
        <div class="col-12" id="causasCard">
          <div class="card h-100">
            <div class="card-header py-2 d-flex align-items-center gap-2">
              <i class="fas fa-exclamation-triangle text-danger"></i>
              <small class="fw-bold">Motivos de pendencia</small>
            </div>
            <div class="card-body py-3">
              <canvas id="barCausasChart"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div class="d-flex align-items-center flex-wrap">
        <button class="btn btn-success btn-sm" id="btn_run_stock" style="margin-right:4px; margin-bottom:2px; margin-top:2px;"><i class="fas fa-play"></i> Stock</button>
        <button class="btn btn-secondary btn-sm" id="btn_bitacora" style="margin-bottom:2px; margin-top:2px;"><i class="fas fa-history"></i> Bitacora</button>
      </div>
      <div class="d-flex align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center" style="gap: .25rem;">
        <label for="pageSizeSelect" class="mb-0 small">Mostrar</label>
        <select id="pageSizeSelect" class="form-control form-control-sm" style="width: auto;">
          <option value="10" selected>10</option>
          <option value="25">25</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
      </div>
      <div id="paginationControls" class="d-flex align-items-center flex-wrap gap-2 d-none">
        <small id="paginationInfo" class="text-muted"></small>
        <nav><ul class="pagination pagination-sm mb-0" id="paginationNumbers"></ul></nav>
      </div>
      <button type="button" class="btn-card-minimize" title="Minimizar">
        <i class="fas fa-minus"></i>
      </button>
    </div>
    <div class="card-body p-0">
      <div id="tableLoading" class="text-center py-4">
        <i class="fas fa-spinner fa-spin fa-2x"></i>
        <p class="mt-2">Cargando datos...</p>
      </div>
      <div class="table-responsive table-scroll">
        <table class="table table-sm table-hover table-striped mb-0" id="filesTable">
          <thead class="table-dark">
            <tr>
              <th class="text-center" style="width:30px;"><input type="checkbox" id="select_all_computers" class="form-check-input"></th>
              <th style="cursor:pointer" data-sort="plaza">Plaza <i class="fas fa-sort"></i></th>
              <th style="cursor:pointer" data-sort="nombre_instalacion">Agente <i class="fas fa-sort"></i></th>
              <th class="text-center">Estado Equipo</th>
              <th style="cursor:pointer" data-sort="archivo">Archivo RBF <i class="fas fa-sort"></i></th>
              <th>Hash RBF</th>
              <th>Fecha RBF</th>
              <th class="text-center">Peso RBF</th>
              <th>Archivo Rebs.</th>
              <th>Hash Rebs.</th>
              <th>Fecha Rebs.</th>
              <th class="text-center">Peso Rebs.</th>
              <th class="text-center" style="cursor:pointer" data-sort="estado">Estado <i class="fas fa-sort"></i></th>
              <th class="text-center" style="cursor:pointer" data-sort="dias"
                  title="Días desde la última modificación más antigua (RBF o Rebsamen)">Días <i class="fas fa-sort"></i></th>
            </tr>
          </thead>
          <tbody id="filesTableBody">
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="confirmModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-play-circle"></i> Confirmar Ejecucion</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
        <p id="confirmModalMessage" class="mb-2"></p>
        <div id="confirmModalList" style="max-height: 45vh; overflow-y: auto;"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success btn-sm" id="confirmEjecutarBtn">
          <i class="fas fa-play"></i> Enviar
        </button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="bitacoraModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-secondary text-white">
        <h5 class="modal-title"><i class="fas fa-history"></i> Bitacora de Ejecuciones</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
        <div id="bitacoraLoading" class="text-center py-4">
          <i class="fas fa-spinner fa-spin fa-2x"></i>
          <p class="mt-2">Cargando bitacora...</p>
        </div>
        <div id="bitacoraContent" class="d-none"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('css')
<style>
.card-header { border-bottom: 2px solid #dee2e6; }
.btn-sm { padding: 0.25rem 0.5rem; font-size: 0.75rem; }
.badge { font-size: 0.7rem; }
.form-label { font-size: 0.75rem; }
.form-control-sm { font-size: 0.75rem; }
.card.text-bg-light .card-body,
.card.text-bg-success .card-body,
.card.text-bg-danger .card-body,
.card.text-bg-info .card-body {
  padding: 0.75rem 0.5rem;
}
.card.text-bg-light .fs-4,
.card.text-bg-success .fs-4,
.card.text-bg-danger .fs-4,
.card.text-bg-info .fs-4 {
  line-height: 1.2;
}
#chartsSection .card-body {
  height: 280px;
  position: relative;
}
#chartsSection canvas {
  max-height: 100% !important;
  max-width: 100% !important;
}
#filesTable { font-size: 0.75rem; }
#filesTable thead th { white-space: nowrap; font-size: 0.7rem; }
#filesTable tbody td { font-size: 0.75rem; vertical-align: middle; }
/* Bootstrap 4 (AdminLTE 3) define .form-check-input como position:absolute con
   margin-left:-1.25rem porque asume que el checkbox vive dentro de un .form-check,
   que compensa ese desplazamiento con padding-left. Sueltos en un <td> no hay quien
   lo compense: el checkbox se posicionaba fuera de la columna, el encabezado y el
   cuerpo quedaban desalineados, y el contenedor con overflow lo recortaba al hacer
   scroll vertical. Aqui se devuelve al flujo normal dentro de su celda. */
#filesTable .form-check-input {
  position: static;
  margin: 0;
  vertical-align: middle;
}
/* Con 14 columnas el texto largo de las celdas fijaba el ancho minimo de la tabla y
   esta se salia de la tarjeta. Se recorta con ellipsis y el valor completo queda en
   el title, que es donde ya vive el detalle de los hash. */
#filesTable th, #filesTable td { padding: 0.2rem 0.3rem; }
#filesTable .celda-texto {
  display: block;
  max-width: 130px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
#filesTable .celda-texto-ancha { max-width: 210px; }
.table-scroll { max-height: 55vh; overflow-y: auto; }
.table-scroll #filesTable thead th {
  position: sticky;
  top: 0;
  z-index: 2;
  background-color: #343a40;
  color: #fff;
}
#paginationNumbers .page-link { padding: 0.15rem 0.45rem; }
.btn-card-minimize {
  background: transparent;
  border: none;
  color: inherit;
  opacity: .65;
  padding: 0.15rem 0.45rem;
  font-size: 0.85rem;
  line-height: 1.4;
  cursor: pointer;
}
.btn-card-minimize:hover { opacity: 1; }
.btn-card-minimize:focus { outline: none; box-shadow: none; }
.hash-chip {
  display: inline-block;
  font-family: monospace;
  font-size: 0.68rem;
  font-weight: 600;
  color: #0d6efd;
  white-space: nowrap;
}
/* Sobre la tabla clara el gris claro del tema oscuro quedaba ilegible. */
.celda-vacia { font-size: 0.62rem; color: #6c757d; font-style: italic; }
</style>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
var DATA_URL = "{{ url('/reportes/archivos-stock/data') }}";
var EXPORT_URL = "{{ url('/reportes/archivos-stock/export') }}";
var AGENTES_URL = "{{ url('/reportes/archivos-stock/agentes') }}";
var EJECUTAR_URL = "{{ url('/reportes/archivos-stock/ejecutar') }}";
var BITACORA_URL = "{{ url('/reportes/archivos-stock/bitacora') }}";
var CSRF_TOKEN = '{{ csrf_token() }}';
var COOLDOWN_MINUTOS = 5;

// Agentes marcados con checkbox. Se conservan al cambiar plaza/tipo/busqueda
// para que el filtro no se pierda, pero si no hay ninguno no se envía.
var selectedAgents = {};

// Equipos marcados en la tabla para enviarles DASTOCK.BAT. Se lleva el id
// del equipo, no el de la fila: un equipo puede tener varios archivos y solo
// debe recibir un unico comando.
var selectedComputerIds = {};
// id -> instante (ms) en que se encolo DASTOCK.BAT, para bloquear el checkbox
// durante la ventana de espera.
var stockEnEjecucion = {};

var ESTADOS_BADGE = {
  verde: '<span class="badge bg-success">Verde</span>',
  amarillo: '<span class="badge bg-warning text-dark">Amarillo</span>',
  rojo: '<span class="badge bg-danger">Rojo</span>',
  no_aplica: '<span class="badge bg-secondary">No aplica</span>',
  no_cuenta: '<span class="badge bg-light text-dark border">No cuenta</span>'
};

function selectedAgentIds() {
  return Object.keys(selectedAgents).filter(function(id) { return selectedAgents[id]; }).map(Number);
}

function refreshAgentCount() {
  $('#agent_selected_count').text(selectedAgentIds().length);
  var total = Object.keys(selectedAgents).length;
  var checked = selectedAgentIds().length;
  $('#agent_select_all').prop('checked', total > 0 && checked === total).prop('indeterminate', checked > 0 && checked < total);
}

function esc(s) {
  return String(s == null ? '' : s).replace(/[&<>"']/g, function(c) {
    return { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c];
  });
}

function renderAgentList(data, truncated) {
  var $list = $('#agent_list');
  if (!data || data.length === 0) {
    $list.html('<div class="text-muted small px-1 py-1">Sin agentes para este filtro</div>');
    return;
  }
  var html = '';
  data.forEach(function(a) {
    var checked = selectedAgents[a.id] ? ' checked' : '';
    var label = a.nombre || a.short_key || ('#' + a.id);
    html += '<div class="form-check mb-0 py-1 px-1">' +
      '<input type="checkbox" class="form-check-input agent-checkbox" id="agente_' + a.id + '" value="' + a.id + '"' + checked + '>' +
      '<label class="form-check-label small text-truncate d-block" for="agente_' + a.id + '" title="' + esc(label + ' · ' + a.short_key + (a.ip ? ' · ' + a.ip : '')) + '" style="max-width:100%">' +
      esc(label) +
      ' <span class="text-muted">(' + esc(a.short_key) + ')</span>' +
      '</label></div>';
  });
  if (truncated) {
    html += '<div class="text-muted small px-1 py-1">Use la búsqueda para acotar la lista.</div>';
  }
  $list.html(html);
  refreshAgentCount();
}

function loadAgents() {
  var params = {};
  var plazas = $('.plaza-checkbox:checked').map(function() { return $(this).val(); }).get();
  if (plazas.length) params.plaza = plazas;
  var tipos = $('.group-type-checkbox:checked').map(function() { return $(this).val(); }).get();
  if (tipos.length) params.type = tipos;
  var q = $.trim($('#agent_search').val());
  if (q) params.q = q;

  $.ajax({
    url: AGENTES_URL,
    type: 'GET',
    data: params,
    dataType: 'json',
    success: function(json) {
      renderAgentList(json.data, json.truncado);
    },
    error: function() {
      $('#agent_list').html('<div class="text-danger small px-1 py-1">No se pudieron cargar los agentes</div>');
    }
  });
}

// Filtra una lista de checkboxes por texto, sin deseleccionar lo ya marcado.
function applyListFilter($input) {
  var $list = $($input.data('target'));
  var term = $.trim($input.val()).toLowerCase();
  var visibles = 0;
  $list.find('.group-type-item, .plaza-item, .archivo-item').each(function() {
    var coincide = term === '' || ($(this).data('label') || '').indexOf(term) !== -1;
    $(this).toggle(coincide);
    if (coincide) visibles++;
  });
  $list.find('.no-filter-results').toggle(visibles === 0 && term !== '');
}

// Un checkbox "Todos" que opera sobre lo que se ve tras la busqueda,
// no sobre la lista completa.
function updateSelectAll($selector, $container, $itemSelector) {
  var visibles = $container.find($itemSelector).filter(':visible').length;
  var marcados = $container.find($itemSelector).filter(':visible')
    .find('input[type="checkbox"]').filter(':checked').length;
  $selector.prop('checked', visibles > 0 && marcados === visibles)
    .prop('indeterminate', marcados > 0 && marcados < visibles);
}

function refreshAllSelectAll() {
  updateSelectAll($('#select_all_types'), $('#group_type_list'), '.group-type-item');
  updateSelectAll($('#select_all_plazas'), $('#plaza_list'), '.plaza-item');
  updateSelectAll($('#select_all_archivos'), $('#archivo_list'), '.archivo-item');
}

function getFilters() {
  var d = {};
  var plazas = $('.plaza-checkbox:checked').map(function() { return $(this).val(); }).get();
  if (plazas.length) d.plaza = plazas;
  var tipos = $('.group-type-checkbox:checked').map(function() { return $(this).val(); }).get();
  if (tipos.length) d.type = tipos;
  var archivos = $('.archivo-checkbox:checked').map(function() { return $(this).val(); }).get();
  if (archivos.length) d.archivo = archivos;
  var agentes = selectedAgentIds();
  if (agentes.length) d.agente = agentes;
  if ($('#conexion_filter').val()) d.conexion = $('#conexion_filter').val();
  if ($('#estado_filter').val()) d.estado = $('#estado_filter').val();
  if ($('#dias_min').val()) d.dias_min = $('#dias_min').val();
  if ($('#dias_max').val()) d.dias_max = $('#dias_max').val();
  return d;
}

function getSelectedComputerIds() {
  return Object.keys(selectedComputerIds).filter(function(id) { return selectedComputerIds[id]; }).map(Number);
}

function clearSelection() {
  selectedComputerIds = {};
  $('#select_all_computers').prop('checked', false).prop('indeterminate', false);
  $('#filesTableBody .computer-checkbox:not(:disabled)').prop('checked', false);
}

function toggleComputer(id) {
  id = String(id);
  if (selectedComputerIds[id]) {
    delete selectedComputerIds[id];
  } else {
    selectedComputerIds[id] = true;
  }
  refreshSelectAllComputers();
}

function refreshSelectAllComputers() {
  var $boxes = $('#filesTableBody .computer-checkbox:not(:disabled)');
  var total = $boxes.length;
  var checked = $boxes.filter(':checked').length;
  $('#select_all_computers').prop('checked', total > 0 && checked === total).prop('indeterminate', checked > 0 && checked < total);
}

// El thead tiene una sola fila fija: no hay segunda fila que haya que anclar
// debajo, asi que no hace falta compensar ningun desplazamiento vertical.
function sincronizarEncabezado() {
  $('#filesTable thead th').css('top', 0);
}

// El bloqueo del checkbox es solo una ayuda visual: la ventana de espera se
// vuelve a evaluar en cada recarga, y el servidor la descarta igual aunque
// alguien llame al endpoint a mano.
function enEsperaBloqueo(id) {
  var desde = stockEnEjecucion[String(id)];
  return !!desde && (Date.now() - desde) < COOLDOWN_MINUTOS * 60000;
}

function refreshCooldownChecks() {
  $('#filesTableBody .computer-checkbox').each(function() {
    var bloqueado = enEsperaBloqueo($(this).data('computer-id'));
    $(this).prop('disabled', bloqueado)
      .attr('title', bloqueado ? 'Ya se ejecuto DASTOCK.BAT en los ultimos ' + COOLDOWN_MINUTOS + ' minutos' : '');
  });
  refreshSelectAllComputers();
}

// Días sin formato: "hoy" es 0, para no sumar un día que no ha transcurrido.
function renderDias(dias) {
  if (dias === null || dias === undefined) return '<span class="celda-vacia">-</span>';
  var n = Number(dias);
  var clase = n >= 30 ? 'text-danger' : (n >= 7 ? 'text-warning' : '');
  return '<span class="' + clase + '">' + n + '</span>';
}

var currentPage = 0;
var pageSize = 10;
var totalRecords = 0;
var sortColumn = 'plaza';
var sortDirection = 'asc';
var chartInstances = {};

function loadData() {
  var filters = getFilters();
  filters.draw = 1;
  filters.start = currentPage * pageSize;
  filters.length = pageSize;
  filters.sort = sortColumn;
  filters.direction = sortDirection;

  $('#tableLoading').removeClass('d-none');
  $('#filesTableBody').empty();

  $.ajax({
    url: DATA_URL,
    type: 'GET',
    data: filters,
    success: function(json) {
      if (json.error) { console.error(json.error); return; }
      renderStats(json);
      renderTable(json);
    },
    error: function() {
      $('#tableLoading').addClass('d-none');
      $('#filesTableBody').html('<tr><td colspan="14" class="text-center py-4 text-muted">Error al cargar los datos</td></tr>');
    }
  });
}

function renderStats(json) {
  $('#total_records').text('Total: ' + (json.recordsTotal || 0) + ' registros');
  if (!json.instalaciones_stats) return;
  var s = json.instalaciones_stats;
  $('#statContempladas').text(s.total_instalaciones);
  $('#statFuera').text(s.no_contempladas + ' fuera del calculo');
  $('#statListas').text(s.listas);
  $('#statPendientes').text(s.pendientes);
  $('#statPercent').text(s.percent + '%');

  if (s.total_instalaciones === 0) {
    $('#chartsSection').addClass('d-none');
    return;
  }

  $('#chartsSection').removeClass('d-none');
  requestAnimationFrame(function() { initAllCharts(s); });
}

function initChart(id, config) {
  var canvas = document.getElementById(id);
  if (!canvas) return null;
  if (chartInstances[id]) { chartInstances[id].destroy(); }
  chartInstances[id] = new Chart(canvas, config);
  return chartInstances[id];
}

function initAllCharts(s) {
  var green = '#28a745', red = '#dc3545', amber = '#ffc107';
  var legend = { position: 'bottom', labels: { font: { size: 10 }, boxWidth: 12, padding: 8 } };

  initChart('pieFilesChart', {
    type: 'doughnut',
    data: {
      labels: ['Listas', 'Pendientes'],
      datasets: [{
        data: [s.listas, s.pendientes],
        backgroundColor: [green, red], borderWidth: 0
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false, resizeDelay: 100, cutout: '60%',
      plugins: {
        legend: legend,
        tooltip: { callbacks: { label: function(ctx) {
          var pct = s.total_instalaciones > 0 ? ((ctx.parsed / s.total_instalaciones) * 100).toFixed(1) : 0;
          return ctx.label + ': ' + ctx.parsed + ' (' + pct + '%)';
        }}}
      }
    }
  });

  if (s.per_plaza && s.per_plaza.length > 0) {
    initChart('barPlazaChart', {
      type: 'bar',
      data: {
        labels: s.per_plaza.map(function(p) { return p.plaza; }),
        datasets: [
          { label: 'Listas', data: s.per_plaza.map(function(p) { return p.listas; }), backgroundColor: green, borderRadius: 3 },
          { label: 'Pendientes', data: s.per_plaza.map(function(p) { return p.pendientes; }), backgroundColor: red, borderRadius: 3 }
        ]
      },
      options: {
        responsive: true, maintainAspectRatio: false, resizeDelay: 100,
        scales: {
          x: { stacked: true, ticks: { font: { size: 10 } }, grid: { display: false } },
          y: { stacked: true, beginAtZero: true, ticks: { font: { size: 10 } }, grid: { color: '#f0f0f0' } }
        },
        plugins: {
          legend: legend,
          tooltip: { callbacks: { label: function(ctx) {
            var plaza = s.per_plaza[ctx.dataIndex];
            var total = plaza.total;
            var pct = total > 0 ? ((ctx.parsed.y / total) * 100).toFixed(1) : 0;
            return ctx.dataset.label + ': ' + ctx.parsed.y + ' (' + pct + '%)';
          }}}
        }
      }
    });
  }

  // Una instalacion puede fallar por mas de un archivo, por eso las barras de
  // motivos suman mas que las pendientes.
  var causas = s.causas || [];
  $('#causasCard').toggleClass('d-none', causas.length === 0);
  if (causas.length > 0) {
    initChart('barCausasChart', {
      type: 'bar',
      data: {
        labels: causas.map(function(c) { return c.etiqueta; }),
        datasets: [{
          label: 'Instalaciones',
          data: causas.map(function(c) { return c.cantidad; }),
          backgroundColor: causas.map(function(c) { return c.codigo === 'PESO' ? amber : red; }),
          borderRadius: 3
        }]
      },
      options: {
        indexAxis: 'y',
        responsive: true, maintainAspectRatio: false, resizeDelay: 100,
        scales: {
          x: { beginAtZero: true, ticks: { font: { size: 10 }, precision: 0 }, grid: { color: '#f0f0f0' } },
          y: { ticks: { font: { size: 10 } }, grid: { display: false } }
        },
        plugins: {
          legend: { display: false },
          tooltip: { callbacks: { label: function(ctx) {
            return ctx.parsed.x + ' instalacion(es) pendiente(s) por este motivo';
          }}}
        }
      }
    });
  }
}

function esc(valor) {
  return $('<span>').text(valor === null || valor === undefined ? '' : valor).html();
}

function formatFecha(v) {
  if (!v) return '<span class="celda-vacia">-</span>';
  var value = String(v);
  // Se muestran los segundos solo en el title: 'dd/mm/yyyy hh:mm:ss' ocupaba
  // ~150px en cada una de las dos columnas y empujaba la tabla fuera de la tarjeta.
  if (value.length >= 19) {
    var completo = value.substring(0, 19).replace('T', ' ');
    return '<span title="' + esc(completo) + '">' + esc(completo.substring(0, 16)) + '</span>';
  }
  return esc(value);
}

function renderHash(celda) {
  if (!celda || !celda.hash_corto) {
    return '<span class="celda-vacia" title="No se encuentra archivo en esta ubicacion">no encontrado</span>';
  }
  return '<span class="hash-chip" title="' + esc(celda.hash) + '">' + esc(celda.hash_corto) + '</span>';
}

function renderTable(json) {
  totalRecords = json.recordsTotal || 0;
  var data = json.data || [];
  var $tbody = $('#filesTableBody');
  $('#tableLoading').addClass('d-none');
  $tbody.empty();

  if (data.length === 0) {
    $tbody.html('<tr><td colspan="14" class="text-center py-4 text-muted">No se encontraron archivos de stock</td></tr>');
    $('#paginationControls').addClass('d-none');
    return;
  }

  data.forEach(function(row) {
    var rbf = row.rbf || {};
    var reb = row.rebsamen || {};

    // El color de cada chip depende de si su hash coincide con el del otro disparador.
    var hayRbf = !!rbf.archivo;
    var hayRebsa = !!reb.archivo;

    var connectionDot = row.estado_equipo === 'online'
      ? '<span class="d-inline-block align-middle" style="width:10px;height:10px;border-radius:50%;background:#28a745;" title="Online"></span>'
      : '<span class="d-inline-block align-middle" style="width:10px;height:10px;border-radius:50%;background:#dc3545;" title="Offline"></span>';

    var statusBadge = ESTADOS_BADGE[row.estado]
      || '<span class="badge bg-secondary">' + esc(row.estado) + '</span>';

    var hashDiferente = hayRbf && hayRebsa && !!rbf.hash && !!reb.hash && rbf.hash !== reb.hash;

    var motivo = '';
    if (row.estado === 'no_cuenta') {
      motivo = '<div class="celda-vacia">no entra en el %</div>';
    } else if (row.estado === 'no_aplica') {
      motivo = '<div class="celda-vacia">solo almacenes</div>';
    } else if (!row.existe) {
      motivo = '<div class="celda-vacia">no aparece en el respaldo</div>';
    } else if (row.estado === 'amarillo' || row.estado === 'rojo') {
      motivo = (row.dias === null || row.dias === undefined)
        ? '<div class="celda-vacia">sin fecha de modificación</div>'
        : '<div class="celda-vacia">' + esc(row.dias) + ' días</div>';
    } else if (hashDiferente) {
      motivo = '<div class="celda-vacia">hash distinto</div>';
    }

    var senalHtml = '';
    if (row.peso_senal) {
      senalHtml = '<div class="mt-1"><span class="badge ' + (row.peso_senal.bloquea ? 'bg-danger' : 'bg-secondary') +
        '" title="' + esc(row.peso_senal.detalle) + '">' + esc(row.peso_senal.etiqueta) + '</span></div>';
    }

    // El peso llega formateado desde el servidor (separador de miles).
    var pesoRbf = rbf.peso_texto !== null && rbf.peso_texto !== undefined
      ? esc(rbf.peso_texto) : '<span class="celda-vacia">-</span>';
    var pesoReb = reb.peso_texto !== null && reb.peso_texto !== undefined
      ? esc(reb.peso_texto) : '<span class="celda-vacia">-</span>';

    $tbody.append(
      '<tr data-computer-id="' + esc(row.id) + '">' +
'<td class="text-center"><input type="checkbox" class="form-check-input computer-checkbox"' +
          (selectedComputerIds[String(row.id)] ? ' checked' : '') +
          (enEsperaBloqueo(row.id) ? ' disabled title="Ya se ejecuto DASTOCK.BAT en los ultimos ' + COOLDOWN_MINUTOS + ' minutos"' : '') +
          ' data-computer-id="' + esc(row.id) + '"></td>' +
        '<td>' + esc(row.plaza) + '</td>' +
        '<td><strong class="celda-texto celda-texto-ancha" title="' + esc(row.nombre_instalacion) + '">' + esc(row.nombre_instalacion) + '</strong></td>' +
        '<td class="text-center">' + connectionDot + '</td>' +
        '<td>' + (hayRbf ? '<strong class="celda-texto" title="' + esc(rbf.archivo) + '">' + esc(rbf.archivo) + '</strong>' : '<span class="celda-vacia">-</span>') + '</td>' +
        '<td>' + renderHash(rbf) + '</td>' +
        '<td class="text-nowrap">' + formatFecha(rbf.fecha_modificacion) + '</td>' +
        '<td class="text-center">' + pesoRbf + '</td>' +
        '<td>' + (hayRebsa ? '<strong class="celda-texto" title="' + esc(reb.archivo) + '">' + esc(reb.archivo) + '</strong>' : '<span class="celda-vacia">-</span>') + '</td>' +
        '<td>' + renderHash(reb) + '</td>' +
        '<td class="text-nowrap">' + formatFecha(reb.fecha_modificacion) + '</td>' +
        '<td class="text-center">' + pesoReb + '</td>' +
        '<td class="text-center"><div>' + statusBadge + motivo + senalHtml + '</div></td>' +
        '<td class="text-center">' + renderDias(row.dias) + '</td>' +
      '</tr>'
    );
  });

  refreshSelectAllComputers();

  updatePagination();

  sincronizarEncabezado();
}

function previewAndConfirmStock() {
  var ids = getSelectedComputerIds();
  if (ids.length === 0) {
    alert('Selecciona al menos un equipo de la tabla.');
    return;
  }

  $('#btn_run_stock').prop('disabled', true);

  $.ajax({
    url: EJECUTAR_URL,
    type: 'POST',
    data: { _token: CSRF_TOKEN, computer_ids: ids, preview: true },
    success: function(json) {
      if (!json.success) {
        alert('Error: ' + (json.message || 'Desconocido'));
        return;
      }
      if (json.count === 0) {
        alert('Ningun equipo seleccionado tiene archivos en amarillo o rojo, o ya recibio DASTOCK.BAT en los ultimos ' + COOLDOWN_MINUTOS + ' minutos.');
        return;
      }

      $('#confirmModalMessage').text(
        'Se enviara el comando ' + json.bat + ' a ' + json.count + ' equipo(s). Solo a los que tienen archivos en amarillo o rojo:'
      );
      var listHtml = '<div class="list-group list-group-flush">';
      json.computers.forEach(function(c) {
        listHtml += '<div class="list-group-item py-1 px-2"><small><strong>' + esc(c.nombre_instalacion) +
          '</strong> (' + esc(c.plaza) + ') - ' + c.archivos + ' archivo(s) en amarillo o rojo</small></div>';
      });
      listHtml += '</div>';
      $('#confirmModalList').html(listHtml);
      $('#confirmModal').modal('show');
    },
    error: function(xhr) {
      alert('Error al ejecutar: ' + (xhr.responseJSON?.message || xhr.statusText));
    },
    complete: function() {
      $('#btn_run_stock').prop('disabled', false);
    }
  });
}

function doEjecutarStock() {
  var ids = getSelectedComputerIds();
  if (ids.length === 0) {
    $('#confirmModal').modal('hide');
    alert('La seleccion cambio: no hay equipos a los que enviar el comando.');
    return;
  }

  $('#confirmEjecutarBtn').prop('disabled', true);

  $.ajax({
    url: EJECUTAR_URL,
    type: 'POST',
    data: { _token: CSRF_TOKEN, computer_ids: ids, preview: false },
    success: function(json) {
      $('#confirmModal').modal('hide');
      if (json.success) {
        alert('Comando ' + json.bat + ' enviado a ' + json.count + ' equipo(s).' +
          (json.en_espera > 0 ? ' ' + json.en_espera + ' equipo(s) ya lo habian recibido hace menos de ' + COOLDOWN_MINUTOS + ' minutos.' : ''));
        // Los equipos encolados quedan bloqueados hasta que expire la ventana de espera.
        (json.computer_ids || []).forEach(function(id) { stockEnEjecucion[String(id)] = Date.now(); });
        clearSelection();
        loadData();
        setTimeout(refreshCooldownChecks, COOLDOWN_MINUTOS * 60000 + 500);
      } else {
        alert('Error: ' + (json.message || 'Desconocido'));
      }
    },
    error: function(xhr) {
      $('#confirmModal').modal('hide');
      alert('Error al ejecutar: ' + (xhr.responseJSON?.message || xhr.statusText));
    },
    complete: function() {
      $('#confirmEjecutarBtn').prop('disabled', false);
    }
  });
}

function renderBitacora(json) {
  var statusBadges = {
    completed: '<span class="badge bg-success">Completado</span>',
    failed: '<span class="badge bg-danger">Failed</span>',
    running: '<span class="badge bg-primary">Running</span>',
    pending: '<span class="badge bg-secondary">Pending</span>'
  };

  var html = '<div class="bitacora-list">';
  json.groups.forEach(function(group, idx) {
    var countsHtml = '';
    ['completed', 'failed', 'running', 'pending'].forEach(function(s) {
      if (group.counts[s]) countsHtml += ' ' + (statusBadges[s] || s) + ' ' + group.counts[s];
    });

    html += '<div class="card mb-2">';
    html += '<div class="card-header py-1 px-2 bitacora-toggle" data-target="bitacoraBody' + idx + '" style="cursor:pointer;">';
    html += '<div class="d-flex align-items-center gap-2">';
    html += '<i class="fas fa-chevron-right toggle-icon"></i>';
    html += '<small class="fw-bold">' + esc(group.created_at) + '</small>';
    html += '<span class="badge bg-secondary">' + group.total + ' equipos</span>';
    html += countsHtml;
    html += '</div></div>';
    html += '<div id="bitacoraBody' + idx + '" class="card-body p-0" style="display:none;">';

    html += '<table class="table table-sm table-striped mb-0"><thead><tr>';
    html += '<th>Computadora</th><th>Plaza</th><th>Comando</th><th>Estado</th><th>Error</th>';
    html += '</tr></thead><tbody>';

    group.items.forEach(function(item) {
      var statusIcon = item.status === 'completed' ? '✅' : (item.status === 'failed' ? '❌' : (item.status === 'running' ? '🔄' : '⏳'));
      var errorText = '-';
      if (item.error) {
        var escaped = $('<span>').text(item.error).html().replace(/\n/g, '<br>');
        errorText = '<pre style="font-size:0.7rem;max-height:60px;overflow-y:auto;white-space:pre-wrap;word-break:break-all;background:#f8d7da;color:#721c24;padding:4px 6px;border-radius:4px;margin:0;">' + escaped + '</pre>';
      }
      html += '<tr>';
      html += '<td><strong>' + esc(item.computer) + '</strong></td>';
      html += '<td>' + esc(item.plaza) + '</td>';
      html += '<td>' + esc(item.label) + '</td>';
      html += '<td>' + statusIcon + ' ' + esc(item.status) + '</td>';
      html += '<td style="max-width:400px;">' + errorText + '</td>';
      html += '</tr>';
    });

    html += '</tbody></table>';
    html += '</div></div>';
  });
  html += '</div>';
  $('#bitacoraContent').html(html).removeClass('d-none');

  $('#bitacoraContent').off('click', '.bitacora-toggle').on('click', '.bitacora-toggle', function() {
    var targetId = $(this).data('target');
    var $body = $('#' + targetId);
    var $icon = $(this).find('.toggle-icon');
    $body.slideToggle(200);
    $icon.toggleClass('fa-chevron-right fa-chevron-down');
  });
}

function updatePagination() {
  var totalPages = Math.ceil(totalRecords / pageSize);
  if (totalPages <= 1) {
    $('#paginationControls').addClass('d-none');
    return;
  }
  $('#paginationControls').removeClass('d-none');
  var from = currentPage * pageSize + 1;
  var to = Math.min((currentPage + 1) * pageSize, totalRecords);
  $('#paginationInfo').text('Mostrando ' + from + ' a ' + to + ' de ' + totalRecords);

  var $ul = $('#paginationNumbers').empty();
  var startPage = Math.max(0, currentPage - 2);
  var endPage = Math.min(totalPages, startPage + 5);
  if (endPage - startPage < 5) startPage = Math.max(0, endPage - 5);

  if (currentPage > 0) {
    $ul.append('<li class="page-item"><a class="page-link page-btn" href="#" data-page="' + (currentPage - 1) + '">&laquo;</a></li>');
  }
  for (var i = startPage; i < endPage; i++) {
    var active = i === currentPage ? ' active' : '';
    $ul.append('<li class="page-item' + active + '"><a class="page-link page-btn" href="#" data-page="' + i + '">' + (i + 1) + '</a></li>');
  }
  if (currentPage < totalPages - 1) {
    $ul.append('<li class="page-item"><a class="page-link page-btn" href="#" data-page="' + (currentPage + 1) + '">&raquo;</a></li>');
  }
}

$(function() {
  refreshAllSelectAll();
  loadAgents();
  loadData();

  $('#btn_search').on('click', function() { currentPage = 0; loadData(); });
  $('#btn_refresh').on('click', function() { currentPage = 0; loadData(); });

  $('#paginationNumbers').on('click', '.page-btn', function(e) {
    e.preventDefault();
    currentPage = parseInt($(this).data('page'), 10);
    loadData();
  });

  $('#btn_reset_filters').on('click', function() {
    $('.group-type-checkbox').prop('checked', false);
    $('.plaza-checkbox').prop('checked', false);
    $('.archivo-checkbox').prop('checked', false);
    $('.filter-search').val('');
    $('.group-type-item, .plaza-item, .archivo-item').show();
    $('.no-filter-results').addClass('d-none');
    $('#agent_search').val('');
    selectedAgents = {};
    $('#conexion_filter').val('');
    $('#estado_filter').val('');
    $('#dias_min, #dias_max').val('');
    currentPage = 0;
    $('#select_all_types, #select_all_plazas, #select_all_archivos')
      .prop('checked', false).prop('indeterminate', false);
    refreshAllSelectAll();
    loadAgents();
    loadData();
  });

  // Buscadores de cada lista de checkboxes.
  $('.filter-search').on('input', function() {
    applyListFilter($(this));
    refreshAllSelectAll();
  });

  $('#select_all_types').on('change', function() {
    var marcar = $(this).prop('checked');
    $('#group_type_list .group-type-item:visible .group-type-checkbox')
      .prop('checked', marcar);
    currentPage = 0;
    loadAgents();
    loadData();
  });
  $('#select_all_plazas').on('change', function() {
    var marcar = $(this).prop('checked');
    $('#plaza_list .plaza-item:visible .plaza-checkbox').prop('checked', marcar);
    currentPage = 0;
    loadAgents();
    loadData();
  });
  $('#select_all_archivos').on('change', function() {
    var marcar = $(this).prop('checked');
    $('#archivo_list .archivo-item:visible .archivo-checkbox').prop('checked', marcar);
    refreshAllSelectAll();
    currentPage = 0;
    loadData();
  });
  $('.plaza-checkbox, .group-type-checkbox').on('change', function() {
    refreshAllSelectAll();
    currentPage = 0;
    loadAgents();
    loadData();
  });
  $('.archivo-checkbox').on('change', function() {
    refreshAllSelectAll();
    currentPage = 0;
    loadData();
  });
  $('#estado_filter, #conexion_filter').on('change', function() {
    currentPage = 0;
    loadData();
  });
  $('#dias_min, #dias_max').on('change', function() {
    currentPage = 0;
    loadData();
  });

  // Seleccion de equipos para el envio de DASTOCK.BAT.
  $('#select_all_computers').on('change', function() {
    var marcar = $(this).prop('checked');
    $('#filesTableBody .computer-checkbox:not(:disabled)').each(function() {
      var id = String($(this).data('computer-id'));
      this.checked = marcar;
      if (marcar) {
        selectedComputerIds[id] = true;
      } else {
        delete selectedComputerIds[id];
      }
    });
    refreshSelectAllComputers();
  });
  $('#filesTableBody').on('change', '.computer-checkbox', function(e) {
    e.stopPropagation();
    var id = String($(this).data('computer-id'));
    if (this.checked) {
      selectedComputerIds[id] = true;
    } else {
      delete selectedComputerIds[id];
    }
    refreshSelectAllComputers();
  });
  // Clic en la fila alterna la seleccion del equipo, igual que en dbf-files-especificos.
  $('#filesTableBody').on('click', 'tr[data-computer-id]', function(e) {
    if ($(e.target).is('input, label, button, a')) return;
    var $box = $(this).find('.computer-checkbox');
    if ($box.is(':disabled')) return;
    toggleComputer($(this).data('computer-id'));
    $box.prop('checked', !!selectedComputerIds[String($(this).data('computer-id'))]);
  });

  $('#btn_run_stock').on('click', function() { previewAndConfirmStock(); });
  $('#confirmEjecutarBtn').on('click', function() { doEjecutarStock(); });

  $('#btn_bitacora').on('click', function() {
    $('#bitacoraContent').addClass('d-none').empty();
    $('#bitacoraLoading').removeClass('d-none');
    $('#bitacoraModal').modal('show');

    $.ajax({
      url: BITACORA_URL,
      type: 'GET',
      data: { limit: 100 },
      success: function(json) {
        $('#bitacoraLoading').addClass('d-none');
        if (!json.success || !json.groups.length) {
          $('#bitacoraContent').removeClass('d-none').html('<div class="text-center text-muted py-4">No hay ejecuciones registradas.</div>');
          return;
        }
        renderBitacora(json);
      },
      error: function() {
        $('#bitacoraLoading').addClass('d-none');
        $('#bitacoraContent').removeClass('d-none').html('<div class="alert alert-danger mb-0">Error al cargar la bitacora.</div>');
      }
    });
  });

  // Buscador de agentes con checkboxes: el texto solo acota la lista,
  // la seleccion se hace marcando los checkboxes.
  var agentSearchTimer = null;
  $('#agent_search').on('input', function() {
    clearTimeout(agentSearchTimer);
    agentSearchTimer = setTimeout(loadAgents, 300);
  });
  $('#agent_list').on('change', '.agent-checkbox', function() {
    selectedAgents[this.value] = this.checked;
    refreshAgentCount();
    currentPage = 0;
    loadData();
  });
  $('#agent_select_all').on('change', function() {
    var marcar = $(this).prop('checked');
    $('#agent_list .agent-checkbox').each(function() {
      this.checked = marcar;
      selectedAgents[this.value] = marcar;
    });
    refreshAgentCount();
    currentPage = 0;
    loadData();
  });
  $('#pageSizeSelect').on('change', function() {
    pageSize = parseInt($(this).val(), 10) || 10;
    currentPage = 0;
    loadData();
  });

  // El encabezado fijo depende de la altura real de su primera fila: se recalcula al
  // redimensionar la ventana (zoom, cambio de ancho de columna) y al minimizar la tarjeta.
  var resizeEncabezadoTimer = null;
  $(window).on('resize', function() {
    clearTimeout(resizeEncabezadoTimer);
    resizeEncabezadoTimer = setTimeout(sincronizarEncabezado, 150);
  });

  $('#btn_export').on('click', function() {
    var f = getFilters();
    var params = new URLSearchParams();
    (f.plaza || []).forEach(function(v) { params.append('plaza[]', v); });
    (f.type || []).forEach(function(v) { params.append('type[]', v); });
    (f.archivo || []).forEach(function(v) { params.append('archivo[]', v); });
    (f.agente || []).forEach(function(v) { params.append('agente[]', v); });
    if (f.conexion) params.append('conexion', f.conexion);
    if (f.estado) params.append('estado', f.estado);
    if (f.dias_min) params.append('dias_min', f.dias_min);
    if (f.dias_max) params.append('dias_max', f.dias_max);
    params.append('_t', Date.now());
    window.open(EXPORT_URL + '?' + params.toString(), '_blank');
  });

  $(document).on('click', '.btn-card-minimize', function() {
    var $btn = $(this);
    var $card = $btn.closest('.card');
    var $body = $card.children('.card-body');
    $body.slideToggle(200, function() {
      window.dispatchEvent(new Event('resize'));
      sincronizarEncabezado();
    });
    $btn.find('i').toggleClass('fa-minus fa-plus');
  });

  $('#filesTable thead th[data-sort]').on('click', function() {
    var col = $(this).data('sort');
    if (sortColumn === col) {
      sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
      sortColumn = col;
      sortDirection = 'asc';
    }
    $('#filesTable thead th i').removeClass('fa-sort-up fa-sort-down').addClass('fa-sort');
    $(this).find('i').removeClass('fa-sort').addClass(sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down');
    currentPage = 0;
    loadData();
  });
});
</script>
@endsection
