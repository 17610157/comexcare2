// ============================================
// CAREAGENT RESURTIDO - ACTUALIZADOR
// Agregar al DistributionAgent
// ============================================

public class ResurtidoUpdateResponse
{
    public bool update_available { get; set; }
    public string version { get; set; }
    public string download_url { get; set; }
    public string checksum { get; set; }
}

public async Task UpdateCareAgentResurtido()
{
    try
    {
        // 1. Verificar update
        var url = "http://212.227.6.127/api/resurtido/check-update?computer_id=" + ComputerId;
        var r = await httpClient.GetFromJsonAsync<ResurtidoUpdateResponse>(url);

        if (r?.update_available != true)
        {
            Log("No hay update para CareAgentResurtido");
            return;
        }

        Log("Nueva version: " + r.version);

        // 2. Detener servicio
        var stopProc = new ProcessStartInfo
        {
            FileName = "powershell",
            Arguments = "-Command Stop-Service -Name 'CareAgentResurtido' -Force",
            UseShellExecute = false,
            CreateNoWindow = true
        };
        using (var p = Process.Start(stopProc)) { p?.WaitForExit(10000); }
        Thread.Sleep(2000);

        // 3. Eliminar archivo
        var installPath = @"C:\Program Files\CareAgentResurtido";
        var exePath = Path.Combine(installPath, "CareAgentResurtido.exe");
        if (File.Exists(exePath)) File.Delete(exePath);

        // 4. Descargar nuevo archivo
        using (var dlResp = await httpClient.GetAsync(r.download_url))
        using (var fs = new FileStream(exePath, FileMode.Create))
        {
            await dlResp.Content.CopyToAsync(fs);
        }

        // 5. Verificar checksum
        using (var sha = SHA256.Create())
        using (var fs = File.OpenRead(exePath))
        {
            var hash = BitConverter.ToString(sha.ComputeHash(fs)).Replace("-", "").ToLower();
            if (hash != r.checksum.ToLower())
            {
                LogError("Checksum no coincide");
                return;
            }
        }

        // 6. Iniciar servicio
        var startProc = new ProcessStartInfo
        {
            FileName = "powershell",
            Arguments = "-Command Start-Service -Name 'CareAgentResurtido'",
            UseShellExecute = false,
            CreateNoWindow = true
        };
        using (var p = Process.Start(startProc)) { p?.WaitForExit(10000); }

        Log("=== CareAgentResurtido actualizado ===");
    }
    catch (Exception ex)
    {
        LogError("Error: " + ex.Message);
    }
}

// ============================================
// DÓNDE AGREGAR EN EL AGENTE
// ============================================
// Agregar en el timer o ciclo principal:
// await UpdateCareAgentResurtido();