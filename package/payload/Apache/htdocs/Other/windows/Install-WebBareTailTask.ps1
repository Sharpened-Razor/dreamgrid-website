param(
    [string]$TaskName =
        "DreamGrid - Web BareTail Launcher"
)

$ErrorActionPreference =
    "Stop"


$Launcher =
    Join-Path `
        $PSScriptRoot `
        "WebBareTailLauncher.ps1"


if(
    !(Test-Path -LiteralPath $Launcher -PathType Leaf)
) {
    throw "BareTail launcher was not found: $Launcher"
}


$WindowsRoot =
    $env:SystemRoot


if(
    [string]::IsNullOrWhiteSpace(
        $WindowsRoot
    )
) {
    $WindowsRoot =
        $env:WINDIR
}


$PowerShellExe =
    $null


if(
    ![string]::IsNullOrWhiteSpace(
        $WindowsRoot
    )
) {

    $Candidate =
        Join-Path `
            $WindowsRoot `
            "System32\WindowsPowerShell\v1.0\powershell.exe"

    if(
        Test-Path `
            -LiteralPath $Candidate `
            -PathType Leaf
    ) {

        $PowerShellExe =
            $Candidate
    }
}


if(
    [string]::IsNullOrWhiteSpace(
        $PowerShellExe
    )
) {

    $Command =
        Get-Command `
            powershell.exe `
            -ErrorAction Stop

    $PowerShellExe =
        $Command.Source
}


$Arguments =
    '-NoProfile -ExecutionPolicy Bypass -File "' +
    $Launcher +
    '"'


$Action =
    New-ScheduledTaskAction `
        -Execute $PowerShellExe `
        -Argument $Arguments


$UserId =
    [System.Security.Principal.WindowsIdentity]::GetCurrent().Name


$Principal =
    New-ScheduledTaskPrincipal `
        -UserId $UserId `
        -LogonType Interactive `
        -RunLevel Highest


$Settings =
    New-ScheduledTaskSettingsSet `
        -MultipleInstances IgnoreNew


$Definition =
    New-ScheduledTask `
        -Action $Action `
        -Principal $Principal `
        -Settings $Settings `
        -Description "Launches BareTail for the DreamGrid web Region Manager."


Register-ScheduledTask `
    -TaskName $TaskName `
    -InputObject $Definition `
    -Force |
Out-Null


Enable-ScheduledTask `
    -TaskName $TaskName |
Out-Null


$Task =
    Get-ScheduledTask `
        -TaskName $TaskName `
        -ErrorAction Stop


if(!$Task.Settings.Enabled) {
    throw "BareTail scheduled task was created but is disabled."
}


$ActualAction =
    $Task.Actions |
    Select-Object -First 1


if(
    [string]$ActualAction.Arguments -notlike
    "*$Launcher*"
) {
    throw "BareTail task action does not point to the portable launcher."
}


Write-Output "TASK INSTALLED"
Write-Output ("TASK NAME: " + $TaskName)
Write-Output ("TASK STATE: " + [string]$Task.State)
Write-Output ("EXECUTE: " + [string]$ActualAction.Execute)
Write-Output ("ARGUMENTS: " + [string]$ActualAction.Arguments)