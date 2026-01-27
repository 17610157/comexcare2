@extends('adminlte::page')

@section('title', 'Distribution Details')

@section('content_header')
    <h1>{{ $distribution->name }}</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <p><strong>Type:</strong> {{ $distribution->type }}</p>
            <p><strong>Status:</strong> {{ $distribution->status }}</p>
            <p><strong>Description:</strong> {{ $distribution->description }}</p>
            <p><strong>Created By:</strong> {{ $distribution->creator->name ?? 'N/A' }}</p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Files</h3>
        </div>
        <div class="card-body">
            <ul>
                @foreach($distribution->files as $file)
                    <li>{{ $file->file_name }} ({{ $file->file_size }} bytes)</li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Targets</h3>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Computer</th>
                        <th>Status</th>
                        <th>Progress</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($distribution->targets as $target)
                        <tr>
                            <td>{{ $target->computer->computer_name }}</td>
                            <td>{{ $target->status }}</td>
                            <td>{{ $target->progress }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop