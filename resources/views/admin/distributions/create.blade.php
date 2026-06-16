@extends('adminlte::page')

@section('title', 'Nueva Distribución de Archivos')

@section('content_header')
    <h1>Nueva Distribución de Archivos</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.distributions.store') }}" method="POST" enctype="multipart/form-data" id="distributionForm">
                @csrf
                
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
                            <select name="type" class="form-control" id="typeSelect" required>
                                <option value="immediate">Inmediato</option>
                                <option value="scheduled">Programado</option>
                                <option value="recurring">Recurrente</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row" id="scheduledRow" style="display:none;">
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

                <div class="row" id="recurringRow" style="display:none;">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Tipo de Frecuencia</label>
                            <select name="recurrence" class="form-control" id="recurrenceSelect">
                                <option value="daily">Diario</option>
                                <option value="weekly">Semanal</option>
                                <option value="monthly">Mensual</option>
                                <option value="hourly">Cada Hora</option>
                                <option value="minutes">Cada Minutos</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3" id="frequencyIntervalRow" style="display:none;">
                        <div class="form-group">
                            <label>Intervalo</label>
                            <select name="frequency_interval" class="form-control" id="frequencyInterval">
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                                <option value="6">6</option>
                                <option value="10">10</option>
                                <option value="15">15</option>
                                <option value="20">20</option>
                                <option value="30">30</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3" id="weekDaysRow" style="display:none;">
                        <div class="form-group">
                            <label>Días de la Semana</label>
                            <select name="week_days[]" class="form-control" multiple style="height: 100px;">
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
                    <div class="col-md-3" id="hourlyTimeRow">
                        <div class="form-group">
                            <label>Hora Programada</label>
                            <input type="time" name="scheduled_time" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Descripción</label>
                    <textarea name="description" class="form-control" rows="2"></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Tipo de Distribución *</label>
                            <select name="distribution_type" class="form-control" id="distributionType" onchange="toggleDistType()">
                                <option value="file" selected>Archivo Normal</option>
                                <option value="update">Actualización (Subcarpeta)</option>
                                <option value="command">Comando/Ejecutar</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6" id="subfolderField">
                        <div class="form-group">
                            <label>Subcarpeta de Destino</label>
                            <input type="text" name="subfolder" class="form-control" placeholder="ej: actualizaciones">
                        </div>
                    </div>
                </div>

                <div id="commandSection" style="display:none;">
                    <div class="form-group">
                        <label>Comando a Ejecutar</label>
                        <input type="text" name="command" class="form-control" placeholder="ej: reiniciar">
                    </div>
                    <div class="form-group">
                        <label>Argumentos (opcional)</label>
                        <input type="text" name="command_args" class="form-control" placeholder="ej: -Param1 valor1">
                    </div>
                </div>

                <div class="form-group">
                    <label>Archivos a distribuir *</label>
                    <input type="file" name="files[]" class="form-control" multiple required>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Tipo de Destino</label>
                            <select name="target_type" class="form-control" id="targetType">
                                <option value="all">Todos los Equipos</option>
                                <option value="group">Grupos Específicos</option>
                                <option value="specific">Equipos Específicos</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row" id="groupsSelect" style="display:none;">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Seleccionar Grupos</label>
                            <select name="groups[]" class="form-control" multiple style="height: 150px;">
                                @foreach(\App\Models\Group::where('active', true)->orderBy('name')->get() as $group)
                                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row" id="specificComputers" style="display:none;">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Seleccionar Equipos</label>
                            <input type="text" class="form-control mb-2" placeholder="Buscar por nombre o short key..." onkeyup="filterComputers(this, 'createComputersSelect')">
                            <select name="computers[]" id="createComputersSelect" class="form-control" multiple style="height: 150px;">
                                @foreach(\App\Models\Computer::where('active', true)->orderBy('computer_name')->get() as $computer)
                                    <option value="{{ $computer->id }}" data-search="{{ strtolower($computer->computer_name.' '.($computer->short_key ?? '')) }}">
                                        {{ $computer->computer_name }} {{ $computer->short_key ? '('.$computer->short_key.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted" id="createComputersCount">{{ \App\Models\Computer::where('active', true)->count() }} computadoras</small>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Crear Distribución</button>
                    <a href="{{ route('admin.distributions.index') }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@stop

@section('js')
<script>
function filterComputers(input, selectId) {
    var filter = input.value.toLowerCase();
    var select = document.getElementById(selectId);
    var visibleCount = 0;

    for (var i = 0; i < select.options.length; i++) {
        var option = select.options[i];
        var searchData = option.getAttribute('data-search') || option.text.toLowerCase();
        var match = searchData.includes(filter);
        option.style.display = match ? '' : 'none';
        if (match) visibleCount++;
    }

    var countId = selectId === 'createComputersSelect' ? 'createComputersCount' : 'modalComputersCount';
    var countEl = document.getElementById(countId);
    if (countEl) {
        countEl.textContent = visibleCount + ' de ' + select.options.length + ' computadoras';
    }
}

function toggleDistType() {
    var type = document.getElementById('distributionType').value;
    var subfolderField = document.getElementById('subfolderField');
    var commandSection = document.getElementById('commandSection');
    var fileInput = document.querySelector('input[name="files[]"]');
    
    if (type === 'command') {
        subfolderField.style.display = 'none';
        commandSection.style.display = 'block';
        if (fileInput) fileInput.removeAttribute('required');
    } else {
        subfolderField.style.display = 'block';
        commandSection.style.display = 'none';
        if (fileInput) fileInput.setAttribute('required', 'required');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    toggleDistType();
    
    var typeSelect = document.getElementById('typeSelect');
    if (typeSelect) {
        typeSelect.addEventListener('change', function() {
            var scheduledRow = document.getElementById('scheduledRow');
            var recurringRow = document.getElementById('recurringRow');
            scheduledRow.style.display = this.value === 'scheduled' ? 'flex' : 'none';
            recurringRow.style.display = this.value === 'recurring' ? 'flex' : 'none';
        });
    }
    
    var recurrenceSelect = document.getElementById('recurrenceSelect');
    if (recurrenceSelect) {
        recurrenceSelect.addEventListener('change', function() {
            var freqInterval = document.getElementById('frequencyIntervalRow');
            var weekDays = document.getElementById('weekDaysRow');
            var hourlyTime = document.getElementById('hourlyTimeRow');
            
            if (this.value === 'daily' || this.value === 'weekly' || this.value === 'monthly') {
                freqInterval.style.display = 'none';
                weekDays.style.display = this.value === 'weekly' ? 'block' : 'none';
                hourlyTime.style.display = 'block';
            } else {
                freqInterval.style.display = 'block';
                weekDays.style.display = 'none';
                hourlyTime.style.display = 'none';
            }
        });
    }
    
    var targetType = document.getElementById('targetType');
    if (targetType) {
        targetType.addEventListener('change', function() {
            var groupsSelect = document.getElementById('groupsSelect');
            var specificComputers = document.getElementById('specificComputers');
            groupsSelect.style.display = this.value === 'group' ? 'block' : 'none';
            specificComputers.style.display = this.value === 'specific' ? 'block' : 'none';
        });
    }
});
</script>
@stop