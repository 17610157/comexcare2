@extends('adminlte::page')

@section('title', 'Sin accesos')

@section('content_header')
    <h1><i class="fas fa-lock text-warning"></i> Sin accesos asignados</h1>
@stop

@section('content')
    <div class="callout callout-warning" style="max-width: 640px;">
        <h5><i class="icon fas fa-exclamation-triangle"></i> Tu cuenta no tiene nada asignado todavia</h5>
        <p class="mb-2">
            Tu rol <strong>{{ auth()->user()->getRoleNames()->first() ?? 'sin rol asignado' }}</strong>
            no tiene permisos habilitados, asi que todavia no hay ninguna seccion que puedas ver.
        </p>
        <p class="mb-0 text-muted">
            Pide a un administrador que te asigne permisos desde
            <strong>Admin &rsaquo; Roles</strong>. En cuanto te asignen uno, esta pagina dejara de aparecer.
        </p>
    </div>
@stop