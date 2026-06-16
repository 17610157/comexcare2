@extends('adminlte::page')

@section('title', 'Crear Versión de Agente')

@section('content_header')
    <h1>Crear Versión de Agente</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.agent-versions.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Versión</label>
                            <input type="text" name="version" class="form-control" required placeholder="e.g. 1.0.0">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Canal</label>
                            <select name="channel" class="form-control" required>
                                <option value="stable">Stable</option>
                                <option value="beta">Beta</option>
                                <option value="alpha">Alfa</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> <strong>Archivos requeridos:</strong> DistributionAgent.exe, Topshelf.dll, Newtonsoft.Json.dll
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>DistributionAgent.exe *</label>
                            <input type="file" name="files[]" class="form-control" required accept=".exe">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Topshelf.dll *</label>
                            <input type="file" name="files[]" class="form-control" required accept=".dll">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Newtonsoft.Json.dll *</label>
                            <input type="file" name="files[]" class="form-control" required accept=".dll">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Registro de cambios</label>
                    <textarea name="changelog" class="form-control" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Crear</button>
            </form>
        </div>
    </div>
@stop