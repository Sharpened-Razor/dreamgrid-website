param(
    [Parameter(Mandatory = $true)]
    [string]$RegionsRoot
)

$ErrorActionPreference =
    "SilentlyContinue"

$WarningPreference =
    "SilentlyContinue"

$ProgressPreference =
    "SilentlyContinue"


[Console]::OutputEncoding =
    New-Object System.Text.UTF8Encoding($false)


function Get-RegionDisplayName {

    param(
        [System.IO.DirectoryInfo]$Folder
    )


    $iniFiles =
        @()


    $regionFolder =
        Join-Path `
            $Folder.FullName `
            "Region"


    if (Test-Path $regionFolder) {

        $iniFiles +=
            Get-ChildItem `
                -LiteralPath $regionFolder `
                -File `
                -Filter "*.ini" `
                -ErrorAction SilentlyContinue
    }


    $iniFiles +=
        Get-ChildItem `
            -LiteralPath $Folder.FullName `
            -File `
            -Filter "*.ini" `
            -ErrorAction SilentlyContinue


    foreach ($ini in $iniFiles) {

        $text =
            [IO.File]::ReadAllText(
                $ini.FullName
            )


        if (
            $text -match
            '(?mi)^\s*RegionName\s*=\s*"?([^"`r`n;]+)'
        ) {

            $candidate =
                $matches[1].Trim()


            if ($candidate) {
                return $candidate
            }
        }


        if (
            $ini.Directory.Name -eq
            "Region"
        ) {

            $candidate =
                [IO.Path]::GetFileNameWithoutExtension(
                    $ini.Name
                )


            if ($candidate) {
                return $candidate
            }
        }
    }


    return $Folder.Name
}


function Get-RegionPort {

    param(
        [System.IO.DirectoryInfo]$Folder
    )


    $searchFiles =
        @()


    $regionFolder =
        Join-Path `
            $Folder.FullName `
            "Region"


    if (Test-Path $regionFolder) {

        $searchFiles +=
            Get-ChildItem `
                -LiteralPath $regionFolder `
                -File `
                -Filter "*.ini" `
                -ErrorAction SilentlyContinue
    }


    foreach ($ini in $searchFiles) {

        $text =
            [IO.File]::ReadAllText(
                $ini.FullName
            )


        if (
            $text -match
            '(?mi)^\s*InternalPort\s*=\s*"?(\d+)'
        ) {

            return [int]$matches[1]
        }
    }


    return 0
}


function Get-PidFile {

    param(
        [System.IO.DirectoryInfo]$Folder
    )


    $candidates =
        @(
            (Join-Path $Folder.FullName "PID.pid"),
            (Join-Path $Folder.FullName "Region\PID.pid")
        )


    foreach ($candidate in $candidates) {

        if (Test-Path $candidate) {
            return $candidate
        }
    }


    return $null
}


function Get-BitCount {

    param(
        [Int64]$Value
    )


    if ($Value -le 0) {
        return 0
    }


    $bits =
        [Convert]::ToString(
            $Value,
            2
        )


    return (
        $bits.ToCharArray() |
        Where-Object {
            $_ -eq "1"
        }
    ).Count
}


function Format-Uptime {

    param(
        [TimeSpan]$Span
    )


    if ($Span.TotalDays -ge 1) {

        return (
            "{0}d {1}h {2}m" -f
            [Math]::Floor($Span.TotalDays),
            $Span.Hours,
            $Span.Minutes
        )
    }


    if ($Span.TotalHours -ge 1) {

        return (
            "{0}h {1}m" -f
            [Math]::Floor($Span.TotalHours),
            $Span.Minutes
        )
    }


    return (
        "{0}m {1}s" -f
        $Span.Minutes,
        $Span.Seconds
    )
}


$processors =
    @(
        Get-CimInstance `
            Win32_Processor `
            -ErrorAction SilentlyContinue
    )


$physicalCores =
    0


$logicalThreads =
    0


foreach ($processor in $processors) {

    $physicalCores +=
        [int]$processor.NumberOfCores


    $logicalThreads +=
        [int]$processor.NumberOfLogicalProcessors
}


if ($logicalThreads -lt 1) {

    $logicalThreads =
        [Environment]::ProcessorCount
}


if ($physicalCores -lt 1) {

    $physicalCores =
        $logicalThreads
}


$cpuModel =
    ""


if ($processors.Count -gt 0) {

    $cpuModel =
        [string]$processors[0].Name


    $cpuModel =
        $cpuModel.Trim()
}


$computer =
    Get-CimInstance `
        Win32_ComputerSystem `
        -ErrorAction SilentlyContinue


$totalMemoryBytes =
    [double]$computer.TotalPhysicalMemory


$totalMemoryGb =
    0


if ($totalMemoryBytes -gt 0) {

    $totalMemoryGb =
        [Math]::Round(
            $totalMemoryBytes / 1GB,
            1
        )
}


$perfByPid =
    @{}


$perfRows =
    @(
        Get-CimInstance `
            Win32_PerfFormattedData_PerfProc_Process `
            -ErrorAction SilentlyContinue
    )


foreach ($perfRow in $perfRows) {

    $perfId =
        [int]$perfRow.IDProcess


    if ($perfId -gt 0) {

        $perfByPid[$perfId] =
            $perfRow
    }
}


$results =
    @()


$regionFolders =
    @(
        Get-ChildItem `
            -LiteralPath $RegionsRoot `
            -Directory `
            -ErrorAction SilentlyContinue
    )


foreach ($folder in $regionFolders) {

    $regionName =
        Get-RegionDisplayName `
            -Folder $folder


    $regionPort =
        Get-RegionPort `
            -Folder $folder


    $pidFile =
        Get-PidFile `
            -Folder $folder


    $processId =
        0


    if ($pidFile) {

        $pidText =
            [IO.File]::ReadAllText(
                $pidFile
            )


        if (
            $pidText -match
            '(\d+)'
        ) {

            $processId =
                [int]$matches[1]
        }
    }


    $running =
        $false


    $workingSetGb =
        0


    $privateMemoryGb =
        0


    $threadCount =
        0


    $handleCount =
        0


    $cpuRaw =
        0


    $cpuHostPercent =
        0


    $cpuEquivalentCores =
        0


    $cpuTotalSeconds =
        0


    $affinityThreads =
        0


    $uptimeSeconds =
        0


    $uptimeText =
        "—"


    if ($processId -gt 0) {

        $process =
            Get-Process `
                -Id $processId `
                -ErrorAction SilentlyContinue


        if ($process) {

            $running =
                $true


            try {

                $workingSetGb =
                    [Math]::Round(
                        [double]$process.WorkingSet64 / 1GB,
                        2
                    )

            }
            catch {
            }


            try {

                $privateMemoryGb =
                    [Math]::Round(
                        [double]$process.PrivateMemorySize64 / 1GB,
                        2
                    )

            }
            catch {
            }


            try {

                $threadCount =
                    [int]$process.Threads.Count

            }
            catch {
            }


            try {

                $handleCount =
                    [int]$process.HandleCount

            }
            catch {
            }


            try {

                $cpuTotalSeconds =
                    [Math]::Round(
                        [double]$process.CPU,
                        1
                    )

            }
            catch {
            }


            try {

                $affinityValue =
                    [Int64]$process.ProcessorAffinity.ToInt64()


                $affinityThreads =
                    Get-BitCount `
                        -Value $affinityValue

            }
            catch {

                $affinityThreads =
                    $logicalThreads
            }


            try {

                $uptime =
                    (Get-Date) -
                    $process.StartTime


                $uptimeSeconds =
                    [Math]::Floor(
                        $uptime.TotalSeconds
                    )


                $uptimeText =
                    Format-Uptime `
                        -Span $uptime

            }
            catch {
            }


            if (
                $perfByPid.ContainsKey(
                    $processId
                )
            ) {

                $perf =
                    $perfByPid[$processId]


                $cpuRaw =
                    [double]$perf.PercentProcessorTime


                $cpuEquivalentCores =
                    [Math]::Round(
                        $cpuRaw / 100,
                        2
                    )


                if ($logicalThreads -gt 0) {

                    $cpuHostPercent =
                        [Math]::Round(
                            $cpuRaw / $logicalThreads,
                            1
                        )
                }
            }
        }
    }


    $memoryPercent =
        0


    if ($totalMemoryBytes -gt 0) {

        $memoryPercent = [Math]::Round(($workingSetGb * 1GB / $totalMemoryBytes) * 100, 1)
    }


    $results +=
        [PSCustomObject]@{

            RegionName =
                $regionName

            FolderName =
                $folder.Name

            Port =
                $regionPort

            Running =
                $running

            ProcessId =
                $processId

            CpuHostPercent =
                $cpuHostPercent

            CpuEquivalentCores =
                $cpuEquivalentCores

            CpuRawPercent =
                [Math]::Round(
                    $cpuRaw,
                    1
                )

            CpuTotalSeconds =
                $cpuTotalSeconds

            WorkingSetGb =
                $workingSetGb

            PrivateMemoryGb =
                $privateMemoryGb

            MemoryPercent =
                $memoryPercent

            ThreadCount =
                $threadCount

            HandleCount =
                $handleCount

            AffinityThreads =
                $affinityThreads

            UptimeSeconds =
                $uptimeSeconds

            UptimeText =
                $uptimeText
        }
}


$pidGroups =
    $results |
    Where-Object {
        $_.ProcessId -gt 0
    } |
    Group-Object `
        ProcessId


foreach ($group in $pidGroups) {

    $shared =
        $group.Count -gt 1


    foreach ($item in $group.Group) {

        Add-Member `
            -InputObject $item `
            -NotePropertyName SharedProcess `
            -NotePropertyValue $shared `
            -Force
    }
}


foreach ($item in $results) {

    if (
        $null -eq
        $item.SharedProcess
    ) {

        Add-Member `
            -InputObject $item `
            -NotePropertyName SharedProcess `
            -NotePropertyValue $false `
            -Force
    }
}


$output =
    [PSCustomObject]@{

        ok =
            $true

        generated =
            (
                Get-Date
            ).ToString(
                "o"
            )

        host =
            [PSCustomObject]@{

                CpuModel =
                    $cpuModel

                PhysicalCores =
                    $physicalCores

                LogicalThreads =
                    $logicalThreads

                TotalMemoryGb =
                    $totalMemoryGb
            }

        regions =
            $results
    }


$output |
    ConvertTo-Json `
        -Depth 6 `
        -Compress