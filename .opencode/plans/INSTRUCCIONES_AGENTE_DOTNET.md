# Instrucciones para el Desarrollador del Agente .NET

## Objetivo
Implementar la llamada al endpoint `/api/agent/report` después de completar descargas, **SIN modificar ninguna otra funcionalidad existente** del agente.

---

## Cambios Requeridos

### 1. Después de completar descarga de archivos (distribución)

Buscar en el código donde aparece el log:
```
"Download completed: X/Y paths successful"
```

Y **después** de ese log, agregar la llamada HTTP:

```csharp
// Después de "Download completed: 1/1 paths successful"
try
{
    var reportUrl = "<report_url del servidor>";
    var reportData = new
    {
        computer_id = _computerId,
        command_id = command.Id,
        distribution_target_id = targetId, // si está disponible en el contexto
        status = "completed",
        progress = 100,
        response = "Download completed successfully"
    };
    
    var content = new StringContent(
        JsonSerializer.Serialize(reportData),
        Encoding.UTF8,
        "application/json"
    );
    
    using var client = new HttpClient();
    client.Timeout = TimeSpan.FromSeconds(30);
    var response = await client.PostAsync(reportUrl, content);
    
    Log($"Report sent to server: {response.StatusCode}");
}
catch (Exception ex)
{
    Log($"Failed to send report: {ex.Message}");
    // NO fallar la distribución si el report falla - continuar normalmente
}
```

### 2. Si la descarga falla

```csharp
catch (Exception ex)
{
    Log($"Download failed: {ex.Message}");
    
    // Reportar falla al servidor
    try
    {
        var reportData = new
        {
            computer_id = _computerId,
            command_id = command.Id,
            distribution_target_id = targetId,
            status = "failed",
            progress = 0,
            response = ex.Message
        };
        
        var content = new StringContent(
            JsonSerializer.Serialize(reportData),
            Encoding.UTF8,
            "application/json"
        );
        
        using var client = new HttpClient();
        var response = await client.PostAsync(reportUrl, content);
    }
    catch { /* Silenciar errores de report */ }
}
```

---

## Endpoint del Servidor

**URL:** `{server_url}/api/agent/report`

**Método:** POST

**Headers:**
```
Content-Type: application/json
```

**Body JSON:**
```json
{
    "computer_id": 14,
    "command_id": 225,
    "distribution_target_id": 123,
    "status": "completed",
    "progress": 100,
    "response": "Download completed successfully"
}
```

**Campos:**
| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| computer_id | int | Sí | ID de la computadora registrado |
| command_id | int | No | ID del comando recibido |
| distribution_target_id | int | No | ID del objetivo de distribución |
| reception_target_id | int | No | ID del objetivo de recepción |
| status | string | Sí | "completed" o "failed" |
| progress | int | No | 0-100 (default 100 si completed) |
| response | string | No | Mensaje de error si falló |

**Respuesta exitosa (200):**
```json
{
    "message": "Report received"
}
```

---

## Información Importante

### Obtener el report_url
El `report_url` viene en la respuesta del heartbeat:
```json
{
    "message": "Heartbeat received",
    "report_url": "http://canovas.camposreyeros.com:8000/api/agent/report"
}
```

**Guardar el report_url del último heartbeat y usarlo para los reports.**

### NO Modificar
- ❌ Sistema de auto-actualización
- ❌ Encriptación de archivos para subir al servidor
- ❌ Lógica de heartbeat
- ❌ Recepción de archivos (receive)
- ❌ Cualquier otra funcionalidad existente

### Sí Mantener
- ✅ Log "Download completed: X/Y paths successful" (no cambiar)
- ✅ Reintentos de descarga existentes
- ✅ Manejo de errores existente
- ✅ Todas las configuraciones de encrypt/skipEncrypt

---

## Ejemplo de Contexto en el Código

El report debe enviarse cuando:
1. Todas las descargas de un comando se completaron exitosamente
2. Alguna descarga falló
3. Ocurrió un error durante el proceso

**Importante:** El report es para el **comando específico**, no para cada archivo individual.

---

## Prueba de Funcionamiento

Después de implementar, verificar en los logs del servidor que aparecen entradas como:
```
[INFO] Report received {"computer_id":14,"command_id":225,"status":"completed",...}
```

Y en la base de datos del servidor, el comando debe cambiar de `status='sent'` a `status='completed'`.

---

## Notas de Compatibilidad

- El servidor tolera que el report no llegue (reenvía comandos cada 5 minutos)
- Pero el objetivo es que el agente llame al report cuando complete
- El agente puede seguir funcionando aunque el report falle (no bloquear)
