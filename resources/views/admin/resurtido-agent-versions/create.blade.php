@extends('adminlte::page')

@section('title', 'Crear Versión de CareAgent Resurtido')

@section('content_header')
    <h1>Crear Versión de CareAgent Resurtido</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.resurtido-agent-versions.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Version</label>
                            <input type="text" name="version" class="form-control" required placeholder="e.g. 1.0.0">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Canal</label>
                            <select name="channel" class="form-control" required>
                                <option value="stable">Estable</option>
                                <option value="beta">Beta</option>
                                <option value="alpha">Alpha</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> <strong>Archivo requerido:</strong> CareAgentResurtido.exe<br>
                    <small>Ruta de instalación: C:\Program Files\CareAgentResurtido</small>
                </div>
                
                <div class="form-group">
                    <label>CareAgentResurtido.exe *</label>
                    <input type="file" name="file" class="form-control" required accept=".exe">
                </div>
                
                <div class="form-group">
                    <label>Changelog</label>
                    <textarea name="changelog" class="form-control" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Crear</button>
            </form>
        </div>
    </div>
@stop