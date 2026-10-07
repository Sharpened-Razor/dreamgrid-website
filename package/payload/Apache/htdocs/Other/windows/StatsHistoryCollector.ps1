. (Join-Path $PSScriptRoot 'WebsiteRuntime.ps1')
$WebsiteRuntime = Get-DgWebsiteRuntime -StartDirectory $PSScriptRoot
$ErrorActionPreference = 'Stop'
Set-StrictMode -Version 2.0


# ==================================================================
# AUSTRALIA CONTROL CENTER
# STATS HISTORY COLLECTOR V1
#
# Runs once per minute from Windows Task Scheduler.
# Keeps seven days of JSONL samples.
# ==================================================================


function Get-RootFromScript {

    $Directory =
        [System.IO.DirectoryInfo]$PSScriptRoot


    # windows -> Other -> htdocs -> Apache -> OutworldzFiles

    for ($i = 0; $i -lt 4; $i++) {

        if ($null -eq $Directory.Parent) {
            throw 'Could not derive DreamGrid root.'
        }

        $Directory =
            $Directory.Parent
    }


    return $Directory.FullName
}


function Get-NumberProperty {
    param(
        $Object,
        [string[]]$Names
    )

    if ($null -eq $Object) {
        return $null
    }

    foreach ($Name in $Names) {

        $Property =
            $Object.PSObject.Properties[$Name]


        if ($null -ne $Property) {

            $Value =
                0.0


            if (
                [double]::TryParse(
                    [string]$Property.Value,
                    [ref]$Value
                )
            ) {

                return [double]$Value
            }
        }
    }

    return $null
}


function Get-NameProperty {
    param($Object)

    foreach ($Name in @(
        'RegionName',
        'regionName',
        'Name',
        'name'
    )) {

        $Property =
            $Object.PSObject.Properties[$Name]


        if (
            $null -ne $Property -and
            -not [string]::IsNullOrWhiteSpace(
                [string]$Property.Value
            )
        ) {

            return [string]$Property.Value
        }
    }

    return 'Unknown'
}


function Get-Average {
    param([object[]]$Values)

    $Numbers =
        @(
            $Values |
            Where-Object {
                $null -ne $_
            }
        )


    if ($Numbers.Count -eq 0) {
        return $null
    }


    return [math]::Round(
        (
            $Numbers |
            Measure-Object -Average
        ).Average,
        3
    )
}


function Get-Minimum {
    param([object[]]$Values)

    $Numbers =
        @(
            $Values |
            Where-Object {
                $null -ne $_
            }
        )


    if ($Numbers.Count -eq 0) {
        return $null
    }


    return [math]::Round(
        (
            $Numbers |
            Measure-Object -Minimum
        ).Minimum,
        3
    )
}


function Test-TcpPort {
    param(
        [string]$HostName,
        [int]$Port,
        [int]$TimeoutMs = 800
    )

    $Client =
        New-Object System.Net.Sockets.TcpClient


    try {

        $Async =
            $Client.BeginConnect(
                $HostName,
                $Port,
                $null,
                $null
            )


        if (
            -not $Async.AsyncWaitHandle.WaitOne(
                $TimeoutMs,
                $false
            )
        ) {

            return $false
        }


        $Client.EndConnect(
            $Async
        )


        return $true
    }
    catch {

        return $false
    }
    finally {

        $Client.Close()
    }
}


function Get-Telemetry {
    param(
        [string]$Root,
        [string]$TelemetryPhp
    )


    $Result =
        [ordered]@{
            ok      = $false
            ms      = $null
            data    = $null
            error   = $null
            source  = $null
        }


    $PhpCandidates =
        @(
            (Join-Path $Root 'PHP8\php.exe'),
            (Join-Path $Root 'PHP\php.exe')
        )


    foreach ($PhpExe in $PhpCandidates) {

        if (
            -not (
                Test-Path `
                    -LiteralPath $PhpExe `
                    -PathType Leaf
            )
        ) {
            continue
        }


        try {

            $Watch =
                [Diagnostics.Stopwatch]::StartNew()


            $Output =
                & $PhpExe $TelemetryPhp 2>&1 |
                Out-String


            $Watch.Stop()


            $First =
                $Output.IndexOf('{')


            $Last =
                $Output.LastIndexOf('}')


            if (
                $First -ge 0 -and
                $Last -gt $First
            ) {

                $Json =
                    $Output.Substring(
                        $First,
                        $Last - $First + 1
                    )


                $Data =
                    $Json |
                    ConvertFrom-Json


                if (
                    $null -ne $Data -and
                    $Data.ok -eq $true
                ) {

                    $Result.ok =
                        $true


                    $Result.ms =
                        [math]::Round(
                            $Watch.Elapsed.TotalMilliseconds,
                            1
                        )


                    $Result.data =
                        $Data


                    $Result.source =
                        'PHP CLI'


                    return $Result
                }
            }
        }
        catch {

            $Result.error =
                $_.Exception.Message
        }
    }


    # ----------------------------------------------------------
    # FALLBACK TO LOCAL APACHE
    # ----------------------------------------------------------

    try {

        $Watch =
            [Diagnostics.Stopwatch]::StartNew()


        $Response =
            Invoke-WebRequest `
                -Uri ($WebsiteRuntime.apacheBase + '/Other/admin-region-live-direct.php') `
                -UseBasicParsing `
                -TimeoutSec 20 `
                -Headers @{
                    'Cache-Control' = 'no-cache'
                    'Pragma'        = 'no-cache'
                }


        $Watch.Stop()


        $Data =
            $Response.Content |
            ConvertFrom-Json


        if (
            $null -ne $Data -and
            $Data.ok -eq $true
        ) {

            $Result.ok =
                $true


            $Result.ms =
                [math]::Round(
                    $Watch.Elapsed.TotalMilliseconds,
                    1
                )


            $Result.data =
                $Data


            $Result.source =
                'Local Apache'


            return $Result
        }
    }
    catch {

        $Result.error =
            $_.Exception.Message
    }


    return $Result
}


function Get-OpenSimCpu {
    param(
        [string]$StateFile
    )


    $NowUnix =
        [DateTimeOffset]::UtcNow.ToUnixTimeSeconds()


    $Processes =
        @(
            Get-Process `
                -Name 'OpenSim' `
                -ErrorAction SilentlyContinue
        )


    $TotalCpu =
        0.0


    foreach ($Process in $Processes) {

        try {

            $TotalCpu +=
                [double]$Process.CPU
        }
        catch {
        }
    }


    $Current =
        [ordered]@{
            ts     = $NowUnix
            cpuSec = $TotalCpu
        }


    $CpuPercent =
        $null


    if (
        Test-Path `
            -LiteralPath $StateFile `
            -PathType Leaf
    ) {

        try {

            $Previous =
                Get-Content `
                    -LiteralPath $StateFile `
                    -Raw |
                ConvertFrom-Json


            $Elapsed =
                [double]$NowUnix -
                [double]$Previous.ts


            $CpuDelta =
                $TotalCpu -
                [double]$Previous.cpuSec


            if (
                $Elapsed -gt 0 -and
                $CpuDelta -ge 0
            ) {

                $CpuPercent =
                    (
                        $CpuDelta /
                        $Elapsed /
                        [Environment]::ProcessorCount
                    ) *
                    100.0


                if ($CpuPercent -lt 0) {
                    $CpuPercent = 0
                }


                if ($CpuPercent -gt 100) {
                    $CpuPercent = 100
                }


                $CpuPercent =
                    [math]::Round(
                        $CpuPercent,
                        2
                    )
            }
        }
        catch {
        }
    }


    $Current |
        ConvertTo-Json -Compress |
        Set-Content `
            -LiteralPath $StateFile `
            -Encoding UTF8


    return $CpuPercent
}


function Get-ServiceCounts {

    $Rows =
        @(
            Get-CimInstance `
                Win32_Process `
                -ErrorAction SilentlyContinue
        )


    $Apache =
        @(
            $Rows |
            Where-Object {
                $_.Name -match '^(httpd|apache2?)\.exe$'
            }
        ).Count


    $MySql =
        @(
            $Rows |
            Where-Object {
                $_.Name -match '^(mysqld|mariadbd)\.exe$'
            }
        ).Count


    $Robust =
        @(
            $Rows |
            Where-Object {

                $_.Name -match '^Robust\.exe$' -or

                (
                    $_.CommandLine -and
                    $_.CommandLine -match 'Robust'
                )
            }
        ).Count


    $OpenSim =
        @(
            $Rows |
            Where-Object {

                $_.Name -match '^OpenSim\.exe$' -or

                (
                    $_.CommandLine -and
                    $_.CommandLine -match 'OpenSim'
                )
            }
        ).Count


    return [ordered]@{
        apache = $Apache
        mysql  = $MySql
        robust = $Robust
        opensim = $OpenSim
    }
}


function Trim-History {
    param(
        [string]$HistoryFile,
        [datetime]$CutoffUtc
    )


    if (
        -not (
            Test-Path `
                -LiteralPath $HistoryFile `
                -PathType Leaf
        )
    ) {
        return
    }


    $Temp =
        "$HistoryFile.trim"


    $Keep =
        New-Object System.Collections.Generic.List[string]


    foreach (
        $Line in
        [System.IO.File]::ReadLines(
            $HistoryFile
        )
    ) {

        if (
            [string]::IsNullOrWhiteSpace(
                $Line
            )
        ) {
            continue
        }


        try {

            $Item =
                $Line |
                ConvertFrom-Json


            $When =
                [datetime]::Parse(
                    [string]$Item.ts
                ).ToUniversalTime()


            if ($When -ge $CutoffUtc) {

                $Keep.Add(
                    $Line
                )
            }
        }
        catch {
        }
    }


    [System.IO.File]::WriteAllLines(
        $Temp,
        $Keep.ToArray(),
        (
            New-Object System.Text.UTF8Encoding($false)
        )
    )


    Move-Item `
        -LiteralPath $Temp `
        -Destination $HistoryFile `
        -Force
}


# ==================================================================
# MAIN
# ==================================================================

$Root =
    Get-RootFromScript


$Other =
    Join-Path $Root 'Apache\htdocs\Other'


$TelemetryPhp =
    Join-Path $Other 'admin-region-live-direct.php'


$DataDir =
    Join-Path $Root 'ControlCenterData\StatsHistory'


if (
    -not (
        Test-Path `
            -LiteralPath $DataDir `
            -PathType Container
    )
) {

    New-Item `
        -ItemType Directory `
        -Path $DataDir `
        -Force |
    Out-Null
}


$HistoryFile =
    Join-Path $DataDir 'history.jsonl'


$StatusFile =
    Join-Path $DataDir 'collector-status.json'


$CpuStateFile =
    Join-Path $DataDir 'opensim-cpu-state.json'


$LockFile =
    Join-Path $DataDir 'collector.lock'


$Lock =
    $null


try {

    $Lock =
        New-Object System.IO.FileStream(
            $LockFile,
            [System.IO.FileMode]::OpenOrCreate,
            [System.IO.FileAccess]::ReadWrite,
            [System.IO.FileShare]::None
        )
}
catch {

    # Another collector is still running.
    exit 0
}


try {

    $UtcNow =
        [DateTime]::UtcNow


    # ----------------------------------------------------------
    # WINDOWS LOAD
    # ----------------------------------------------------------

    $Os =
        Get-CimInstance `
            Win32_OperatingSystem


    $CpuObjects =
        @(
            Get-CimInstance `
                Win32_Processor
        )


    $CpuLoads =
        @(
            $CpuObjects |
            ForEach-Object {
                [double]$_.LoadPercentage
            }
        )


    $Cpu =
        Get-Average $CpuLoads


    $RamTotalKb =
        [double]$Os.TotalVisibleMemorySize


    $RamFreeKb =
        [double]$Os.FreePhysicalMemory


    $RamUsedKb =
        $RamTotalKb -
        $RamFreeKb


    $RamUsedPct =
        $null


    if ($RamTotalKb -gt 0) {

        $RamUsedPct =
            [math]::Round(
                (
                    $RamUsedKb /
                    $RamTotalKb
                ) *
                100,
                2
            )
    }


    $RamUsedGb =
        [math]::Round(
            $RamUsedKb /
            1MB,
            2
        )


    # ----------------------------------------------------------
    # OPENSIM PROCESS CPU
    # ----------------------------------------------------------

    $OpenSimCpu =
        Get-OpenSimCpu `
            -StateFile $CpuStateFile


    # ----------------------------------------------------------
    # SERVICES
    # ----------------------------------------------------------

    $Services =
        Get-ServiceCounts


    # ----------------------------------------------------------
    # PORTS
    # ----------------------------------------------------------

    $LoginOk =
        Test-TcpPort `
            -HostName $WebsiteRuntime.host `
            -Port $WebsiteRuntime.loginPort


    $DiagnosticsOk =
        Test-TcpPort `
            -HostName $WebsiteRuntime.host `
            -Port $WebsiteRuntime.diagnosticsPort


    # ----------------------------------------------------------
    # LIVE REGION TELEMETRY
    # ----------------------------------------------------------

    $Telemetry =
        Get-Telemetry `
            -Root $Root `
            -TelemetryPhp $TelemetryPhp


    $Rows =
        @()


    if (
        $Telemetry.ok -and
        $null -ne $Telemetry.data
    ) {

        if (
            $null -ne
            $Telemetry.data.PSObject.Properties['rows']
        ) {

            $Rows =
                @(
                    $Telemetry.data.rows
                )
        }
        elseif (
            $null -ne
            $Telemetry.data.PSObject.Properties['regions']
        ) {

            $Rows =
                @(
                    $Telemetry.data.regions
                )
        }
    }


    $SimValues =
        @()


    $PhysicsValues =
        @()


    $FrameValues =
        @()


    $RootAgents =
        0.0


    $ChildAgents =
        0.0


    $Scripts =
        0.0


    $ScriptEps =
        0.0


    $Prims =
        0.0


    foreach ($Row in $Rows) {

        $Sim =
            Get-NumberProperty `
                -Object $Row `
                -Names @(
                    'SimFPS'
                )


        if (
            $null -ne $Sim -and
            $Sim -gt 0
        ) {

            $SimValues +=
                $Sim
        }


        $Physics =
            Get-NumberProperty `
                -Object $Row `
                -Names @(
                    'PhysicsFPS',
                    'PhyFPS',
                    'PhysFPS'
                )


        if (
            $null -ne $Physics -and
            $Physics -gt 0
        ) {

            $PhysicsValues +=
                $Physics
        }


        $Frame =
            Get-NumberProperty `
                -Object $Row `
                -Names @(
                    'TotlFt',
                    'FrameMS',
                    'FrameTime'
                )


        if (
            $null -ne $Frame -and
            $Frame -gt 0
        ) {

            $FrameValues +=
                $Frame
        }


        $Value =
            Get-NumberProperty `
                -Object $Row `
                -Names @(
                    'RootAg',
                    'RootAgents'
                )


        if ($null -ne $Value) {

            $RootAgents +=
                $Value
        }


        $Value =
            Get-NumberProperty `
                -Object $Row `
                -Names @(
                    'ChldAg',
                    'ChildAgents'
                )


        if ($null -ne $Value) {

            $ChildAgents +=
                $Value
        }


        $Value =
            Get-NumberProperty `
                -Object $Row `
                -Names @(
                    'AtvScr',
                    'ActiveScripts'
                )


        if ($null -ne $Value) {

            $Scripts +=
                $Value
        }


        $Value =
            Get-NumberProperty `
                -Object $Row `
                -Names @(
                    'ScrEPS',
                    'ScriptEPS',
                    'ScriptEvents'
                )


        if ($null -ne $Value) {

            $ScriptEps +=
                $Value
        }


        $Value =
            Get-NumberProperty `
                -Object $Row `
                -Names @(
                    'Prims',
                    'PrimCount'
                )


        if ($null -ne $Value) {

            $Prims +=
                $Value
        }
    }


    $Sample =
        [ordered]@{

            ts =
                $UtcNow.ToString(
                    'o'
                )

            cpu =
                $Cpu

            ramUsedPct =
                $RamUsedPct

            ramUsedGb =
                $RamUsedGb

            opensimCpu =
                $OpenSimCpu

            avgSim =
                Get-Average $SimValues

            lowSim =
                Get-Minimum $SimValues

            avgPhysics =
                Get-Average $PhysicsValues

            lowPhysics =
                Get-Minimum $PhysicsValues

            avgFrame =
                Get-Average $FrameValues

            rootAgents =
                [int]$RootAgents

            childAgents =
                [int]$ChildAgents

            activeScripts =
                [int]$Scripts

            scriptEps =
                [math]::Round(
                    $ScriptEps,
                    2
                )

            prims =
                [int]$Prims

            regions =
                $Rows.Count

            apache =
                [int]$Services.apache

            mysql =
                [int]$Services.mysql

            robust =
                [int]$Services.robust

            opensim =
                [int]$Services.opensim

            login =
                [bool]$LoginOk

            diagnostics =
                [bool]$DiagnosticsOk

            telemetryOk =
                [bool]$Telemetry.ok

            telemetryMs =
                $Telemetry.ms
        }


    $Json =
        $Sample |
        ConvertTo-Json `
            -Compress `
            -Depth 5


    [System.IO.File]::AppendAllText(
        $HistoryFile,
        $Json + [Environment]::NewLine,
        (
            New-Object System.Text.UTF8Encoding($false)
        )
    )


    # ----------------------------------------------------------
    # STATUS FILE
    # ----------------------------------------------------------

    $Status =
        [ordered]@{

            ok =
                $true

            lastRunUtc =
                $UtcNow.ToString(
                    'o'
                )

            telemetryOk =
                [bool]$Telemetry.ok

            telemetrySource =
                $Telemetry.source

            telemetryError =
                $Telemetry.error

            historyFile =
                'history.jsonl'
        }


    $Status |
        ConvertTo-Json `
            -Compress `
            -Depth 4 |
        Set-Content `
            -LiteralPath $StatusFile `
            -Encoding UTF8


    # ----------------------------------------------------------
    # KEEP ONLY SEVEN DAYS
    #
    # Trim at minute 00 and 30 so we are not rewriting the file
    # every minute.
    # ----------------------------------------------------------

    if (
        $UtcNow.Minute -eq 0 -or
        $UtcNow.Minute -eq 30
    ) {

        Trim-History `
            -HistoryFile $HistoryFile `
            -CutoffUtc $UtcNow.AddDays(-7)
    }
}
catch {

    $Status =
        [ordered]@{

            ok =
                $false

            lastRunUtc =
                [DateTime]::UtcNow.ToString(
                    'o'
                )

            error =
                $_.Exception.Message
        }


    try {

        $Status |
            ConvertTo-Json `
                -Compress `
                -Depth 4 |
            Set-Content `
                -LiteralPath $StatusFile `
                -Encoding UTF8
    }
    catch {
    }


    throw
}
finally {

    if ($null -ne $Lock) {

        $Lock.Dispose()
    }
}