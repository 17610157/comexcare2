@extends('adminlte::page')

@section('title', 'Nueva Recepción de Archivos')

@section('content_header')
    <h1>Nueva Recepción de Archivos</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.file-receptions.store') }}" method="POST" id="receptionForm">
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
                </div>

                <div class="row" id="recurringRow" style="display:none;">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Recurrencia</label>
                            <select name="recurrence" class="form-control">
                                <option value="daily">Diario</option>
                                <option value="weekly">Semanal</option>
                                <option value="monthly">Mensual</option>
                            </select>
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
                            <label>Tipo de Destino *</label>
                            <select name="target_type" class="form-control" id="targetType" required>
                                <option value="all">Todas las Computadoras</option>
                                <option value="group">Grupo Específico</option>
                                <option value="specific">Computadoras Específicas</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group" id="groupSelect" style="display:none;">
                            <label>Grupo</label>
                            <select name="group_id" class="form-control">
                                <option value="">Seleccionar...</option>
                                @foreach($groups as $group)
                                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                                @endforeach
                            </select>
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

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Crear Recepción
                </button>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('typeSelect').addEventListener('change', function() {
            document.getElementById('scheduledRow').style.display = this.value === 'scheduled' ? 'flex' : 'none';
            document.getElementById('recurringRow').style.display = this.value === 'recurring' ? 'flex' : 'none';
        });

        document.getElementById('targetType').addEventListener('change', function() {
            document.getElementById('groupSelect').style.display = this.value === 'group' ? 'block' : 'none';
            document.getElementById('specificComputers').style.display = this.value === 'specific' ? 'block' : 'none';
        });
    </script>
@stop
