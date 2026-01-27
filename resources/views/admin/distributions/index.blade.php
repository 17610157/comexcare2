@extends('adminlte::page')

@section('title', 'Distributions')

@section('content_header')
    <h1>Distributions</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#createDistributionModal">Create Distribution</button>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($distributions as $distribution)
                        <tr>
                            <td>{{ $distribution->id }}</td>
                            <td>{{ $distribution->name }}</td>
                            <td>{{ $distribution->type }}</td>
                            <td>{{ $distribution->status }}</td>
                            <td>{{ $distribution->creator->name ?? 'N/A' }}</td>
                            <td>
                                <a href="{{ route('admin.distributions.show', $distribution) }}" class="btn btn-info btn-sm">View</a>
                                <button class="btn btn-danger btn-sm" onclick="deleteDistribution({{ $distribution->id }}, {{ json_encode($distribution->name) }})">Delete</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Modal -->
    <div class="modal fade" id="createDistributionModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form action="{{ route('admin.distributions.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Create Distribution</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
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
                                @foreach($groups ?? [] as $group)
                                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group" id="scheduled-at" style="display: none;">
                            <label>Scheduled At</label>
                            <input type="datetime-local" name="scheduled_at" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create</button>
                    </div>
                </form>
            </div>
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

@section('js')
<script>
function deleteDistribution(id, name) {
    Swal.fire({
        title: 'Are you sure?',
        text: `Delete distribution "${name}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ url("admin/distributions") }}/' + id;
            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = '{{ csrf_token() }}';
            form.appendChild(csrf);
            const method = document.createElement('input');
            method.type = 'hidden';
            method.name = '_method';
            method.value = 'DELETE';
            form.appendChild(method);
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>
@stop