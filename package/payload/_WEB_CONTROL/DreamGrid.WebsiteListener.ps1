$ErrorActionPreference='Stop'
$bridgeScript=(Join-Path (Split-Path -Parent $PSScriptRoot) 'Apache/robust-console-bridge.ps1').Replace('\','/')
$processIds=@(Get-CimInstance Win32_Process | Where-Object {
    $_.CommandLine -and $_.CommandLine.Replace('\','/').IndexOf($bridgeScript,[StringComparison]::OrdinalIgnoreCase) -ge 0
} | ForEach-Object ProcessId)
$ports=@()
foreach($processId in $processIds){$ports+=@(Get-NetTCPConnection -OwningProcess $processId -State Listen -ErrorAction SilentlyContinue | ForEach-Object LocalPort)}
[pscustomobject]@{host=[Net.IPAddress]::Loopback.ToString();ports=@($ports | Sort-Object -Unique)} | ConvertTo-Json -Compress
