@extends('adminlte::page')

@section('title', 'CareAgent Resurtido - Versiones')

@section('content_header')
    <h1>CareAgent Resurtido - Versiones</h1>
@stop

@section('content')
    <div class="card mt-3">
        <div class="card-header">
            <a href="{{ route('admin.resurtido-agent-versions.create') }}" class="btn btn-primary">Crear Versión</a>
            @if(isset($computersWithoutUpdate) && $computersWithoutUpdate > 0)
                <span class="badge badge-warning ml-2">{{ $computersWithoutUpdate }} computadoras necesitan actualización</span>
            @endif
        </div>
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
<th>Versión</th>
                         <th>Canal</th>
                         <th>Archivo</th>
                         <th>Checksum</th>
                         <th>Activo</th>
                         <th>Creado</th>
                         <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($versions as $version)
                        <tr>
                            <td><strong>{{ $version->version }}</strong></td>
                            <td>
                                @if($version->channel === 'stable')
                                    <span class="badge badge-success">Estable</span>
                                @elseif($version->channel === 'beta')
                                    <span class="badge badge-warning">Beta</span>
                                @else
                                    <span class="badge badge-secondary">Alfa</span>
                                @endif
                            </td>
                            <td>
                                @php $files = $version->files; @endphp
                                @if(!empty($files))
                                    <small class="text-muted">{{ count($files) }} archivo(s):</small>
                                    <ul class="mb-0 pl-3">
                                        @foreach($files as $file)
                                            <li class="text-small">{{ $file['name'] ?? 'N/A' }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <span class="text-muted">Sin archivos</span>
                                @endif
                            </td>
                            <td><small class="text-muted">{{ $version->checksum ? substr($version->checksum, 0, 16) . '...' : 'N/A' }}</small></td>
                            <td>
                                @if($version->is_active)
                                    <span class="badge badge-success">Activo</span>
                                @else
                                    <span class="badge badge-secondary">Inactivo</span>
                                @endif
                            </td>
                            <td>{{ $version->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                @if($version->is_active)
                                    <button type="button" class="btn btn-info btn-sm" onclick="showDeployModal({{ $version->id }})">Deploy</button>
                                @endif
                                <form action="{{ route('admin.resurtido-agent-versions.destroy', $version) }}" method="POST" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Desactivar esta versión?')">Desactivar</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $versions->links() }}
        </div>
    </div>

    <!-- Deploy Modal -->
    <div class="modal fade" id="deployModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="deployForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Desplegar Actualización</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p>Seleccione las computadoras para desplegar esta actualización:</p>
                        <div class="form-group">
                            <label>Filtrar por Plaza</label>
                            <select id="plazaFilter" class="form-control">
                                <option value="">-- Todas las Plazas --</option>
                                @foreach($plazas as $plaza)
                                    <option value="{{ $plaza }}">{{ $plaza }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mt-2">
                            <label>Computadoras</label>
                            <select name="computer_ids[]" id="computerSelect" class="form-control" multiple size="10">
                                @foreach(\App\Models\Computer::whereNull('resurtido_agent_version')->orWhere('resurtido_agent_version', '')->get() as $computer)
                                    <option value="{{ $computer->id }}" data-plaza="{{ $computer->plaza }}">{{ $computer->computer_name }} ({{ $computer->short_key }}) - {{ $computer->plaza }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                         <button type="submit" class="btn btn-primary">Desplegar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showDeployModal(versionId) {
            document.getElementById('deployForm').action = '/admin/resurtido-agent-versions/' + versionId + '/deploy';
            document.getElementById('plazaFilter').value = '';
            filterComputers();
            $('#deployModal').modal('show');
        }

        function filterComputers() {
            const plaza = document.getElementById('plazaFilter').value;
            const options = document.getElementById('computerSelect').options;

            for (let i = 0; i < options.length; i++) {
                const option = options[i];
                if (!plaza || option.dataset.plaza === plaza) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                    option.selected = false;
                }
            }
        }

        document.getElementById('plazaFilter').addEventListener('change', filterComputers);
    </script>
@stop