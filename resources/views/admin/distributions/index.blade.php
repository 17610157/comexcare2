@extends('adminlte::page')

@section('title', 'Distributions')

@section('content_header')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <h1>Distributions</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#createDistributionModal">
                        <i class="fas fa-plus"></i> Create Distribution
                    </button>
                </div>
                <div class="card-body">
                    <table id="distributionsTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Files</th>
                                <th>Targets</th>
                                <th>Progress</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($distributions as $distribution)
                                <tr>
                                    <td>{{ $distribution->id }}</td>
                                    <td>{{ $distribution->name }}</td>
                                    <td>
                                        @if($distribution->type === 'immediate')
                                            <span class="badge badge-primary">Immediate</span>
                                        @elseif($distribution->type === 'scheduled')
                                            <span class="badge badge-warning">Scheduled</span>
                                        @else
                                            <span class="badge badge-info">Recurring</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($distribution->status === 'completed')
                                            <span class="badge badge-success">Completed</span>
                                        @elseif($distribution->status === 'in_progress')
                                            <span class="badge badge-primary">In Progress</span>
                                        @elseif($distribution->status === 'pending')
                                            <span class="badge badge-warning">Pending</span>
                                        @elseif($distribution->status === 'failed')
                                            <span class="badge badge-danger">Failed</span>
                                        @else
                                            <span class="badge badge-secondary">{{ $distribution->status }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $distribution->files->count() }}</td>
                                    <td>{{ $distribution->targets->count() }}</td>
                                    <td>
                                        @php
                                            $completed = $distribution->targets->where('status', 'completed')->count();
                                            $total = $distribution->targets->count();
                                            $percent = $total > 0 ? round(($completed / $total) * 100) : 0;
                                        @endphp
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar" role="progressbar" style="width: {{ $percent }}%;" 
                                                 aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100">
                                                {{ $percent }}%
                                            </div>
                                        </div>
                                        <small>{{ $completed }}/{{ $total }} completed</small>
                                    </td>
                                    <td>{{ $distribution->created_at->diffForHumans() }}</td>
                                    <td>
                                        <button type="button" class="btn btn-info btn-sm" data-toggle="modal" 
                                                data-target="#viewDistributionModal{{ $distribution->id }}">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-warning btn-sm" 
                                                onclick="editDistribution({{ $distribution->id }}, '{{ $distribution->name }}', '{{ $distribution->type }}', '{{ $distribution->description ?? '' }}', '{{ $distribution->scheduled_at ?? '' }}')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm" 
                                                onclick="deleteDistribution({{ $distribution->id }}, '{{ $distribution->name }}')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Modal -->
    <div class="modal fade" id="createDistributionModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <form id="createDistributionForm" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header bg-primary">
                        <h5 class="modal-title">Create Distribution</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Name *</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Type *</label>
                            <select name="type" class="form-control" id="distributionType" required>
                                <option value="immediate">Immediate</option>
                                <option value="scheduled">Scheduled</option>
                                <option value="recurring">Recurring</option>
                            </select>
                        </div>
                        <div class="form-group" id="scheduledAtGroup" style="display: none;">
                            <label>Scheduled At</label>
                            <input type="datetime-local" name="scheduled_at" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Files *</label>
                            <input type="file" name="files[]" class="form-control" multiple required id="fileInput">
                            <small class="text-muted">Select multiple files to distribute</small>
                            <div id="fileList" class="mt-2"></div>
                        </div>
                        <div class="form-group">
                            <label>Target Type *</label>
                            <select name="target_type" class="form-control" id="targetType" required>
                                <option value="all">All Computers</option>
                                <option value="group">Group</option>
                                <option value="specific">Specific</option>
                            </select>
                        </div>
                        <div class="form-group" id="groupSelect" style="display: none;">
                            <label>Group</label>
                            <select name="group_id" class="form-control">
                                @foreach($groups ?? [] as $group)
                                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group" id="specificComputers" style="display: none;">
                            <label>Computers</label>
                            <select name="computer_ids[]" class="form-control" multiple>
                                @foreach($computers ?? [] as $computer)
                                    <option value="{{ $computer->id }}">{{ $computer->computer_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-upload"></i> Create & Distribute
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Detail Modals -->
    @foreach($distributions as $distribution)
    <div class="modal fade" id="viewDistributionModal{{ $distribution->id }}" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info">
                    <h5 class="modal-title">Distribution: {{ $distribution->name }}</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Details</h6>
                            <table class="table table-sm">
                                <tr><th>ID:</th><td>{{ $distribution->id }}</td></tr>
                                <tr><th>Name:</th><td>{{ $distribution->name }}</td></tr>
                                <tr><th>Type:</th><td>{{ $distribution->type }}</td></tr>
                                <tr><th>Status:</th><td>{{ $distribution->status }}</td></tr>
                                <tr><th>Created:</th><td>{{ $distribution->created_at }}</td></tr>
                                <tr><th>Description:</th><td>{{ $distribution->description ?? 'N/A' }}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Files</h6>
                            <ul class="list-group">
                                @foreach($distribution->files as $file)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        {{ $file->file_name }}
                                        <span class="badge badge-primary badge-pill">{{ number_format($file->file_size / 1024, 2) }} KB</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <h6>Targets Progress</h6>
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>Computer</th>
                                        <th>Status</th>
                                        <th>Progress</th>
                                        <th>Attempts</th>
                                        <th>Last Update</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($distribution->targets as $target)
                                        <tr>
                                            <td>{{ $target->computer->computer_name ?? 'Unknown' }}</td>
                                            <td>
                                                @if($target->status === 'completed')
                                                    <span class="badge badge-success">Completed</span>
                                                @elseif($target->status === 'in_progress')
                                                    <span class="badge badge-primary">In Progress</span>
                                                @elseif($target->status === 'failed')
                                                    <span class="badge badge-danger">Failed</span>
                                                @else
                                                    <span class="badge badge-warning">{{ $target->status }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 15px; width: 100px;">
                                                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $target->progress ?? 0 }}%;">
                                                    </div>
                                                </div>
                                                {{ $target->progress ?? 0 }}%
                                            </td>
                                            <td>{{ $target->attempts ?? 0 }}</td>
                                            <td>{{ $target->updated_at ? $target->updated_at->diffForHumans() : 'N/A' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="refreshDistribution({{ $distribution->id }})">
                        <i class="fas fa-sync"></i> Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endforeach

    <!-- Edit Modal -->
    <div class="modal fade" id="editDistributionModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form id="editDistributionForm">
                    @csrf
                    @method('PUT')
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title">Edit Distribution</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="editId">
                        <div class="form-group">
                            <label>Name *</label>
                            <input type="text" name="name" id="editName" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Type *</label>
                            <select name="type" id="editType" class="form-control" required>
                                <option value="immediate">Immediate</option>
                                <option value="scheduled">Scheduled</option>
                                <option value="recurring">Recurring</option>
                            </select>
                        </div>
                        <div class="form-group" id="editScheduledAtGroup" style="display: none;">
                            <label>Scheduled At</label>
                            <input type="datetime-local" name="scheduled_at" id="editScheduledAt" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" id="editDescription" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning" id="editSubmitBtn">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>
<script>
$(document).ready(function() {
    $('#distributionsTable').DataTable({
        "order": [[0, "desc"]],
        "language": {
            "decimal": "",
            "emptyTable": "No hay datos disponibles",
            "info": "Mostrando _START_ a _END_ de _TOTAL_ entradas",
            "infoEmpty": "Mostrando 0 a 0 de 0 entradas",
            "infoFiltered": "(filtrado de _MAX_ entradas totales)",
            "infoPostFix": "",
            "thousands": ",",
            "lengthMenu": "Mostrar _MENU_ entradas",
            "loadingRecords": "Cargando...",
            "processing": "Procesando...",
            "search": "Buscar:",
            "zeroRecords": "No se encontraron registros coincidentes",
            "paginate": {
                "first": "Primero",
                "last": "Último",
                "next": "Siguiente",
                "previous": "Anterior"
            },
            "aria": {
                "sortAscending": ": activar para ordenar la columna ascendente",
                "sortDescending": ": activar para ordenar la columna descendente"
            }
        }
    });

    // File input change
    $('#fileInput').change(function() {
        const files = this.files;
        let html = '<ul class="list-group" style="max-height: 150px; overflow-y: auto;">';
        for (let i = 0; i < files.length; i++) {
            html += '<li class="list-group-item py-1">' + files[i].name + ' (' + (files[i].size / 1024).toFixed(2) + ' KB)</li>';
        }
        html += '</ul>';
        $('#fileList').html(html);
    });

    // Distribution type change
    $('#distributionType').change(function() {
        $('#scheduledAtGroup').toggle(this.value === 'scheduled');
    });

    // Target type change
    $('#targetType').change(function() {
        $('#groupSelect').toggle(this.value === 'group');
        $('#specificComputers').toggle(this.value === 'specific');
    });

    // Form submit
    $('#createDistributionForm').submit(function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = $('#submitBtn');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creating...');

        $.ajax({
            url: '{{ route("admin.distributions.store") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: 'Distribution created successfully!'
                }).then(() => {
                    location.reload();
                });
            },
            error: function(xhr) {
                let msg = 'Error creating distribution';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: msg
                });
                submitBtn.prop('disabled', false).html('<i class="fas fa-upload"></i> Create & Distribute');
            }
        });
    });
});

function deleteDistribution(id, name) {
    Swal.fire({
        title: '¿Eliminar distribución?',
        text: `¿Estás seguro de eliminar "${name}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '/admin/distributions/' + id,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    _method: 'DELETE'
                },
                success: function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted',
                        text: 'Distribution deleted successfully'
                    }).then(() => {
                        location.reload();
                    });
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error deleting distribution'
                    });
                }
            });
        }
    });
}

function refreshDistribution(id) {
    location.reload();
}

function editDistribution(id, name, type, description, scheduledAt) {
    $('#editId').val(id);
    $('#editName').val(name);
    $('#editType').val(type);
    $('#editDescription').val(description);
    
    if (scheduledAt) {
        // Convert to datetime-local format
        const date = new Date(scheduledAt);
        const localDate = new Date(date.getTime() - date.getTimezoneOffset() * 60000);
        $('#editScheduledAt').val(localDate.toISOString().slice(0, 16));
        $('#editScheduledAtGroup').show();
    } else {
        $('#editScheduledAt').val('');
        $('#editScheduledAtGroup').hide();
    }
    
    $('#editType').change(function() {
        $('#editScheduledAtGroup').toggle(this.value === 'scheduled');
    });
    
    $('#editDistributionModal').modal('show');
}

$('#editDistributionForm').submit(function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const id = $('#editId').val();
    const submitBtn = $('#editSubmitBtn');
    submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

    $.ajax({
        url: '/admin/distributions/' + id,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: 'Distribution updated successfully!'
            }).then(() => {
                $('#editDistributionModal').modal('hide');
                location.reload();
            });
        },
        error: function(xhr) {
            let msg = 'Error updating distribution';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            }
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: msg
            });
            submitBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Save Changes');
        }
    });
});
</script>
@stop
