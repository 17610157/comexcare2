# Instrucciones para el Agente .NET: Huella de Máquina (`machine_key`)

## Objetivo

Cada agente debe calcular una **huella de máquina estable** (no numérica) y enviarla al servidor
en `register` y `heartbeat`. El servidor la persiste en `computers.machine_key` y la usa como
**identificador fuerte del equipo**, de modo que aunque cambien la MAC, el nombre o el
`computer_id` local, el registro se vuelva a anclar a la máquina correcta.

La huella es **opcional y retrocompatible**: si el cálculo falla, el agente debe seguir
funcionando y enviar los campos actuales sin `machine_key`.

---

## 1. Cálculo de la huella (C#)

Combinar componentes de hardware y aplicar SHA-256. Resultado: **hex de 64 caracteres en
minúsculas**.

| Componente | Origen |
|---|---|
| MachineGuid | Registro `HKLM\SOFTWARE\Microsoft\Cryptography` valor `MachineGuid` |
| UUID de BIOS | WMI `Win32_ComputerSystemProduct` propiedad `UUID` |
| Serial de disco | WMI `Win32_DiskDrive` propiedad `SerialNumber` (disco fijo) |
| Id de CPU | WMI `Win32_Processor` propiedad `ProcessorId` |

```csharp
using System;
using System.Linq;
using System.Management;
using System.Security.Cryptography;
using System.Text;
using Microsoft.Win32;

public static class MachineFingerprint
{
    public static string Compute()
    {
        try
        {
            var machineGuid = ReadRegistry(
                @"HKEY_LOCAL_MACHINE\SOFTWARE\Microsoft\Cryptography", "MachineGuid");
            var biosUuid = WmiFirst("Win32_ComputerSystemProduct", "UUID");
            var diskSerial = WmiFirst("Win32_DiskDrive", "SerialNumber");
            var cpuId = WmiFirst("Win32_Processor", "ProcessorId");

            var raw = $"{machineGuid}|{biosUuid}|{diskSerial}|{cpuId}";

            using var sha = SHA256.Create();
            var hash = sha.ComputeHash(Encoding.UTF8.GetBytes(raw));
            return BitConverter.ToString(hash).Replace("-", "").ToLowerInvariant();
        }
        catch (Exception ex)
        {
            Log($"No se pudo calcular machine_key: {ex.Message}");
            return null; // huella opcional: continuar sin ella
        }
    }

    private static string ReadRegistry(string key, string name)
        => Registry.GetValue(key, name, "")?.ToString() ?? "";

    private static string WmiFirst(string wmiClass, string property)
    {
        using var searcher = new ManagementObjectSearcher($"SELECT {property} FROM {wmiClass}");
        foreach (ManagementObject obj in searcher.Get())
        {
            var value = obj[property]?.ToString();
            if (!string.IsNullOrWhiteSpace(value))
            {
                return value;
            }
        }
        return "";
    }
}
```

Notas:
- Usar `|` como separador evita ambigüedad al concatenar.
- Si un componente no está disponible, usar cadena vacía y continuar con los demás.
- La huella se calcula **una sola vez** y se guarda en la configuración local del agente
  (junto a `computer_id`), en la misma carpeta donde ya se persiste el `short_key`
  (`C:\ProgramData\DistributionAgent\...`).

---

## 2. Enviar la huella

### 2.1 `POST {server_url}/api/register`

Agregar el campo `machine_key` al JSON existente:

```json
{
    "id": 1234,
    "computer_name": "SUCURSAL-01",
    "mac_address": "4C:23:38:6E:6B:67",
    "machine_key": "3f2a...64-char-hex...",
    "agent_version": "2.0.0",
    "system_info": { "os": "Windows 11" }
}
```

### 2.2 `POST {server_url}/api/heartbeat`

Agregar `machine_key` **y `mac_address`** al JSON existente. **El agente debe enviar `machine_key`
SIEMPRE** (en cada heartbeat, incluso si el `computer_id` local ya se conoce) y **también la
`mac_address`** (la MAC real del equipo, la misma que manda en `register`), porque el servidor
identifica al equipo por hardware y **no** por `computer_name` ni por `computer_id`:

```json
{
    "computer_id": 1234,
    "computer_name": "SUCURSAL-01",
    "mac_address": "4C:23:38:6E:6B:67",
    "agent_version": "2.0.0",
    "machine_key": "3f2a...64-char-hex..."
}
```

### 2.3 `POST {server_url}/api/getComputerId` (opcional)

Si el agente usa este endpoint para recuperar su `computer_id`, puede enviar `machine_key`:

```json
{ "machine_key": "3f2a...64-char-hex" }
```

El servidor responde `{ "computer_id": 1234 }` o `404` si no lo encuentra.

---

## 3. Persistencia autoritativa

La respuesta de `register` incluye `machine_key` (el valor que el servidor tiene como
autoritativo):

```json
{
    "id": 1234,
    "message": "Registered successfully",
    "group_id": 5,
    "group_name": "Sucursal Norte",
    "short_key": "SUC01",
    "machine_key": "3f2a...64-char-hex..."
}
```

El agente debe:
1. Guardar el `machine_key` devuelto por el servidor en su configuración local.
2. Enviarlo en los siguientes `heartbeat`.
3. Si el valor devuelto difiere del calculado localmente, **usar el del servidor** de ahí en
   adelante (caso de convergencia de registros duplicados).

### 3.1 Auto-sanación del `computer_id` (IMPORTANTE)

La respuesta de `heartbeat` incluye ahora **`computer_id`** (el id autoritativo del registro
que el servidor conserva por la huella):

```json
{
    "message": "Heartbeat received",
    "computer_id": 1234,
    "computer_name": "SUCURSAL-01",
    "download_path": "C:\\ProgramData\\DistributionAgent\\files",
    "download_paths": ["C:\\ProgramData\\DistributionAgent\\files"],
    "receive_paths": [],
    "report_url": "http://servidor:8000/api/report",
    "machine_key": "3f2a...64-char-hex..."
}
```

El agente debe:
1. Leer `computer_id` de la respuesta del heartbeat.
2. **Persistirlo** como su `computer_id` local (sobrescribiendo el anterior), igual que ya hace
   con `report_url`.
3. Usar ese id en `commands`, `update`, `agent-defaults`, `resurtido`, `report`, etc.

Esto permite que un agente cuyo `computer_id` local quedó desactualizado o apuntando a un
registro eliminado **se recupere solo** en el siguiente heartbeat, adoptando el registro que
posee la `machine_key`.

---

## 4. Reglas del servidor (para referencia del agente)

- **La identidad del equipo es `machine_key` → `mac_address` (hardware).** El servidor **nunca**
  identifica ni fusiona equipos por `computer_name` (no es único) ni por `short_key`
  (clave de tienda). Por eso el agente debe mandar `machine_key` y `mac_address` en cada llamada.
- `machine_key` y `mac_address` son **UNIQUE** en la BD: garantizan un solo registro por equipo.
- `machine_key`: se normaliza a **minúsculas**; máximo **64 caracteres**.
- Si llega `machine_key`, el servidor conserva el registro que la posee, aunque el `computer_id`
  local esté desactualizado o eliminado.
- Si el `computer_id` enviado apunta a **otro** registro sin huella, ese registro obsoleto se
  desactiva (es el registro viejo del mismo equipo).
- Si el `computer_id` enviado pertenece a **otra** máquina (tiene una `machine_key` distinta),
  el servidor **no** lo reutiliza: crea/usa el registro de esta máquina y devuelve su id.
- El `heartbeat` devuelve `computer_id` para que el agente se auto-corrija.
- El `short_key` se sigue asignando desde el panel/registro; el `heartbeat` **no lo sobrescribe**.

---

## 5. NO Modificar

- ❌ Sistema de auto-actualización
- ❌ Encriptación de archivos
- ❌ Lógica de heartbeat existente (solo agregar el campo `machine_key`)
- ❌ Recepción de archivos (receive)
- ❌ Cualquier otra funcionalidad existente

---

## 6. Prueba de funcionamiento

Después de implementar:

1. Registrar un equipo nuevo y verificar en la BD del servidor:
   ```sql
   SELECT id, computer_name, mac_address, machine_key FROM computers WHERE id = <id>;
   ```
   El `machine_key` debe ser un hex de 64 caracteres.
2. Reiniciar el agente y confirmar en los logs del servidor que `heartbeat` mantiene el mismo
   `machine_key` (no lo borra ni lo duplica).
3. (Opcional) Cambiar la MAC del equipo, reiniciar el agente y confirmar que `register`
   devuelve el **mismo** `id` (matcheo por huella).

---

## 7. Publicar la nueva versión

1. Compilar `DistributionAgent.exe` (con `Topshelf.dll` y `Newtonsoft.Json.dll`).
2. Subir la nueva versión desde el panel de administración → **Versiones de Agente**.
3. El servidor distribuirá la actualización vía `checkUpdate`; los agentes empezarán a
   reportar `machine_key` automáticamente tras actualizarse.
