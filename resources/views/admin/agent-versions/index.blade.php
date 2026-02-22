@extends('adminlte::page')

@section('title', 'Agent Versions')

@section('content_header')
    <h1>Agent Versions</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <a href="{{ route('admin.agent-versions.create') }}" class="btn btn-primary">Create Version</a>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Version</th>
                        <th>Channel</th>
                        <th>Active</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($versions as $version)
                        <tr>
                            <td>{{ $version->version }}</td>
                            <td>{{ $version->channel }}</td>
                            <td>{{ $version->is_active ? 'Yes' : 'No' }}</td>
                            <td>{{ $version->created_at->format('Y-m-d') }}</td>
                            <td>
                                <form action="{{ route('admin.agent-versions.destroy', $version) }}" method="POST" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Deactivate</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop