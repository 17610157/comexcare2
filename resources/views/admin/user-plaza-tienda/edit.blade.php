@extends('adminlte::page')

@section('title', 'Editar Asignación - ' . $user->name)

@section('content_header')
    <h1>Editar Asignación de {{ $user->name }}</h1>
    <p class="text-muted">Seleccione las plazas y tiendas que el usuario podrá ver en los reportes.</p>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.user-plaza-tienda.update', $user->id) }}" method="POST" id="asignacionForm">
                @csrf
                @method('PUT')

                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Cómo funciona:</strong>
                    <ul class="mb-0">
                        <li>Si selecciona <strong>solo plazas</strong>, el usuario verá todos los reportes de esas plazas.</li>
                        <li>Si selecciona <strong>plazas y tiendas específicas</strong>, el usuario solo verá reportes de esas tiendas.</li>
                        <li>Deje tiendas en blanco para otorgar acceso a toda la plaza.</li>
                    </ul>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-primary">
                                <h3 class="card-title">
                                    <input type="checkbox" id="selectAllPlazas"> 
                                    <label for="selectAllPlazas" class="mb-0">Seleccionar Plazas</label>
                                </h3>
                            </div>
                            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                <div class="form-group">
                                    @foreach($plazas as $plaza)
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input plaza-checkbox" 
                                               id="plaza_{{ $plaza }}" 
                                               name="plazas[]" 
                                               value="{{ $plaza }}"
                                               {{ in_array($plaza, $userPlazas) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="plaza_{{ $plaza }}">
                                            <strong>{{ $plaza }}</strong>
                                            <span class="text-muted">({{ DB::table('bi_sys_tiendas')->where('id_plaza', $plaza)->count() }} tiendas)</span>
                                        </label>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-success">
                                <h3 class="card-title">
                                    <input type="checkbox" id="selectAllTiendas"> 
                                    <label for="selectAllTiendas" class="mb-0">Seleccionar Tiendas Específicas</label>
                                </h3>
                            </div>
                            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                <div class="alert alert-warning">
                                    <small>Solo seleccione tiendas si desea limitar el acceso a tiendas específicas. Deje todo desmarcado para dar acceso a todas las tiendas de las plazas seleccionadas.</small>
                                </div>
                                <div class="form-group">
                                    @foreach($tiendasPorPlaza as $plaza => $tiendas)
                                    <div class="mt-3">
                                        <h6><strong>Plaza {{ $plaza }}</strong></h6>
                                        @foreach($tiendas as $tienda)
                                        <div class="custom-control custom-checkbox custom-checkbox-sm">
                                            <input type="checkbox" class="custom-control-input tienda-checkbox" 
                                                   id="tienda_{{ $plaza }}_{{ $tienda }}" 
                                                   name="tiendas[]" 
                                                   value="{{ $tienda }}"
                                                   {{ in_array($tienda, $userTiendas) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="tienda_{{ $plaza }}_{{ $tienda }}">
                                                {{ $tienda }}
                                            </label>
                                        </div>
                                        @endforeach
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Asignaciones
                    </button>
                    <a href="{{ route('admin.user-plaza-tienda.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
@stop

@section('js')
<script>
$(document).ready(function() {
    $('#selectAllPlazas').on('change', function() {
        $('.plaza-checkbox').prop('checked', $(this).prop('checked'));
    });

    $('#selectAllTiendas').on('change', function() {
        $('.tienda-checkbox').prop('checked', $(this).prop('checked'));
    });

    $('.plaza-checkbox').on('change', function() {
        if (!$(this).prop('checked')) {
            $('#selectAllPlazas').prop('checked', false);
        }
    });

    $('.tienda-checkbox').on('change', function() {
        if (!$(this).prop('checked')) {
            $('#selectAllTiendas').prop('checked', false);
        }
    });

    $('#asignacionForm').on('submit', function(e) {
        var plazasChecked = $('.plaza-checkbox:checked').length;
        if (plazasChecked === 0) {
            e.preventDefault();
            alert('Debe seleccionar al menos una plaza');
            return false;
        }
    });
});
</script>
@stop
