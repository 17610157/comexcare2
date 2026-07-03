@extends('adminlte::page')

@section('title', 'Editar Categoría')

@section('content_header')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.agent-defaults.index') }}">Archivos Predeterminados</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.agent-defaults.show', $category) }}">{{ $category->name }}</a></li>
            <li class="breadcrumb-item active">Editar</li>
        </ol>
    </nav>
    <h1 class="h3"><i class="fas fa-edit text-warning"></i> Editar: {{ $category->name }}</h1>
@stop

@section('content')
    <form action="{{ route('admin.agent-defaults.update', $category) }}" method="POST">
        @csrf @method('PUT')

        <div class="card">
            <div class="card-header bg-warning text-white">
                <h5 class="card-title mb-0"><i class="fas fa-info-circle"></i> Datos Generales</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-9">
                        <label class="form-label fw-bold">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $category->name) }}" required>
                        @error('name')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Estado</label>
                        <div class="form-check form-switch mt-2">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                                   value="1" @checked($category->is_active) style="width:40px;height:20px;">
                            <label class="form-check-label fw-bold @if($category->is_active) text-success @else text-secondary @endif"
                                   for="is_active">{{ $category->is_active ? 'Activo' : 'Inactivo' }}</label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label fw-bold">Descripción</label>
                        <textarea name="description" id="description"
                                  class="form-control @error('description') is-invalid @enderror"
                                  rows="2">{{ old('description', $category->description) }}</textarea>
                        @error('description')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <a href="{{ route('admin.agent-defaults.show', $category) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Cancelar
                </a>
                @can('agent-defaults.editar')
                    <button type="submit" class="btn btn-warning float-right">
                        <i class="fas fa-save"></i> Actualizar Categoría
                    </button>
                @endcan
            </div>
        </div>
    </form>
@stop