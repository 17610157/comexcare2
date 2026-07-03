@extends('adminlte::page')

@section('title', 'Nueva Categoría')

@section('content_header')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.agent-defaults.index') }}">Archivos Predeterminados</a></li>
            <li class="breadcrumb-item active">Nueva Categoría</li>
        </ol>
    </nav>
    <h1 class="h3"><i class="fas fa-plus-circle text-primary"></i> Nueva Categoría</h1>
@stop

@section('content')
    <form action="{{ route('admin.agent-defaults.store') }}" method="POST">
        @csrf

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="fas fa-info-circle"></i> Datos Generales</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-9">
                        <label class="form-label fw-bold">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" required>
                        @error('name')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Estado</label>
                        <div class="form-check form-switch mt-2">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                                   value="1" checked style="width:40px;height:20px;">
                            <label class="form-check-label text-success fw-bold" for="is_active">Activo</label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label fw-bold">Descripción</label>
                        <textarea name="description" id="description"
                                  class="form-control @error('description') is-invalid @enderror"
                                  rows="2">{{ old('description') }}</textarea>
                        @error('description')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <a href="{{ route('admin.agent-defaults.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
                @can('agent-defaults.crear')
                    <button type="submit" class="btn btn-primary float-right">
                        <i class="fas fa-save"></i> Guardar Categoría
                    </button>
                @endcan
            </div>
        </div>
    </form>
@stop