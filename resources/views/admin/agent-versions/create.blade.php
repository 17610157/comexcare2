@extends('adminlte::page')

@section('title', 'Create Agent Version')

@section('content_header')
    <h1>Create Agent Version</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.agent-versions.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label>Version</label>
                    <input type="text" name="version" class="form-control" required placeholder="e.g. 1.0.0">
                </div>
                <div class="form-group">
                    <label>Channel</label>
                    <select name="channel" class="form-control" required>
                        <option value="stable">Stable</option>
                        <option value="beta">Beta</option>
                        <option value="alpha">Alpha</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>File</label>
                    <input type="file" name="file" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Changelog</label>
                    <textarea name="changelog" class="form-control"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Create</button>
            </form>
        </div>
    </div>
@stop