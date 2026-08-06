@extends('adminlte::page')

@section('title', 'Versiones de Agente')

@section('content_header')
    <h1>Versiones de Agente</h1>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('error') }}
        </div>
    @endif

    <div class="card mt-3">
        <div class="card-header">
            <a href="{{ route('admin.agent-versions.create') }}" class="btn btn-primary">Crear Versión</a>
            @if(isset($computersWithoutUpdate) && $computersWithoutUpdate > 0)
                <span class="badge badge-warning ml-2">{{ $computersWithoutUpdate }} computadoras sin versión</span>
            @endif
        </div>
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Versión</th>
                        <th>Canal</th>
                        <th>Archivos</th>
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
                                    <small class="text-muted">{{ count($files) }} archivos:</small>
                                    <ul class="mb-0 pl-3">
                                        @foreach($files as $file)
                                            <li class="text-small">{{ $file['name'] ?? 'N/A' }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <span class="text-muted">Sin archivos</span>
                                @endif
                            </td>
                            <td>
                                @if($version->is_active)
                                    <span class="badge badge-success">Activo</span>
                                @else
                                    <span class="badge badge-secondary">Inactivo</span>
                                @endif
                            </td>
                            <td>{{ $version->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <button type="button" class="btn btn-info btn-sm" onclick="showDeployModal({{ $version->id }})">Deploy</button>
                                @if(!$version->is_active)
                                    <form action="{{ route('admin.agent-versions.activate', $version) }}" method="POST" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('¿Activar esta versión?')">Activar</button>
                                    </form>
                                @else
                                    <form action="{{ route('admin.agent-versions.destroy', $version) }}" method="POST" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Desactivar esta versión?')">Desactivar</button>
                                    </form>
                                @endif
                                <form action="{{ route('admin.agent-versions.force-delete', $version) }}" method="POST" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-dark btn-sm" onclick="return confirm('¿Eliminar permanentemente esta versión y sus archivos? Esta acción no se puede deshacer.')">Eliminar</button>
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
                        <div class="form-group">
                            <label><strong>Destino del deploy</strong></label>
                            <select name="deploy_target" id="deployTarget" class="form-control" required>
                                <option value="">-- Seleccionar --</option>
                                <option value="all">Todas las computadoras</option>
                                <option value="group">Por grupo</option>
                                <option value="store">Por tienda (plaza)</option>
                                <option value="computers">Equipo(s) específico(s)</option>
                            </select>
                        </div>

                        <div class="form-group" id="groupField" style="display: none;">
                            <label><strong>Grupo</strong></label>
                            <select name="group_id" id="groupSelect" class="form-control">
                                <option value="">-- Seleccionar grupo --</option>
                                @foreach($groups as $group)
                                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group" id="storeField" style="display: none;">
                            <label><strong>Tienda (Plaza)</strong></label>
                            <select name="plaza" id="plazaSelect" class="form-control">
                                <option value="">-- Seleccionar tienda --</option>
                                @foreach($plazas as $plaza)
                                    <option value="{{ $plaza }}">{{ $plaza }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group" id="computersField" style="display: none;">
                            <label><strong>Computadoras</strong></label>
                            <input type="text" id="computerSearch" class="form-control mb-2" placeholder="Buscar por nombre, clave o plaza...">
                            <select name="computer_ids[]" id="computerSelect" class="form-control" multiple size="10">
                                @foreach($computers as $computer)
                                    <option value="{{ $computer->id }}" data-search="{{ strtolower($computer->nombre_instalacion . ' ' . $computer->short_key . ' ' . $computer->plaza) }}">
                                        {{ $computer->nombre_instalacion }} ({{ $computer->short_key }}) - {{ $computer->plaza }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Mantén Ctrl/Cmd para seleccionar múltiples. {{ $computers->count() }} equipo(s) disponibles.</small>
                        </div>

                        <div id="targetSummary" class="alert alert-info" style="display: none;"></div>
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
            document.getElementById('deployForm').action = '{{ url("admin/agent-versions") }}/' + versionId + '/deploy';
            document.getElementById('deployTarget').value = '';
            document.getElementById('groupField').style.display = 'none';
            document.getElementById('storeField').style.display = 'none';
            document.getElementById('computersField').style.display = 'none';
            document.getElementById('computerSearch').value = '';
            resetComputerSelect();
            document.getElementById('targetSummary').style.display = 'none';
            $('#deployModal').modal('show');
        }

        function resetComputerSelect() {
            var options = document.getElementById('computerSelect').options;
            for (var i = 0; i < options.length; i++) {
                options[i].style.display = '';
                options[i].selected = false;
            }
        }

        document.getElementById('deployTarget').addEventListener('change', function () {
            var target = this.value;
            var groupField = document.getElementById('groupField');
            var storeField = document.getElementById('storeField');
            var computersField = document.getElementById('computersField');
            var summary = document.getElementById('targetSummary');

            groupField.style.display = 'none';
            storeField.style.display = 'none';
            computersField.style.display = 'none';
            summary.style.display = 'none';

            if (target === 'group') {
                groupField.style.display = '';
            } else if (target === 'store') {
                storeField.style.display = '';
            } else if (target === 'computers') {
                computersField.style.display = '';
                resetComputerSelect();
            } else if (target === 'all') {
                summary.textContent = 'Se enviará la actualización a TODAS las computadoras.';
                summary.className = 'alert alert-warning';
                summary.style.display = '';
            }
        });

        document.getElementById('computerSearch').addEventListener('input', function () {
            var query = this.value.toLowerCase();
            var options = document.getElementById('computerSelect').options;

            for (var i = 0; i < options.length; i++) {
                var option = options[i];
                if (!query || option.dataset.search.indexOf(query) !== -1) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                    option.selected = false;
                }
            }
        });

        document.getElementById('computerSelect').addEventListener('change', function () {
            var summary = document.getElementById('targetSummary');
            var count = this.selectedOptions.length;
            if (count > 0) {
                summary.textContent = 'Se enviará la actualización a ' + count + ' equipo(s) seleccionado(s).';
                summary.className = 'alert alert-info';
                summary.style.display = '';
            } else {
                summary.style.display = 'none';
            }
        });

        document.getElementById('groupSelect').addEventListener('change', function () {
            var summary = document.getElementById('targetSummary');
            var selected = this.options[this.selectedIndex];
            if (this.value) {
                summary.textContent = 'Se enviará la actualización al grupo: ' + selected.text;
                summary.className = 'alert alert-info';
                summary.style.display = '';
            } else {
                summary.style.display = 'none';
            }
        });

        document.getElementById('plazaSelect').addEventListener('change', function () {
            var summary = document.getElementById('targetSummary');
            var selected = this.options[this.selectedIndex];
            if (this.value) {
                summary.textContent = 'Se enviará la actualización a la tienda: ' + selected.text;
                summary.className = 'alert alert-info';
                summary.style.display = '';
            } else {
                summary.style.display = 'none';
            }
        });
    </script>
@stop
