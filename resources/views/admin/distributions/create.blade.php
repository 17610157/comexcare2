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

                <!-- Programación -->
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

                <!-- Tipo de distribución -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Tipo de Distribución *</label>
                            <select name="distribution_type" class="form-control" id="distributionType">
                                <option value="file">Archivo Normal</option>
                                <option value="update">Actualización (Subcarpeta)</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6" id="subfolderRow" style="display:none;">
                        <div class="form-group">
                            <label>Subcarpeta de Destino *</label>
                            <input type="text" name="subfolder" class="form-control" placeholder="ej: actualizaciones/2026">
                            <small class="text-muted">La subcarpeta debe existir en la ruta principal del equipo</small>
                        </div>
                    </div>
                </div>

                <!-- Archivos -->
                <div class="card card-info mt-3">
                    <div class="card-header">
                        <h3 class="card-title">Archivos a Distribuir</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Seleccionar Archivos *</label>
                            <input type="file" name="files[]" class="form-control" multiple required>
                            <small class="text-muted">Seleccione uno o múltiples archivos</small>
                        </div>
                    </div>
                </div>

                <!-- Destino -->
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Tipo de Destino *</label>
                            <select name="target_type" class="form-control" id="targetType" required>
                                <option value="all">Todas las Computadoras</option>
                                <option value="group">Grupos Específicos</option>
                                <option value="specific">Computadoras Específicas</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group" id="groupsSelect" style="display:none;">
                            <label>Grupos</label>
                            <select name="group_ids[]" class="form-control" multiple style="height: 150px;">
                                @foreach($groups as $group)
                                    <option value="{{ $group->id }}">{{ $group->name }} ({{ $group->type ?? 'Sin tipo' }})</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Mantenga Ctrl/Cmd presionado para seleccionar varios</small>
                        </div>
                        <div class="form-group" id="specificComputers" style="display:none;">
                            <label>Computadoras</label>
                            <select name="computer_ids[]" class="form-control" multiple style="height: 150px;">
                                @foreach($computers as $computer)
                                    <option value="{{ $computer->id }}">{{ $computer->computer_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary mt-3">
                    <i class="fas fa-download"></i> Crear Distribución
                </button>
                <a href="{{ route('admin.distributions.index') }}" class="btn btn-secondary mt-3">Cancelar</a>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var typeSelect = document.getElementById('typeSelect');
            if (typeSelect) {
                typeSelect.addEventListener('change', function() {
                    var scheduledRow = document.getElementById('scheduledRow');
                    var recurringRow = document.getElementById('recurringRow');
                    if (scheduledRow) scheduledRow.style.display = this.value === 'scheduled' ? 'flex' : 'none';
                    if (recurringRow) recurringRow.style.display = this.value === 'recurring' ? 'flex' : 'none';
                });
            }

            var recurrenceSelect = document.getElementById('recurrenceSelect');
            if (recurrenceSelect) {
                recurrenceSelect.addEventListener('change', function() {
                    var recurrence = this.value;
                    var weekDaysRow = document.getElementById('weekDaysRow');
                    var frequencyIntervalRow = document.getElementById('frequencyIntervalRow');
                    var hourlyTimeRow = document.getElementById('hourlyTimeRow');
                    
                    if (weekDaysRow) weekDaysRow.style.display = recurrence === 'weekly' ? 'block' : 'none';
                    if (frequencyIntervalRow) frequencyIntervalRow.style.display = (recurrence === 'hourly' || recurrence === 'minutes') ? 'block' : 'none';
                    if (hourlyTimeRow) hourlyTimeRow.style.display = (recurrence === 'daily' || recurrence === 'weekly' || recurrence === 'monthly') ? 'block' : 'none';
                    
                    var intervalLabel = document.querySelector('#frequencyIntervalRow label');
                    var intervalSelect = document.getElementById('frequencyInterval');
                    if (intervalSelect) {
                        intervalSelect.innerHTML = '';
                        
                        if (recurrence === 'hourly' && intervalLabel) {
                            intervalLabel.textContent = 'Cada cuántas horas:';
                            for (var i = 1; i <= 23; i++) {
                                var opt = document.createElement('option');
                                opt.value = i;
                                opt.textContent = i;
                                intervalSelect.appendChild(opt);
                            }
                        } else if (recurrence === 'minutes' && intervalLabel) {
                            intervalLabel.textContent = 'Cada cuántos minutos:';
                            var values = [1, 2, 3, 4, 5, 6, 10, 12, 15, 20, 30, 45, 60];
                            values.forEach(function(v) {
                                var opt = document.createElement('option');
                                opt.value = v;
                                opt.textContent = v;
                                intervalSelect.appendChild(opt);
                            });
                        }
                    }
                });
            }

            var targetType = document.getElementById('targetType');
            if (targetType) {
                targetType.addEventListener('change', function() {
                    var groupsSelect = document.getElementById('groupsSelect');
                    var specificComputers = document.getElementById('specificComputers');
                    if (groupsSelect) groupsSelect.style.display = this.value === 'group' ? 'block' : 'none';
                    if (specificComputers) specificComputers.style.display = this.value === 'specific' ? 'block' : 'none';
                });
            }

            var distributionType = document.getElementById('distributionType');
            if (distributionType) {
                distributionType.addEventListener('change', function() {
                    var subfolderRow = document.getElementById('subfolderRow');
                    if (subfolderRow) subfolderRow.style.display = this.value === 'update' ? 'block' : 'none';
                });
            }
        });
    </script>
@stop
