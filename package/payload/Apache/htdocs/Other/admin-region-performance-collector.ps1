param(
    [string]$Ports = ""
)

$ErrorActionPreference = "SilentlyContinue"
$WarningPreference = "SilentlyContinue"
$InformationPreference = "SilentlyContinue"
$ProgressPreference = "SilentlyContinue"


# ============================================================
# PORTS
# ============================================================

$PortList = @()

foreach ($Part in ($Ports -split ",")) {

    $PortNumber = 0

    if ([int]::TryParse($Part.Trim(), [ref]$PortNumber)) {

        if ($PortNumber -gt 0 -and $PortNumber -le 65535) {

            $PortList += $PortNumber
        }
    }
}

$PortList =
    @(
        $PortList |
        Sort-Object -Unique
    )


# ============================================================
# HOST CPU - CACHED STATIC HARDWARE V7.9D
# ============================================================

$PhysicalCores = 0
$LogicalThreads = 0
$CpuModel = ""
$TotalMemoryBytes = 0.0

$HostCachePath =
    Join-Path `
        ([System.IO.Path]::GetTempPath()) `
        "australia-region-performance-host-v79d.json"

$HostCacheMaxAgeSeconds = [int64]86400
$CurrentUnix = [DateTimeOffset]::UtcNow.ToUnixTimeSeconds()
$HostCacheValid = $false
$HostCacheData = $null

if (Test-Path -LiteralPath $HostCachePath -PathType Leaf) {

    try {

        $HostCacheText = [System.IO.File]::ReadAllText($HostCachePath)

        if (-not [string]::IsNullOrWhiteSpace($HostCacheText)) {

            $HostCacheData =
                $HostCacheText |
                ConvertFrom-Json -ErrorAction Stop

            $SavedUnix = [int64]$HostCacheData.SavedUnix
            $CacheAge = $CurrentUnix - $SavedUnix

            if (
                $CacheAge -ge 0 -and
                $CacheAge -lt $HostCacheMaxAgeSeconds -and
                [string]$HostCacheData.MachineName -eq [Environment]::MachineName -and
                [int]$HostCacheData.PhysicalCores -gt 0 -and
                [int]$HostCacheData.LogicalThreads -gt 0 -and
                -not [string]::IsNullOrWhiteSpace([string]$HostCacheData.CpuModel) -and
                [double]$HostCacheData.TotalMemoryBytes -gt 0
            ) {

                $HostCacheValid = $true
            }
        }
    }
    catch {

        $HostCacheValid = $false
    }
}

if ($HostCacheValid) {

    $PhysicalCores = [int]$HostCacheData.PhysicalCores
    $LogicalThreads = [int]$HostCacheData.LogicalThreads
    $CpuModel = [string]$HostCacheData.CpuModel
    $TotalMemoryBytes = [double]$HostCacheData.TotalMemoryBytes
}
else {

    $CpuInfo =
        @(
            Get-CimInstance `
                Win32_Processor `
                -ErrorAction SilentlyContinue
        )

    $ComputerInfo =
        Get-CimInstance `
            Win32_ComputerSystem `
            -ErrorAction SilentlyContinue

    if ($CpuInfo.Count -gt 0) {

        $PhysicalCores =
            [int](
                (
                    $CpuInfo |
                    Measure-Object `
                        -Property NumberOfCores `
                        -Sum
                ).Sum
            )

        $LogicalThreads =
            [int](
                (
                    $CpuInfo |
                    Measure-Object `
                        -Property NumberOfLogicalProcessors `
                        -Sum
                ).Sum
            )

        $CpuModel = [string]$CpuInfo[0].Name
    }

    if ($LogicalThreads -le 0) {

        $LogicalThreads = [Environment]::ProcessorCount
    }

    if ($ComputerInfo) {

        $TotalMemoryBytes = [double]$ComputerInfo.TotalPhysicalMemory
    }

    if (
        $PhysicalCores -gt 0 -and
        $LogicalThreads -gt 0 -and
        -not [string]::IsNullOrWhiteSpace($CpuModel) -and
        $TotalMemoryBytes -gt 0
    ) {

        $HostCacheObject =
            [ordered]@{
                SavedUnix        = [int64]$CurrentUnix
                MachineName      = [Environment]::MachineName
                CpuModel         = [string]$CpuModel
                PhysicalCores    = [int]$PhysicalCores
                LogicalThreads   = [int]$LogicalThreads
                TotalMemoryBytes = [double]$TotalMemoryBytes
            }

        try {

            $HostCacheJson =
                $HostCacheObject |
                ConvertTo-Json -Depth 4 -Compress

            $HostCacheEncoding =
                [System.Text.UTF8Encoding]::new($false)

            [System.IO.File]::WriteAllText(
                $HostCachePath,
                $HostCacheJson,
                $HostCacheEncoding
            )
        }
        catch {
        }
    }
}

# ============================================================
# TCP LISTENERS - FAST NETSTAT V7.9E
# ============================================================

$Listeners = @()

$NetstatExe =
    Join-Path `
        $env:SystemRoot `
        "System32\netstat.exe"

if (Test-Path -LiteralPath $NetstatExe -PathType Leaf) {

    $NetstatOutput =
        @(
            & $NetstatExe -ano -p tcp 2>$null
        )

    foreach ($NetstatLine in $NetstatOutput) {

        $NetstatText =
            ([string]$NetstatLine).Trim()

        if ([string]::IsNullOrWhiteSpace($NetstatText)) {
            continue
        }

        $NetstatParts =
            @(
                $NetstatText -split "\s+"
            )

        if ($NetstatParts.Count -lt 5) {
            continue
        }

        if ($NetstatParts[0] -ne "TCP") {
            continue
        }

        if ($NetstatParts[3] -ne "LISTENING") {
            continue
        }

        $PortMatchItem =
            [regex]::Match(
                $NetstatParts[1],
                ":(\d+)$"
            )

        if (-not $PortMatchItem.Success) {
            continue
        }

        $PortNumber = 0
        $ProcessNumber = 0

        if (
            -not [int]::TryParse(
                $PortMatchItem.Groups[1].Value,
                [ref]$PortNumber
            )
        ) {
            continue
        }

        if (
            -not [int]::TryParse(
                $NetstatParts[4],
                [ref]$ProcessNumber
            )
        ) {
            continue
        }

        if ($PortList -notcontains $PortNumber) {
            continue
        }

        $Listeners +=
            [pscustomobject]@{
                LocalPort = [int]$PortNumber
                OwningProcess = [int]$ProcessNumber
            }
    }
}
else {

    $Listeners =
        @(
            Get-NetTCPConnection `
                -State Listen `
                -ErrorAction SilentlyContinue
        )
}

# ============================================================
# PROCESS DATA
# ============================================================

$Rows = @()


foreach ($Port in $PortList) {

    $Listener =
        $Listeners |
        Where-Object {
            [int]$_.LocalPort -eq [int]$Port
        } |
        Select-Object -First 1


    if (-not $Listener) {

        $Rows +=
            [pscustomobject]@{
                Port            = [int]$Port
                Running         = $false
                ProcessId       = 0
                CpuTotalSeconds = 0.0
                WorkingSetBytes = 0.0
                ThreadCount     = 0
                AffinityThreads = 0
                UptimeSeconds   = 0.0
                StartTimeUtc    = ""
            }

        continue
    }


    $ProcessId =
        [int]$Listener.OwningProcess


    $Process =
        Get-Process `
            -Id $ProcessId `
            -ErrorAction SilentlyContinue


    if (-not $Process) {

        $Rows +=
            [pscustomobject]@{
                Port            = [int]$Port
                Running         = $false
                ProcessId       = [int]$ProcessId
                CpuTotalSeconds = 0.0
                WorkingSetBytes = 0.0
                ThreadCount     = 0
                AffinityThreads = 0
                UptimeSeconds   = 0.0
                StartTimeUtc    = ""
            }

        continue
    }


    $CpuTotalSeconds = 0.0
    $WorkingSetBytes = 0.0
    $ThreadCount = 0
    $AffinityThreads = 0
    $UptimeSeconds = 0.0
    $StartTimeUtc = ""


    try {

        $CpuTotalSeconds =
            [double]$Process.TotalProcessorTime.TotalSeconds
    }
    catch {
    }


    try {

        $WorkingSetBytes =
            [double]$Process.WorkingSet64
    }
    catch {
    }


    try {

        $ThreadCount =
            [int]$Process.Threads.Count
    }
    catch {
    }


    try {

        $StartTime =
            $Process.StartTime

        $StartTimeUtc =
            $StartTime.ToUniversalTime().ToString("o")

        $UptimeSeconds =
            [double](
                (Get-Date) -
                $StartTime
            ).TotalSeconds

        if ($UptimeSeconds -lt 0) {

            $UptimeSeconds = 0
        }
    }
    catch {
    }


    try {

        $Mask =
            [UInt64]$Process.ProcessorAffinity.ToInt64()

        $Count = 0

        while ($Mask -ne 0) {

            if (($Mask -band 1) -eq 1) {

                $Count++
            }

            $Mask =
                $Mask -shr 1
        }

        $AffinityThreads =
            [int]$Count
    }
    catch {
    }


    if ($AffinityThreads -le 0) {

        $AffinityThreads =
            [int]$LogicalThreads
    }


    $Rows +=
        [pscustomobject]@{
            Port            = [int]$Port
            Running         = $true
            ProcessId       = [int]$ProcessId
            CpuTotalSeconds = [double]$CpuTotalSeconds
            WorkingSetBytes = [double]$WorkingSetBytes
            ThreadCount     = [int]$ThreadCount
            AffinityThreads = [int]$AffinityThreads
            UptimeSeconds   = [double]$UptimeSeconds
            StartTimeUtc    = [string]$StartTimeUtc
        }
}


# ============================================================
# OUTPUT
# ============================================================

$Timestamp =
    [double](
        [DateTimeOffset]::UtcNow.ToUnixTimeMilliseconds()
    ) / 1000.0


$Result =
    [ordered]@{

        ok =
            $true

        timestamp =
            $Timestamp

        host =
            [ordered]@{

                CpuModel =
                    $CpuModel

                PhysicalCores =
                    [int]$PhysicalCores

                LogicalThreads =
                    [int]$LogicalThreads

                TotalMemoryBytes =
                    [double]$TotalMemoryBytes
            }

        processes =
            $Rows
    }


$Result |
    ConvertTo-Json `
        -Depth 6 `
        -Compress
