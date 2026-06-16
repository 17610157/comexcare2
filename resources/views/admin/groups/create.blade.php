@extends('adminlte::page')

@section('title', 'Crear Grupo')

@section('content_header')
    <h1>Crear Grupo</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.groups.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label>Nombre</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Descripción</label>
                    <textarea name="description" class="form-control"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Crear</button>
            </form>
        </div>
    </div>
@stop