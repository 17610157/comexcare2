@extends('adminlte::page')

@section('title', 'Editar Asignación - ' . $user->name)

@section('content_header')
    <h1>Editar Asignación de {{ $user->name }}</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('user-plaza-tienda.update', $user->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label>Plazas</label>
                    <select name="plazas[]" class="form-control" multiple id="plazasSelect">
                        @foreach($plazas as $plaza)
                            <option value="{{ $plaza }}" 
                                {{ in_array($plaza, $user->plazaTiendas->pluck('plaza')->toArray()) ? 'selected' : '' }}>
                                {{ $plaza }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Mantenga Ctrl (Windows) o Cmd (Mac) presionado para seleccionar múltiples</small>
                </div>

                <div class="form-group">
                    <label>Tiendas</label>
                    <select name="tiendas[]" class="form-control" multiple id="tiendasSelect">
                        @foreach($tiendas as $tienda)
                            <option value="{{ $tienda }}"
                                {{ in_array($tienda, $user->plazaTiendas->pluck('tienda')->toArray()) ? 'selected' : '' }}>
                                {{ $tienda }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Mantenga Ctrl (Windows) o Cmd (Mac) presionado para seleccionar múltiples</small>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Asignaciones
                    </button>
                    <a href="{{ route('user-plaza-tienda.index') }}" class="btn btn-secondary">
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
    $('#plazasSelect, #tiendasSelect').select2({
        multiple: true,
        allowClear: true
    });
});
</script>
@stop
