<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autorización de Registro</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background-color: #f4f6f9;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .auth-card {
            max-width: 500px;
            width: 100%;
        }
        .auth-card .card-header {
            background: #007bff;
            color: white;
            text-align: center;
        }
        .file-info {
            background: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .file-info p {
            margin-bottom: 5px;
        }
        .authorizer-info {
            background: #e9ecef;
            border-left: 4px solid #6c757d;
            padding: 12px 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        #successMessage {
            display: none;
        }
        #successMessage.show {
            display: block;
        }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="card shadow">
            <div class="card-header">
                <h4><i class="fas fa-shield-alt"></i> Autorización de Registro</h4>
            </div>
            <div class="card-body">
                <div class="file-info">
                    <p><strong>Tipo:</strong>
                        @if($fileList->type === 'whitelist')
                            <span class="badge badge-success">Whitelist (Blanca)</span>
                        @else
                            <span class="badge badge-danger">Blacklist (Negra)</span>
                        @endif
                    </p>
                    <p><strong>Archivo:</strong> <code>{{ $fileList->file_name }}</code></p>
                    <p><strong>Descripción:</strong> {{ $fileList->description ?? 'Sin descripción' }}</p>
                    <p><strong>Registrado por:</strong> {{ $fileList->creator->name ?? 'Desconocido' }}</p>
                    <p><strong>Fecha:</strong> {{ $fileList->created_at->format('d/m/Y H:i') }}</p>
                    @if($fileList->module)
                        <p><strong>Módulo:</strong> {{ $fileList->module->name }}</p>
                    @endif
                </div>

                <div class="authorizer-info">
                    <p class="mb-1"><strong>Autorizando como:</strong></p>
                    <p class="mb-0"><i class="fas fa-user"></i> {{ $authorizerName }}</p>
                    <p class="mb-0"><i class="fas fa-envelope"></i> {{ $authorizerEmail }}</p>
                </div>

                <div id="authorizationForm">
                    <p class="text-muted text-center mb-3">Al hacer clic en "Autorizar", se registrará tu nombre y correo como autorizador de este registro.</p>
                    <button type="button" class="btn btn-success btn-block btn-lg" id="authorizeBtn">
                        <i class="fas fa-check-circle"></i> Autorizar Registro
                    </button>
                </div>

                <div id="successMessage" class="text-center mt-4">
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h4>¡Autorización Exitosa!</h4>
                        <p>El registro ha sido autorizado correctamente.</p>
                        <p class="text-muted"><small>Este enlace ya no es válido.</small></p>
                    </div>
                </div>

                <div id="errorMessage" class="text-center mt-4" style="display:none;">
                    <div class="alert alert-danger">
                        <i class="fas fa-times-circle fa-3x text-danger"></i>
                        <h4>Error</h4>
                        <p id="errorText"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#authorizeBtn').click(function() {
            const btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');

            $.ajax({
                url: '{{ route("authorization.process", $token) }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    $('#authorizationForm').hide();
                    $('#successMessage').addClass('show').show();
                },
                error: function(xhr) {
                    let msg = 'Error al procesar la autorización';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    $('#errorText').text(msg);
                    $('#errorMessage').show();
                    btn.prop('disabled', false).html('<i class="fas fa-check-circle"></i> Autorizar Registro');
                }
            });
        });
    });
    </script>
</body>
</html>
