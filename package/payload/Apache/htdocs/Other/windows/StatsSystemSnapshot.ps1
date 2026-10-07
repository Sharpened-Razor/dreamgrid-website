$ErrorActionPreference = 'Stop'

$os =
    Get-CimInstance `
        -ClassName Win32_OperatingSystem


$processors =
    @(
        Get-CimInstance `
            -ClassName Win32_Processor
    )


$cpuValues =
    @()


foreach ($processor in $processors) {

    if ($null -ne $processor.LoadPercentage) {

        $cpuValues +=
            [double]$processor.LoadPercentage
    }
}


$cpu = $null


if ($cpuValues.Count -gt 0) {

    $cpu =
        [math]::Round(
            (
                $cpuValues |
                Measure-Object `
                    -Average
            ).Average,
            1
        )
}


$openSimProcesses =
    @(
        Get-Process `
            -Name OpenSim `
            -ErrorAction SilentlyContinue
    )


$threadCount = 0


foreach ($process in $openSimProcesses) {

    try {

        $threadCount +=
            [int]$process.Threads.Count
    }
    catch {
    }
}


$result =
    [ordered]@{

        cpu =
            $cpu

        memoryTotal =
            (
                [double]$os.TotalVisibleMemorySize *
                1024
            )

        memoryFree =
            (
                [double]$os.FreePhysicalMemory *
                1024
            )

        boot =
            $os.LastBootUpTime.
            ToUniversalTime().
            ToString('o')

        openSimThreads =
            [int]$threadCount

        openSimProcesses =
            [int]$openSimProcesses.Count
    }


$result |
    ConvertTo-Json `
        -Compress