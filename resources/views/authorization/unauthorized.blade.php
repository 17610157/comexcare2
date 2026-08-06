<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autorización No Disponible</title>
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
        .unauth-card {
            max-width: 450px;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="unauth-card">
        <div class="card shadow">
            <div class="card-header bg-danger text-white text-center">
                <h4><i class="fas fa-exclamation-triangle"></i> No Disponible</h4>
            </div>
            <div class="card-body text-center">
                <i class="fas fa-times-circle fa-4x text-danger mb-3"></i>
                <h5>{{ $message }}</h5>
                <p class="text-muted mt-3">Si crees que esto es un error, contacta al administrador del sistema.</p>
            </div>
        </div>
    </div>
</body>
</html>
