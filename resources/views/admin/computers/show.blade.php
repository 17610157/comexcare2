@extends('adminlte::page')

@section('title', 'Computer Details')

@section('content_header')
    <h1>{{ $computer->computer_name }}</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <p><strong>Name:</strong> {{ $computer->computer_name }}</p>
            <p><strong>MAC:</strong> {{ $computer->mac_address }}</p>
            <p><strong>IP:</strong> {{ $computer->ip_address }}</p>
            <p><strong>Status:</strong> {{ $computer->status }}</p>
            <p><strong>Agent Version:</strong> {{ $computer->agent_version }}</p>
            <p><strong>Group:</strong> {{ $computer->group->name ?? 'N/A' }}</p>
            <p><strong>Last Seen:</strong> {{ $computer->last_seen ? $computer->last_seen->diffForHumans() : 'Never' }}</p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">System Info</h3>
        </div>
        <div class="card-body">
            @if($computer->system_info)
                <pre>{{ json_encode($computer->system_info, JSON_PRETTY_PRINT) }}</pre>
            @else
                No system info available.
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent Commands</h3>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Sent At</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($computer->commands->take(10) as $command)
                        <tr>
                            <td>{{ $command->type }}</td>
                            <td>{{ $command->status }}</td>
                            <td>{{ $command->sent_at ? $command->sent_at->diffForHumans() : 'Not sent' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop