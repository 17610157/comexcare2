<div class="monitored-form">
    <div class="form-group">
        <label>Nivel *</label>
        <select name="scope" class="form-control">
            <option value="group" {{ ($formScope ?? 'group') === 'group' ? 'selected' : '' }}>Grupo</option>
            <option value="computer" {{ ($formScope ?? 'group') === 'computer' ? 'selected' : '' }}>Equipo</option>
            <option value="general" {{ ($formScope ?? 'group') === 'general' ? 'selected' : '' }}>General (todas las máquinas)</option>
        </select>
    </div>
    <div class="alert alert-info general-hint d-none">
        Este archivo se aplicará a <strong>todas las máquinas</strong>.
    </div>
    <div class="form-group">
        <label>Grupo *</label>
        <select name="group_id" class="form-control">
            <option value="">Seleccionar grupo...</option>
            @foreach($groups as $group)
                <option value="{{ $group->id }}" {{ $form && $form->group_id === $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group d-none">
        <label>Equipo *</label>
        <select name="computer_id" class="form-control">
            <option value="">Seleccionar equipo...</option>
            @foreach($computers as $computer)
                <option value="{{ $computer->id }}" {{ $form && $form->computer_id === $computer->id ? 'selected' : '' }}>
                    {{ $computer->computer_name }}{{ $computer->short_key ? ' ('.$computer->short_key.')' : '' }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label>Ruta (path) *</label>
        <input type="text" name="path" class="form-control"
               placeholder="D:\PVSI\AJTFLU_RESUMEN o MODEM/ATM o ."
               value="{{ $form->path ?? '' }}" required>
        <small class="form-text text-muted">Ruta absoluta en el equipo o relativa al download_path del agente.</small>
    </div>
    <div class="form-group">
        <label>Archivos (file_names)</label>
        <div class="file-names-list">
            @php $names = ($form && $form->file_names !== null) ? $form->file_names : [null]; @endphp
            @forelse($names as $name)
                <div class="input-group mb-1 file-name-row">
                    <input type="text" name="file_names[]" class="form-control"
                           placeholder="ConciliacionApp.exe, *.DBF o vacío para listar todos"
                           value="{{ $name ?? '' }}">
                    <div class="input-group-append">
                        <button type="button" class="btn btn-outline-danger remove-file-name" tabindex="-1">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            @empty
                <div class="input-group mb-1 file-name-row">
                    <input type="text" name="file_names[]" class="form-control"
                           placeholder="ConciliacionApp.exe, *.DBF o vacío para listar todos">
                    <div class="input-group-append">
                        <button type="button" class="btn btn-outline-danger remove-file-name" tabindex="-1">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            @endforelse
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary add-file-name">
            <i class="fas fa-plus"></i> Agregar archivo
        </button>
        <small class="form-text text-muted">Cada fila puede ser un nombre exacto o un comodín (*.DBF). Vacías = listar todos los archivos de la carpeta.</small>
    </div>
    <div class="form-group">
        <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="recursive_{{ $formId }}" name="recursive" value="1" {{ $form && $form->recursive ? 'checked' : '' }}>
            <label class="custom-control-label" for="recursive_{{ $formId }}">Buscar también en subcarpetas</label>
        </div>
    </div>
    <div class="form-group">
        <label>Orden</label>
        <input type="number" name="sort_order" class="form-control" min="0" value="{{ $form->sort_order ?? 0 }}">
    </div>
</div>
