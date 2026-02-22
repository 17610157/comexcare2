@extends('adminlte::page')

@section('title', 'Create Distribution')

@section('content_header')
    <h1>Create Distribution</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.distributions.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Type</label>
                    <select name="type" class="form-control" required>
                        <option value="immediate">Immediate</option>
                        <option value="scheduled">Scheduled</option>
                        <option value="recurring">Recurring</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control"></textarea>
                </div>
                <div class="form-group">
                    <label>Files</label>
                    <input type="file" name="files[]" class="form-control" multiple>
                </div>
                <div class="form-group">
                    <label>Target Type</label>
                    <select name="target_type" class="form-control" required>
                        <option value="all">All Computers</option>
                        <option value="group">Group</option>
                        <option value="specific">Specific</option>
                    </select>
                </div>
                <div class="form-group" id="group-select" style="display: none;">
                    <label>Group</label>
                    <select name="group_id" class="form-control">
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}">{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" id="scheduled-at" style="display: none;">
                    <label>Scheduled At</label>
                    <input type="datetime-local" name="scheduled_at" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary">Create</button>
            </form>
        </div>
    </div>

    <script>
        document.querySelector('[name=target_type]').addEventListener('change', function() {
            document.getElementById('group-select').style.display = this.value === 'group' ? 'block' : 'none';
        });
        document.querySelector('[name=type]').addEventListener('change', function() {
            document.getElementById('scheduled-at').style.display = this.value === 'scheduled' ? 'block' : 'none';
        });
    </script>
@stop