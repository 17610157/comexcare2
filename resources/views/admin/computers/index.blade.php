@extends('adminlte::page')

@section('title', 'Computers')

@section('content_header')
    <h1>Computers</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>MAC Address</th>
                        <th>IP</th>
                        <th>Status</th>
                        <th>Group</th>
                        <th>Download Path</th>
                        <th>Last Seen</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($computers as $computer)
                        <tr>
                            <td>{{ $computer->computer_name }}</td>
                            <td>{{ $computer->mac_address }}</td>
                            <td>{{ $computer->ip_address }}</td>
                            <td>{{ $computer->status }}</td>
                            <td>{{ $computer->group->name ?? 'N/A' }}</td>
                            <td><small>{{ $computer->download_path ?? 'C:\ProgramData\DistributionAgent\files' }}</small></td>
                            <td>{{ $computer->last_seen ? $computer->last_seen->diffForHumans() : 'Never' }}</td>
                            <td>
                                <a href="{{ route('admin.computers.show', $computer) }}" class="btn btn-info btn-sm">View</a>
                                @can('distribution.editar')
                                <a href="{{ route('admin.computers.edit', $computer) }}" class="btn btn-warning btn-sm">Edit</a>
                                <form action="{{ route('admin.computers.destroy', $computer) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar este agente?')">Eliminar</button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop