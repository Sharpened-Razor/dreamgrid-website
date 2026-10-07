[CmdletBinding()]
param(
    [switch]$ValidateOnly
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

$TaskName =
    "DreamGrid - Web Console Launcher"

$Launcher =
    Join-Path `
        $PSScriptRoot `
        "WebConsoleLauncher.vbs"

if (!(Test-Path -LiteralPath $Launcher -PathType Leaf)) {
    throw "WebConsoleLauncher.vbs was not found: $Launcher"
}

$WindowsRoot =
    [Environment]::GetEnvironmentVariable(
        "SystemRoot"
    )

if ([string]::IsNullOrWhiteSpace($WindowsRoot)) {

    $WindowsRoot =
        [Environment]::GetEnvironmentVariable(
            "windir"
        )
}

if ([string]::IsNullOrWhiteSpace($WindowsRoot)) {
    throw "Windows root could not be resolved."
}

$Wscript =
    Join-Path `
        $WindowsRoot `
        "System32\wscript.exe"

if (!(Test-Path -LiteralPath $Wscript -PathType Leaf)) {

    $Command =
        Get-Command `
            wscript.exe `
            -ErrorAction SilentlyContinue |
        Select-Object -First 1

    if ($null -eq $Command) {
        throw "wscript.exe could not be located."
    }

    $Wscript =
        $Command.Source
}

$UserName =
    [System.Security.Principal.WindowsIdentity]::GetCurrent().Name

if ([string]::IsNullOrWhiteSpace($UserName)) {
    throw "Current Windows user could not be resolved."
}

if ($ValidateOnly) {

    Write-Host "VALIDATION OK"
    Write-Host "TASK: $TaskName"
    Write-Host "USER: $UserName"
    Write-Host "WSCRIPT: $Wscript"
    Write-Host "LAUNCHER: $Launcher"

    exit 0
}

$Action =
    New-ScheduledTaskAction `
        -Execute $Wscript `
        -Argument ('"{0}"' -f $Launcher)

$Principal =
    New-ScheduledTaskPrincipal `
        -UserId $UserName `
        -LogonType Interactive `
        -RunLevel Highest

$Settings =
    New-ScheduledTaskSettingsSet `
        -AllowStartIfOnBatteries `
        -DontStopIfGoingOnBatteries `
        -ExecutionTimeLimit (
            New-TimeSpan -Minutes 5
        ) `
        -MultipleInstances IgnoreNew

$Definition =
    New-ScheduledTask `
        -Action $Action `
        -Principal $Principal `
        -Settings $Settings `
        -Description "Launches the selected DreamGrid region console on the interactive desktop."

Register-ScheduledTask `
    -TaskName $TaskName `
    -InputObject $Definition `
    -Force |
Out-Null

$Task =
    Get-ScheduledTask `
        -TaskName $TaskName `
        -ErrorAction Stop

$InstalledAction =
    $Task.Actions |
    Select-Object -First 1

if ($null -eq $InstalledAction) {
    throw "Installed task has no action."
}

if (
    ![string]::Equals(
        [string]$InstalledAction.Execute,
        [string]$Wscript,
        [System.StringComparison]::OrdinalIgnoreCase
    )
) {
    throw "Installed task executable does not match."
}

if (
    ([string]$InstalledAction.Arguments).IndexOf(
        $Launcher,
        [System.StringComparison]::OrdinalIgnoreCase
    ) -lt 0
) {
    throw "Installed task does not reference the portable launcher."
}

Write-Host "INSTALLED:"
Write-Host $TaskName
Write-Host "EXECUTE:"
Write-Host $InstalledAction.Execute
Write-Host "ARGUMENTS:"
Write-Host $InstalledAction.Arguments
