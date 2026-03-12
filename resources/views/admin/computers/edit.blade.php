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
                    <label>Short Key (Clave Corta)</label>
                    <input type="text" name="short_key" class="form-control" value="{{ $computer->short_key }}" maxlength="50" placeholder="Ej: VALES01">
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
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Additional Download Paths (Fixed 10)</h3>
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
                <div class="card card-info">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Additional Download Paths (Dynamic)</h3>
                        <button type="button" class="btn btn-sm btn-success" onclick="addPathField()">+ Add Path</button>
                    </div>
                    <div class="card-body" id="dynamicPaths">
                        @php
                            $additionalPaths = $computer->agent_config['additional_download_paths'] ?? [];
                        @endphp
                        @if(count($additionalPaths) > 0)
                            @foreach($additionalPaths as $index => $path)
                            <div class="form-group d-flex align-items-center">
                                <label class="mr-2">Path {{ $index + 11 }}:</label>
                                <input type="text" name="additional_download_paths[]" class="form-control" value="{{ $path }}">
                                <button type="button" class="btn btn-danger btn-sm ml-2" onclick="this.parentElement.remove()">X</button>
                            </div>
                            @endforeach
                        @endif
                    </div>
                </div>
                <div class="form-group">
                    <label>Agent Config (JSON)</label>
                    <textarea name="agent_config_json" class="form-control" rows="5">{{ json_encode($computer->agent_config) }}</textarea>
                </div>

                <!-- Reception Paths Configuration -->
                <div class="card card-success">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title"><i class="fas fa-download"></i> Rutas de Recepción (Desde servidor al agente)</h3>
                        <button type="button" class="btn btn-sm btn-success" onclick="addReceivePathField()">+ Add Ruta</button>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">
                            Configura las rutas donde el agente recibirá archivos del servidor. 
                            Ejemplo: Si configuras <code>C:\Vales</code> con carpeta <code>VALES</code>, 
                            el servidor guardará los archivos en <code>CLAVECORTA/VALES/</code>
                        </p>
                        <div id="receivePathsContainer">
                            @php
                                $receivePaths = $computer->receive_paths ?? [];
                            @endphp
                            @if(count($receivePaths) > 0)
                                @foreach($receivePaths as $index => $path)
                                <div class="form-group row">
                                    <div class="col-md-5">
                                        <label>Ruta Local (Agente)</label>
                                        <input type="text" name="receive_paths[{{ $index }}][local_path]" class="form-control" 
                                               value="{{ $path['local_path'] ?? '' }}" placeholder="C:\Vales">
                                    </div>
                                    <div class="col-md-4">
                                        <label>Nombre de Carpeta</label>
                                        <input type="text" name="receive_paths[{{ $index }}][folder_name]" class="form-control" 
                                               value="{{ $path['folder_name'] ?? '' }}" placeholder="VALES">
                                    </div>
                                    <div class="col-md-2">
                                        <label>Tipo</label>
                                        <select name="receive_paths[{{ $index }}][type]" class="form-control">
                                            <option value="file" {{ ($path['type'] ?? 'file') === 'file' ? 'selected' : '' }}>Archivo</option>
                                            <option value="folder" {{ ($path['type'] ?? '') === 'folder' ? 'selected' : '' }}>Carpeta</option>
                                        </select>
                                    </div>
                                    <div class="col-md-1 d-flex align-items-end">
                                        <button type="button" class="btn btn-danger btn-block" onclick="this.closest('.form-group.row').remove()">X</button>
                                    </div>
                                </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Update</button>
            </form>
        </div>
    </div>

    <script>
        let pathCount = {{ count($additionalPaths ?? []) }};
        let receivePathCount = {{ count($receivePaths ?? []) }};
        
        function addPathField() {
            pathCount++;
            const container = document.getElementById('dynamicPaths');
            const div = document.createElement('div');
            div.className = 'form-group d-flex align-items-center';
            div.innerHTML = `
                <label class="mr-2">Path ${pathCount + 10}:</label>
                <input type="text" name="additional_download_paths[]" class="form-control" placeholder="C:\Path\To\Folder">
                <button type="button" class="btn btn-danger btn-sm ml-2" onclick="this.parentElement.remove()">X</button>
            `;
            container.appendChild(div);
        }

        function addReceivePathField() {
            receivePathCount++;
            const container = document.getElementById('receivePathsContainer');
            const div = document.createElement('div');
            div.className = 'form-group row';
            div.innerHTML = `
                <div class="col-md-5">
                    <label>Ruta Local (Agente)</label>
                    <input type="text" name="receive_paths[${receivePathCount}][local_path]" class="form-control" placeholder="C:\\Vales">
                </div>
                <div class="col-md-4">
                    <label>Nombre de Carpeta</label>
                    <input type="text" name="receive_paths[${receivePathCount}][folder_name]" class="form-control" placeholder="VALES">
                </div>
                <div class="col-md-2">
                    <label>Tipo</label>
                    <select name="receive_paths[${receivePathCount}][type]" class="form-control">
                        <option value="file">Archivo</option>
                        <option value="folder">Carpeta</option>
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" class="btn btn-danger btn-block" onclick="this.closest('.form-group.row').remove()">X</button>
                </div>
            `;
            container.appendChild(div);
        }
    </script>
@stop