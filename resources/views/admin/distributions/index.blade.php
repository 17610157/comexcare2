@extends('adminlte::page')

@use('Illuminate\Support\Str')

@section('title', 'Distribuciones')

@once
    @push('scripts')
        @vite(['resources/js/app.js'])
        <script>
            window.Laravel = window.Laravel || {};
            window.Laravel.broadcastingPort = 6001;
            window.Laravel.broadcastingHost = window.location.hostname;
        </script>
    @endpush
@endonce

@section('content_header')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <h1>Distribuciones</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#createDistributionModal">
                        <i class="fas fa-plus"></i> Crear Distribución
                    </button>
                </div>
                <div class="card-body">
                    <table id="distributionsTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
<th>ID</th>
                                 <th>Nombre</th>
                                 <th>Tipo</th>
                                 <th>Estado</th>
                                 <th>Archivos</th>
                                 <th>Objetivos</th>
                                 <th>Progreso</th>
                                 <th>Creado</th>
                                 <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($distributions as $distribution)
                                <tr>
                                    <td>{{ $distribution->id }}</td>
                                    <td>{{ $distribution->name }}</td>
                                    <td>
                                        @if($distribution->type === 'immediate')
                                            <span class="badge badge-primary">Inmediato</span>
                                        @elseif($distribution->type === 'scheduled')
                                            <span class="badge badge-warning">Programado</span>
                                        @else
                                            <span class="badge badge-info">Recurrente</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($distribution->status === 'completed')
<span class="badge badge-success">Completado</span>
                                        @elseif($distribution->status === 'in_progress')
<span class="badge badge-primary">En Progreso</span>
                                        @elseif($distribution->status === 'pending')
<span class="badge badge-warning">Pendiente</span>
                                        @elseif($distribution->status === 'failed')
<span class="badge badge-danger">Fallido</span>
                                        @elseif($distribution->status === 'stopped')
<span class="badge badge-secondary">Detenido</span>
                                        @else
                                            <span class="badge badge-secondary">{{ $distribution->status }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $distribution->files->count() }}</td>
                                    <td>{{ $distribution->targets->count() }}</td>
                                    <td>
                                        @php
                                            $completed = $distribution->targets->where('status', 'completed')->count();
                                            $total = $distribution->targets->count();
                                            $percent = $total > 0 ? round(($completed / $total) * 100) : 0;
                                        @endphp
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar" role="progressbar" style="width: {{ $percent }}%;" 
                                                 aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100">
                                                {{ $percent }}%
                                            </div>
                                        </div>
                                        <small>{{ $completed }}/{{ $total }} completados</small>
                                    </td>
                                    <td>{{ $distribution->created_at->diffForHumans() }}</td>
                                    <td>
                                        <button type="button" class="btn btn-info btn-sm" data-toggle="modal" 
                                                data-target="#viewDistributionModal{{ $distribution->id }}">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        @if($distribution->type === 'recurring')
                                            @if($distribution->status === 'stopped')
                                                <button type="button" class="btn btn-success btn-sm" 
                                                        onclick="startDistribution({{ $distribution->id }}, '{{ $distribution->name }}')">
                                                    <i class="fas fa-play"></i>
                                                </button>
                                            @else
                                                <button type="button" class="btn btn-secondary btn-sm" 
                                                        onclick="stopDistribution({{ $distribution->id }}, '{{ $distribution->name }}')">
                                                    <i class="fas fa-stop"></i>
                                                </button>
                                            @endif
                                        @endif
                                        <button type="button" class="btn btn-warning btn-sm" 
                                                onclick="editDistribution({{ $distribution->id }}, '{{ $distribution->name }}', '{{ $distribution->type }}', '{{ $distribution->distribution_type ?? 'file' }}', '{{ $distribution->subfolder ?? '' }}', '{{ $distribution->description ?? '' }}', '{{ $distribution->scheduled_at ?? '' }}', '{{ json_encode($distribution->files->pluck('file_name')->toArray()) }}', {{ $distribution->targets->count() }}, '{{ json_encode($distribution->targets->pluck('computer_id')->toArray()) }}', '{{ $distribution->command ?? '' }}', '{{ $distribution->command_args ?? '' }}')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm" 
                                                onclick="deleteDistribution({{ $distribution->id }}, '{{ $distribution->name }}')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Modal -->
    <div class="modal fade" id="createDistributionModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <form id="createDistributionForm" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header bg-primary">
                        <h5 class="modal-title">Crear Distribución</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
<label>Nombre *</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
<label>Tipo *</label>
                                    <select name="type" class="form-control" id="modalDistributionType" required>
                                        <option value="immediate">Inmediato</option>
                                        <option value="scheduled">Programado</option>
                                        <option value="recurring">Recurrente</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Scheduled -->
                        <div class="row" id="modalScheduledRow" style="display:none;">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Fecha Programada</label>
                                    <input type="datetime-local" name="scheduled_at" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Hora Programada</label>
                                    <input type="time" name="scheduled_time" class="form-control">
                                </div>
                            </div>
                        </div>

                        <!-- Recurring -->
                        <div class="row" id="modalRecurringRow" style="display:none;">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Tipo de Frecuencia</label>
                                    <select name="recurrence" class="form-control" id="modalRecurrenceSelect">
                                        <option value="daily">Diario</option>
                                        <option value="weekly">Semanal</option>
                                        <option value="monthly">Mensual</option>
                                        <option value="hourly">Cada Hora</option>
                                        <option value="minutes">Cada Minutos</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3" id="modalFrequencyIntervalRow" style="display:none;">
                                <div class="form-group">
                                    <label>Intervalo</label>
                                    <select name="frequency_interval" class="form-control" id="modalFrequencyInterval">
                                        <option value="1">1</option>
                                        <option value="2">2</option>
                                        <option value="3">3</option>
                                        <option value="5">5</option>
                                        <option value="10">10</option>
                                        <option value="15">15</option>
                                        <option value="30">30</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3" id="modalWeekDaysRow" style="display:none;">
                                <div class="form-group">
                                    <label>Días de la Semana</label>
                                    <select name="week_days[]" class="form-control" multiple style="height: 80px;">
                                        <option value="monday">Lunes</option>
                                        <option value="tuesday">Martes</option>
                                        <option value="wednesday">Miércoles</option>
                                        <option value="thursday">Jueves</option>
                                        <option value="friday">Viernes</option>
                                        <option value="saturday">Sábado</option>
                                        <option value="sunday">Domingo</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3" id="modalHourlyTimeRow">
                                <div class="form-group">
                                    <label>Hora Programada</label>
                                    <input type="time" name="scheduled_time" class="form-control">
                                </div>
                            </div>
                        </div>

<div class="form-group">
                                     <label>Descripción</label>
                                     <textarea name="description" class="form-control" rows="3"></textarea>
                                 </div>

                        <!-- Tipo de distribución -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Tipo de Distribución</label>
                                    <select name="distribution_type" class="form-control" id="modalDistributionTypeField">
                                        <option value="file">Archivo Normal</option>
                                        <option value="update">Actualización (Subcarpeta)</option>
                                        <option value="command">Comando/Ejecutar</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6" id="modalSubfolderRow">
                                <div class="form-group">
                                    <label>Subcarpeta de Destino</label>
                                    <input type="text" name="subfolder" class="form-control" placeholder="ej: actualizaciones o actualizaciones/2026">
                                    <small class="text-muted">Sin / al inicio. Ej: carpeta o carpeta/subcarpeta</small>
                                </div>
                            </div>
                            <div class="col-md-12 d-none" id="modalCommandRow">
                                <div class="form-group">
                                    <label>Comando a Ejecutar</label>
                                    <input type="text" name="command" class="form-control" placeholder="ej: powershell -ExecutionPolicy Bypass -File script.ps1">
                                    <small class="text-muted">Comando o programa a ejecutar en el agente</small>
                                </div>
                                <div class="form-group">
                                    <label>Argumentos (opcional)</label>
                                    <input type="text" name="command_args" class="form-control" placeholder="ej: -Param1 valor1 -Param2 valor2">
                                </div>
                            </div>
                        </div>
<!-- Archivo tipo file input -->
                        <div class="form-group" id="modalFileGroup">
<label>Archivos *</label>
                             <input type="file" name="files[]" class="form-control" multiple required id="fileInput">
                             <small class="text-muted">Seleccione múltiples archivos para distribuir</small>
                            <div id="fileList" class="mt-2"></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
<label>Tipo de Objetivo *</label>
                                     <select name="target_type" class="form-control" id="modalTargetType" required>
                                         <option value="all">Todas las Computadoras</option>
                                         <option value="group">Grupos Específicos</option>
                                         <option value="specific">Específico</option>
                                     </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group" id="modalGroupsSelect" style="display:none;">
                                    <label>Grupos</label>
                                    <select name="group_ids[]" class="form-control" multiple style="height: 100px;">
                                        @foreach($groups ?? [] as $group)
                                            <option value="{{ $group->id }}">{{ $group->name }} ({{ $group->type ?? 'Sin tipo' }})</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Ctrl/Cmd para seleccionar varios</small>
                                </div>
                                <div class="form-group" id="modalSpecificComputers" style="display:none;">
                                    <label>Computadoras</label>
                                    <input type="text" class="form-control mb-2" placeholder="Buscar por nombre o short key..." onkeyup="filterComputers(this, 'modalComputersSelect')">
                                    <select name="computer_ids[]" id="modalComputersSelect" class="form-control" multiple style="height: 150px;">
                                        @foreach($computers ?? [] as $computer)
                                            <option value="{{ $computer->id }}" data-search="{{ strtolower($computer->computer_name.' '.($computer->short_key ?? '')) }}">
                                                {{ $computer->computer_name }} {{ $computer->short_key ? '('.$computer->short_key.')' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted" id="modalComputersCount">{{ count($computers ?? []) }} computadoras</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                         <button type="submit" class="btn btn-primary" id="submitBtn">
                             <i class="fas fa-upload"></i> Crear y Distribuir
                         </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Detail Modals -->
    @foreach($distributions as $distribution)
    <div class="modal fade" id="viewDistributionModal{{ $distribution->id }}" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info">
                    <h5 class="modal-title">Distribución: {{ $distribution->name }}</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Detalles</h6>
                            <table class="table table-sm">
                                <tr><th>ID:</th><td>{{ $distribution->id }}</td></tr>
                                <tr><th>Nombre:</th><td>{{ $distribution->name }}</td></tr>
                                 <tr><th>Tipo:</th><td>{{ $distribution->type }}</td></tr>
                                 <tr><th>Estado:</th><td>{{ $distribution->status }}</td></tr>
                                 <tr><th>Creado:</th><td>{{ $distribution->created_at }}</td></tr>
                                 <tr><th>Descripción:</th><td>{{ $distribution->description ?? 'N/A' }}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Archivos</h6>
                            <ul class="list-group">
                                @foreach($distribution->files as $file)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        {{ $file->file_name }}
                                        <span class="badge badge-primary badge-pill">{{ number_format($file->file_size / 1024, 2) }} KB</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <h6>Progreso de Objetivos</h6>
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                         <th>CLAVECORTA</th>
                                         <th>PLAZA</th>
                                         <th>Estado</th>
                                         <th>Progreso</th>
                                         <th>Intentos</th>
                                         <th>Error</th>
                                         <th>Última Actualización</th>
                                         <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($distribution->targets as $target)
                                        <tr>
                                                <td>{{ $target->computer->short_key ?? '-' }}</td>
                                                <td>{{ $target->computer->plaza ?? '-' }}</td>
                                            <td>
                                                @if($target->computer?->status === 'offline')
                                                    <span class="badge badge-secondary">Equipo Apagado</span>
                                                @elseif($target->status === 'completed')
<span class="badge badge-success">Completado</span>
                                                @elseif($target->status === 'in_progress')
<span class="badge badge-primary">En Progreso</span>
                                                @elseif($target->status === 'failed')
<span class="badge badge-danger">Fallido</span>
                                                @else
                                                    <span class="badge badge-warning">{{ $target->status }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 15px; width: 100px;">
                                                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $target->progress ?? 0 }}%;">
                                                    </div>
                                                </div>
                                                {{ $target->progress ?? 0 }}%
                                            </td>
                                            <td>{{ $target->attempts ?? 0 }}</td>
                                            <td>
                                                @if($target->computer?->status === 'offline')
                                                    <span class="text-muted">Equipo Apagado</span>
                                                @elseif($target->error_message)
                                                    <span class="text-danger" title="{{ $target->error_message }}">
                                                        <i class="fas fa-exclamation-circle"></i> {{ Str::limit($target->error_message, 30) }}
                                                    </span>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>{{ $target->updated_at ? $target->updated_at->diffForHumans() : 'N/A' }}</td>
                                            <td>
                                                @if(in_array($target->status, ['failed', 'pending']))
                                                    <form action="{{ route('admin.distributions.retry-target', $target) }}" method="POST" style="display:inline;">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-primary" title="Reenviar">
                                                            <i class="fas fa-redo"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
<button type="button" class="btn btn-primary" onclick="refreshDistribution({{ $distribution->id }})">
                         <i class="fas fa-sync"></i> Actualizar
                     </button>
                </div>
            </div>
        </div>
    </div>
    @endforeach

    <!-- Edit Modal -->
    <div class="modal fade" id="editDistributionModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form id="editDistributionForm">
                    @csrf
                    @method('PUT')
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title">Editar Distribución</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="editId">
                        <div class="form-group">
<label>Nombre *</label>
                             <input type="text" name="name" id="editName" class="form-control" required>
                        </div>
                        <div class="form-group">
<label>Tipo *</label>
                             <select name="type" id="editType" class="form-control" required>
                                <option value="immediate">Inmediato</option>
                                <option value="scheduled">Programado</option>
                                <option value="recurring">Recurrente</option>
                            </select>
                        </div>
                        <div class="form-group" id="editScheduledAtGroup" style="display:none;">
                            <label>Fecha Programada</label>
                            <input type="datetime-local" name="scheduled_at" id="editScheduledAt" class="form-control">
                        </div>
                        <div class="form-group">
<label>Descripción</label>
                             <textarea name="description" id="editDescription" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Tipo de Distribución</label>
                                    <select name="distribution_type" id="editDistributionType" class="form-control">
                                        <option value="file">Archivo Normal</option>
                                        <option value="update">Actualización (Subcarpeta)</option>
                                        <option value="command">Comando/Ejecutar</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6" id="editSubfolderRow">
                                <div class="form-group">
                                    <label>Subcarpeta de Destino</label>
                                    <input type="text" name="subfolder" id="editSubfolder" class="form-control" placeholder="ej: actualizaciones">
                                    <small class="text-muted">Opcional: deja vacío para ruta principal</small>
                                </div>
                            </div>
                            <div class="col-md-12 d-none" id="editCommandRow">
                                <div class="form-group">
                                    <label>Comando a Ejecutar</label>
                                    <input type="text" name="command" id="editCommand" class="form-control" placeholder="ej: powershell -ExecutionPolicy Bypass -File script.ps1">
                                </div>
                                <div class="form-group">
                                    <label>Argumentos (opcional)</label>
                                    <input type="text" name="command_args" id="editCommandArgs" class="form-control" placeholder="ej: -Param1 valor1">
                                </div>
                            </div>
                        </div>

                        <div class="card card-info mt-3" id="editFilesCard">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Archivos a Distribuir</h6>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
<label>Archivos *</label>
                                     <input type="file" name="files[]" class="form-control" multiple id="editFileInput">
                                    <div id="editFileList" class="mt-2"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="form-group">
<label>Tipo de Objetivo *</label>
                                     <select name="target_type" id="editTargetType" class="form-control" required>
                                         <option value="all">Todas las Computadoras</option>
                                         <option value="group">Grupos Específicos</option>
                                         <option value="specific">Específico</option>
                                     </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group d-none" id="editModalGroupsSelect">
                                    <label>Grupos</label>
                                    <select name="group_ids[]" id="editGroupIds" class="form-control" multiple style="height: 100px;">
                                        @foreach($groups ?? [] as $group)
                                            <option value="{{ $group->id }}">{{ $group->name }} ({{ $group->type ?? 'Sin tipo' }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group d-none" id="editModalSpecificComputers">
                                    <label>Computadoras</label>
                                    <input type="text" class="form-control mb-2" placeholder="Buscar por nombre o short key..." onkeyup="filterComputers(this, 'editComputerIds')">
                                    <select name="computer_ids[]" id="editComputerIds" class="form-control" multiple style="height: 150px;">
                                        @foreach($computers ?? [] as $computer)
                                            <option value="{{ $computer->id }}" data-search="{{ strtolower($computer->computer_name.' '.($computer->short_key ?? '')) }}">
                                                {{ $computer->computer_name }} {{ $computer->short_key ? '('.$computer->short_key.')' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted" id="editComputersCount">{{ count($computers ?? []) }} computadoras</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                         <button type="button" class="btn btn-info" id="editRestartBtn" onclick="restartDistribution()">
                             <i class="fas fa-redo"></i> Reutilizar y Reiniciar
                         </button>
                         <button type="submit" class="btn btn-warning" id="editSubmitBtn">
                             <i class="fas fa-save"></i> Guardar Cambios
                         </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">
    <style>
        #distributionsTable td:last-child,
        #distributionsTable th:last-child {
            white-space: normal !important;
            overflow: visible !important;
            text-overflow: unset !important;
            max-width: none !important;
            width: 180px;
        }
        #distributionsTable .btn-sm {
            padding: 0.25rem 0.4rem;
            font-size: 0.8rem;
        }
    </style>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>
<script>
// Polling intervals (fallback)
let pollingIntervals = {};
let pendingRequests = {};
const POLL_INTERVAL = 2000; // 2 seconds

// Check for distributions that need polling (stored in localStorage)
function getPendingDistributions() {
    try {
        const stored = localStorage.getItem('pendingDistributions');
        return stored ? JSON.parse(stored) : [];
    } catch(e) {
        return [];
    }
}

function addPendingDistribution(id) {
    try {
        const pending = getPendingDistributions();
        if (!pending.includes(id)) {
            pending.push(id);
            localStorage.setItem('pendingDistributions', JSON.stringify(pending));
        }
    } catch(e) {}
}

function removePendingDistribution(id) {
    try {
        const pending = getPendingDistributions();
        const idx = pending.indexOf(id);
        if (idx > -1) {
            pending.splice(idx, 1);
            localStorage.setItem('pendingDistributions', JSON.stringify(pending));
        }
    } catch(e) {}
}

function clearPendingDistributions() {
    localStorage.removeItem('pendingDistributions');
}

function filterComputers(input, selectId) {
    const filter = input.value.toLowerCase();
    const select = document.getElementById(selectId);
    let visibleCount = 0;

    for (const option of select.options) {
        const searchData = option.getAttribute('data-search') || option.text.toLowerCase();
        const match = searchData.includes(filter);
        option.style.display = match ? '' : 'none';
        if (match) visibleCount++;
    }

    const countEl = document.getElementById(selectId === 'editComputerIds' ? 'editComputersCount' : 'modalComputersCount');
    if (countEl) {
        countEl.textContent = visibleCount + ' de ' + select.options.length + ' computadoras';
    }
}

// WebSocket connection tracking
const wsSubscriptions = new Set();

function updateDistributionProgressUI(data) {
    const distributionId = data.distribution_id;
    
    // Update main table row
    const row = $('#distributionsTable tbody tr').filter(function() {
        return $(this).find('td:first').text() == distributionId;
    });
    
    if (row.length) {
        // Update progress bar
        const progressBar = row.find('.progress-bar');
        progressBar.css('width', data.percent + '%').attr('aria-valuenow', data.percent);
        progressBar.text(data.percent + '%');
        
        // Update small text
        row.find('td:nth-child(7) small').text(data.completed_targets + '/' + data.total_targets + ' completed');
        
        // Update status badge
        const statusCell = row.find('td:nth-child(4)');
        let statusBadge = '';
        switch(data.distribution_status) {
            case 'completed':
                statusBadge = '<span class="badge badge-success">Completed</span>';
                break;
            case 'in_progress':
                statusBadge = '<span class="badge badge-primary">En Progreso</span>';
                break;
            case 'pending':
                statusBadge = '<span class="badge badge-warning">Pending</span>';
                break;
            case 'failed':
                statusBadge = '<span class="badge badge-danger">Failed</span>';
                break;
            case 'stopped':
                statusBadge = '<span class="badge badge-secondary">Stopped</span>';
                break;
            default:
                statusBadge = '<span class="badge badge-secondary">' + data.distribution_status + '</span>';
        }
        statusCell.html(statusBadge);
        
        // Update view modal if open
        const modal = $('#viewDistributionModal' + distributionId);
        if (modal.length && modal.is(':visible')) {
            const modalProgressBar = modal.find('.modal-body .progress-bar');
            if (modalProgressBar.length) {
                modalProgressBar.css('width', data.percent + '%');
            }
            modal.find('.modal-body small').text(data.completed_targets + '/' + data.total_targets + ' completed');
        }
    }
}

function subscribeToDistribution(distributionId) {
    if (wsSubscriptions.has(distributionId)) return;
    
    // Check if Echo is available, otherwise use polling
    if (typeof window.Echo === 'undefined') {
        console.log('Echo not available, using polling for distribution:', distributionId);
        startPollingDistribution(distributionId);
        return;
    }
    
    const channelName = 'distribution.' + distributionId;
    console.log('Subscribing to WebSocket channel:', channelName);
    
    window.Echo.private(channelName)
        .listen('.distribution.progress', (data) => {
            console.log('WebSocket event received:', data);
            updateDistributionProgressUI(data);
            
            // Stop WebSocket if completed or failed
            if (data.distribution_status === 'completed' || data.distribution_status === 'failed') {
                setTimeout(() => {
                    window.Echo.leave(channelName);
                    wsSubscriptions.delete(distributionId);
                }, 2000);
            }
        })
        .error((error) => {
            console.error('WebSocket error for distribution', distributionId, error);
            // Fallback to polling on WebSocket error
            startPollingDistribution(distributionId);
        });
    
    wsSubscriptions.add(distributionId);
}

function subscribeToAllDistributions() {
    @foreach($distributions as $distribution)
        @if($distribution->status === 'pending' || $distribution->status === 'in_progress')
            subscribeToDistribution({{ $distribution->id }});
        @endif
    @endforeach
}

function updateDistributionProgress(distributionId) {
    if (pendingRequests[distributionId]) {
        return;
    }

    pendingRequests[distributionId] = true;

    $.ajax({
        url: '/admin/distributions/' + distributionId + '/progress',
        type: 'GET',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        complete: function() {
            pendingRequests[distributionId] = false;
        },
        success: function(data) {
            // Update main table row
            const row = $('#distributionsTable tbody tr').filter(function() {
                return $(this).find('td:first').text() == data.id;
            });
            
            if (row.length) {
                // Update progress bar
                const progressBar = row.find('.progress-bar');
                progressBar.css('width', data.percent + '%').attr('aria-valuenow', data.percent);
                progressBar.text(data.percent + '%');
                
                // Update small text
                row.find('td:nth-child(7) small').text(data.completed + '/' + data.total + ' completed');
                
                // Update status badge
                const statusCell = row.find('td:nth-child(4)');
                let statusBadge = '';
                switch(data.status) {
                    case 'completed':
                        statusBadge = '<span class="badge badge-success">Completed</span>';
                        break;
                    case 'in_progress':
statusBadge = '<span class="badge badge-primary">En Progreso</span>';
                        break;
                    case 'pending':
                        statusBadge = '<span class="badge badge-warning">Pending</span>';
                        break;
                    case 'failed':
                        statusBadge = '<span class="badge badge-danger">Failed</span>';
                        break;
                    case 'stopped':
                        statusBadge = '<span class="badge badge-secondary">Stopped</span>';
                        break;
                    default:
                        statusBadge = '<span class="badge badge-secondary">' + data.status + '</span>';
                }
                statusCell.html(statusBadge);
            }
            
            // Stop polling if completed or failed
            if (data.status === 'completed' || data.status === 'failed') {
                stopPollingDistribution(distributionId);
            }
        },
        error: function(xhr) {
            if (xhr.status === 404) {
                stopPollingDistribution(distributionId);
            }
        }
    });
}

function startPollingDistribution(distributionId) {
    // Clear existing interval if any
    if (pollingIntervals[distributionId]) {
        clearInterval(pollingIntervals[distributionId]);
    }

    // Save to localStorage so polling continues after page refresh
    addPendingDistribution(distributionId);

    // Start new polling interval
    pollingIntervals[distributionId] = setInterval(function() {
        updateDistributionProgress(distributionId);
    }, POLL_INTERVAL);

    // Initial update
    updateDistributionProgress(distributionId);
}

function stopPollingDistribution(distributionId) {
    if (pollingIntervals[distributionId]) {
        clearInterval(pollingIntervals[distributionId]);
        delete pollingIntervals[distributionId];
    }
    removePendingDistribution(distributionId);
}

$(document).ready(function() {
    // Clean stale localStorage entries (distributions that no longer exist or are done)
    const storedPending = getPendingDistributions();
    const activeIds = new Set([
        @foreach($distributions as $distribution)
            @if($distribution->status === 'pending' || $distribution->status === 'in_progress')
                {{ $distribution->id }},
            @endif
        @endforeach
    ]);
    storedPending.forEach(id => {
        if (!activeIds.has(id)) {
            removePendingDistribution(id);
        }
    });

    // Try WebSocket first; falls back to polling automatically
    subscribeToAllDistributions();

    // Initialize DataTable
    $('#distributionsTable').DataTable({
        "order": [[0, "desc"]],
        "language": {
            "decimal": "",
            "emptyTable": "No hay datos disponibles",
            "info": "Mostrando _START_ a _END_ de _TOTAL_ entradas",
            "infoEmpty": "Mostrando 0 a 0 de 0 entradas",
            "infoFiltered": "(filtrado de _MAX_ entradas totales)",
            "infoPostFix": "",
            "thousands": ",",
            "lengthMenu": "Mostrar _MENU_ entradas",
            "loadingRecords": "Cargando...",
            "processing": "Procesando...",
            "search": "Buscar:",
            "zeroRecords": "No se encontraron registros coincidentes",
            "paginate": {
                "first": "Primero",
                "last": "Último",
                "next": "Siguiente",
                "previous": "Anterior"
            },
            "aria": {
                "sortAscending": ": activar para ordenar la columna ascendente",
                "sortDescending": ": activar para ordenar la columna descendente"
            }
        }
    });

    let validatedFiles = [];
    let hasBlockedFiles = false;

    function validateSelectedFiles(fileInput, callback) {
        const files = fileInput.files;
        if (!files.length) {
            validatedFiles = [];
            hasBlockedFiles = false;
            if (callback) callback(true);
            return;
        }

        const fileNames = [];
        for (let i = 0; i < files.length; i++) {
            fileNames.push(files[i].name);
        }

        $.ajax({
            url: '{{ route("admin.file-lists.validate") }}',
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                files: fileNames
            },
            success: function(data) {
                hasBlockedFiles = false;

                if (data.blacklisted && data.blacklisted.length > 0) {
                    hasBlockedFiles = true;
                    let list = data.blacklisted.map(function(f) {
                        return '<li>' + f + '</li>';
                    }).join('');
                    Swal.fire({
                        icon: 'error',
                        title: 'Archivos en Blacklist',
                        html: '<div class="text-left">Los siguientes archivos <strong>no pueden ser enviados</strong> porque están en la lista negra:</div>' +
                              '<ul class="text-left mt-2" style="color:#dc3545;font-weight:bold;">' + list + '</ul>',
                        confirmButtonText: 'Entendido'
                    });
                }

                if (data.not_whitelisted && data.not_whitelisted.length > 0) {
                    hasBlockedFiles = true;
                    let list = data.not_whitelisted.map(function(f) {
                        return '<li>' + f + '</li>';
                    }).join('');
                    Swal.fire({
                        icon: 'warning',
                        title: 'Archivos no permitidos',
                        html: '<div class="text-left">Los siguientes archivos <strong>no están en la whitelist</strong> y no pueden ser enviados:</div>' +
                              '<ul class="text-left mt-2" style="color:#856404;font-weight:bold;">' + list + '</ul>',
                        confirmButtonText: 'Entendido'
                    });
                }

                if (callback) callback(!hasBlockedFiles);
            },
            error: function() {
                if (callback) callback(true);
            }
        });
    }

    // File input change
    $('#fileInput').change(function() {
        const files = this.files;
        let html = '<ul class="list-group" style="max-height: 150px; overflow-y: auto;">';
        for (let i = 0; i < files.length; i++) {
            html += '<li class="list-group-item py-1">' + files[i].name + ' (' + (files[i].size / 1024).toFixed(2) + ' KB)</li>';
        }
        html += '</ul>';
        $('#fileList').html(html);
        validateSelectedFiles(this);
    });

    // Edit File input change
    $('#editFileInput').change(function() {
        const files = this.files;
        if (files.length > 0) {
            let html = '<ul class="list-group" style="max-height: 150px; overflow-y: auto;">';
            for (let i = 0; i < files.length; i++) {
                html += '<li class="list-group-item py-1">' + files[i].name + ' (' + (files[i].size / 1024).toFixed(2) + ' KB) (nuevo)</li>';
            }
            html += '</ul>';
            $('#editFileList').html(html);
            validateSelectedFiles(this);
        }
    });

    // Distribution type change (modal)
    $('#modalDistributionType').change(function() {
        $('#modalScheduledRow').toggle(this.value === 'scheduled');
        $('#modalRecurringRow').toggle(this.value === 'recurring');
    });

    // Update type change (modal) - show/hide subfolder or command
    $('#modalDistributionTypeField').change(function() {
        const type = this.value;
        const isCommand = type === 'command';
        const isFileOrUpdate = type === 'file' || type === 'update';

        if (isCommand) {
            $('#modalSubfolderRow').addClass('d-none');
            $('#modalCommandRow').removeClass('d-none').show();
            $('#modalFileGroup').addClass('d-none');
        } else {
            $('#modalSubfolderRow').removeClass('d-none').show();
            $('#modalCommandRow').addClass('d-none');
            $('#modalFileGroup').removeClass('d-none').show();
        }

        // Toggle required attribute
        $('#fileInput').prop('required', isFileOrUpdate);
    });

    // Recurrence change (modal)
    $('#modalRecurrenceSelect').change(function() {
        const recurrence = this.value;
        $('#modalWeekDaysRow').toggle(recurrence === 'weekly');
        $('#modalFrequencyIntervalRow').toggle(recurrence === 'hourly' || recurrence === 'minutes');
        $('#modalHourlyTimeRow').toggle(recurrence === 'daily' || recurrence === 'weekly' || recurrence === 'monthly');
        
        const intervalLabel = $('#modalFrequencyIntervalRow label');
        const intervalSelect = document.getElementById('modalFrequencyInterval');
        intervalSelect.innerHTML = '';
        
        if (recurrence === 'hourly') {
            intervalLabel.text('Cada cuántas horas:');
            for (let i = 1; i <= 23; i++) {
                const opt = document.createElement('option');
                opt.value = i;
                opt.textContent = i;
                intervalSelect.appendChild(opt);
            }
        } else if (recurrence === 'minutes') {
            intervalLabel.text('Cada cuántos minutos:');
            const values = [1, 2, 3, 4, 5, 6, 10, 12, 15, 20, 30, 45, 60];
            values.forEach(v => {
                const opt = document.createElement('option');
                opt.value = v;
                opt.textContent = v;
                intervalSelect.appendChild(opt);
            });
        }
    });

    // Target type change (modal)
    $('#modalTargetType').change(function() {
        $('#modalGroupsSelect').toggle(this.value === 'group');
        $('#modalSpecificComputers').toggle(this.value === 'specific');
    });

    // Form submit
    $('#createDistributionForm').submit(function(e) {
        e.preventDefault();

        if (hasBlockedFiles) {
            Swal.fire({
                icon: 'error',
                title: 'Archivos bloqueados',
                text: 'Elimina los archivos bloqueados antes de crear la distribución.',
                confirmButtonText: 'Entendido'
            });
            return;
        }

        const formData = new FormData(this);
        const submitBtn = $('#submitBtn');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creando...');

        $.ajax({
            url: '{{ route("admin.distributions.store") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                const distributionId = response.distribution;
                $('#createDistributionModal').modal('hide');
                Swal.fire({
                    icon: 'success',
 title: 'Éxito',
                     text: 'Distribución creada exitosamente!'
                }).then(() => {
                    startPollingDistribution(distributionId);
                    location.reload();
                });
            },
            error: function(xhr) {
                let msg = 'Error creating distribution';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: msg
                });
                submitBtn.prop('disabled', false).html('<i class="fas fa-upload"></i> Crear y Distribuir');
            }
        });
    });
});

function deleteDistribution(id, name) {
    Swal.fire({
        title: '¿Eliminar distribución?',
        text: `¿Estás seguro de eliminar "${name}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            stopPollingDistribution(id);
            $.ajax({
                url: '/admin/distributions/' + id,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    _method: 'DELETE'
                },
                success: function() {
                    Swal.fire({
                        icon: 'success',
title: 'Eliminado',
                         text: 'Distribución eliminada exitosamente'
                    }).then(() => {
                        location.reload();
                    });
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error deleting distribution'
                    });
                }
            });
        }
    });
}

function stopDistribution(id, name) {
    Swal.fire({
        title: '¿Detener distribución?',
        text: `¿Estás seguro de detener "${name}"? Ya no se enviarán más comandos.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#6c757d',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, detener!',
         cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            stopPollingDistribution(id);
            $.ajax({
                url: '/admin/distributions/' + id + '/stop',
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function() {
                    Swal.fire({
                        icon: 'success',
title: 'Detenido',
                         text: 'Distribución detenida exitosamente'
                    }).then(() => {
                        location.reload();
                    });
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error stopping distribution'
                    });
                }
            });
        }
    });
}

function startDistribution(id, name) {
    Swal.fire({
        title: '¿Iniciar distribución?',
        text: `¿Estás seguro de iniciar "${name}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, iniciar!',
         cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            startPollingDistribution(id);
            $.ajax({
                url: '/admin/distributions/' + id + '/start',
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function() {
                    Swal.fire({
                        icon: 'success',
title: 'Iniciado',
                         text: 'Distribución iniciada exitosamente'
                    }).then(() => {
                        location.reload();
                    });
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error starting distribution'
                    });
                }
            });
        }
    });
}

function refreshDistribution(id) {
    updateDistributionProgress(id);
}

function editDistribution(id, name, type, distributionType, subfolder, description, scheduledAt, filesHtml, targetsCount, computerIdsJson, command, commandArgs) {
    $('#editId').val(id);
    $('#editName').val(name);
    $('#editType').val(type);
    $('#editDistributionType').val(distributionType || 'file');
    $('#editSubfolder').val(subfolder || '');
    $('#editDescription').val(description);
    $('#editCommand').val(command || '');
    $('#editCommandArgs').val(commandArgs || '');

    // Mostrar/ocultar campos según tipo
    const isCommand = distributionType === 'command';
    $('#editSubfolderRow').toggleClass('d-none', isCommand);
    $('#editCommandRow').toggleClass('d-none', !isCommand);
    $('#editFilesCard').toggleClass('d-none', isCommand);

    if (scheduledAt) {
        const date = new Date(scheduledAt);
        const localDate = new Date(date.getTime() - date.getTimezoneOffset() * 60000);
        $('#editScheduledAt').val(localDate.toISOString().slice(0, 16));
        $('#editScheduledAtGroup').show();
    } else {
        $('#editScheduledAt').val('');
        $('#editScheduledAtGroup').hide();
    }

    $('#editFileList').html(filesHtml || '<div class="text-muted">No hay archivos cargados</div>');

    // Preseleccionar computadoras en el multiselect
    let computerIds = [];
    try {
        computerIds = JSON.parse(computerIdsJson || '[]');
    } catch(e) {}
    $('#editComputerIds option').prop('selected', false);
    computerIds.forEach(function(cid) {
        $('#editComputerIds option[value="' + cid + '"]').prop('selected', true);
    });

    if (targetsCount > 0) {
        const totalComputers = $('#editComputerIds option').length;
        if (targetsCount >= totalComputers) {
            $('#editTargetType').val('all');
            $('#editModalSpecificComputers').addClass('d-none');
            $('#editModalGroupsSelect').addClass('d-none');
        } else {
            $('#editTargetType').val('specific');
            $('#editModalSpecificComputers').removeClass('d-none');
            $('#editModalGroupsSelect').addClass('d-none');
        }
    }

    $('#editType').off('change').on('change', function() {
        if (this.value === 'scheduled') {
            $('#editScheduledAtGroup').show();
        } else {
            $('#editScheduledAtGroup').hide();
        }
    });

    $('#editTargetType').off('change').on('change', function() {
        $('#editModalGroupsSelect').toggleClass('d-none', this.value !== 'group');
        $('#editModalSpecificComputers').toggleClass('d-none', this.value !== 'specific');
    });

    $('#editDistributionType').off('change').on('change', function() {
        const isCmd = this.value === 'command';
        $('#editSubfolderRow').toggleClass('d-none', isCmd);
        $('#editCommandRow').toggleClass('d-none', !isCmd);
        $('#editFilesCard').toggleClass('d-none', isCmd);
    });

    $('#editDistributionModal').modal('show');
}

function restartDistribution() {
    const id = $('#editId').val();
    if (!id) return;

    Swal.fire({
        title: '¿Reutilizar distribución?',
        text: 'Esto reiniciará la distribución desde 0, borrando el progreso anterior.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, reiniciar!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = $('#editDistributionForm').serializeArray().filter(function(field) {
                return field.name !== '_method';
            });
            formData.push({ name: '_token', value: $('meta[name="csrf-token"]').attr('content') });
            $.ajax({
                url: '/admin/distributions/' + id + '/restart',
                type: 'POST',
                data: $.param(formData),
                success: function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Reiniciada',
                        text: 'Distribución reiniciada correctamente'
                    }).then(() => {
                        location.reload();
                    });
                },
                error: function(xhr) {
                    let msg = 'Error al reiniciar distribución';
                    if (xhr.responseJSON) {
                        msg = xhr.responseJSON.error || xhr.responseJSON.message || msg;
                    } else if (xhr.status === 422) {
                        msg = 'Error de validación';
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: msg
                    });
                }
            });
        }
    });
}

$('#editDistributionForm').submit(function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const id = $('#editId').val();
    const submitBtn = $('#editSubmitBtn');
    submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

    $.ajax({
        url: '/admin/distributions/' + id,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            Swal.fire({
                icon: 'success',
title: 'Éxito',
                 text: 'Distribución actualizada exitosamente!'
            }).then(() => {
                $('#editDistributionModal').modal('hide');
                location.reload();
            });
        },
        error: function(xhr) {
            let msg = 'Error updating distribution';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            }
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: msg
            });
            submitBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Guardar Cambios');
        }
    });
});
</script>
@stop
