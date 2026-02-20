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
                            <td>{{ $computer->last_seen ? $computer->last_seen->diffForHumans() : 'Never' }}</td>
                            <td>
                                <a href="{{ route('admin.computers.show', $computer) }}" class="btn btn-info btn-sm">View</a>
                                @can('distribution.editar')
                                <a href="{{ route('admin.computers.edit', $computer) }}" class="btn btn-warning btn-sm">Edit</a>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop