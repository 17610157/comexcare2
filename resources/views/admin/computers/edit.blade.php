@extends('adminlte::page')

@section('title', 'Edit Computer')

@section('content_header')
    <h1>Edit {{ $computer->computer_name }}</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.computers.update', $computer) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label>Computer Name</label>
                    <input type="text" name="computer_name" class="form-control" value="{{ $computer->computer_name }}">
                </div>
                <div class="form-group">
                    <label>Group</label>
                    <select name="group_id" class="form-control">
                        <option value="">None</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" {{ $computer->group_id == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Download Path</label>
                    <input type="text" name="download_path" class="form-control" value="{{ $computer->download_path ?? 'C:\ProgramData\DistributionAgent\files' }}">
                </div>
                <div class="card card-secondary">
                    <div class="card-header">
                        <h3 class="card-title">Additional Download Paths (10)</h3>
                    </div>
                    <div class="card-body">
                        @for($i = 1; $i <= 10; $i++)
                        <div class="form-group">
                            <label>Path {{ $i }}</label>
                            <input type="text" name="download_path_{{ $i }}" class="form-control" 
                                   value="{{ $computer->{'download_path_'.$i} ?? '' }}" 
                                   placeholder="C:\ProgramData\DistributionAgent\files{{ $i }}">
                        </div>
                        @endfor
                    </div>
                </div>
                <div class="form-group">
                    <label>Agent Config (JSON)</label>
                    <textarea name="agent_config" class="form-control" rows="5">{{ json_encode($computer->agent_config) }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary">Update</button>
            </form>
        </div>
    </div>
@stop