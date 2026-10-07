$ErrorActionPreference = "Stop"


# ============================================================
# PORTABLE GRID WEB CONSOLE LAUNCHER
#
# Installed location:
#
#   <GridRoot>\Apache\htdocs\Other\windows
#
# Therefore four parent levels above this script is GridRoot.
# ============================================================


try {

    $GridRoot =
        [System.IO.Path]::GetFullPath(
            (
                Join-Path `
                    $PSScriptRoot `
                    "..\..\..\.."
            )
        )

}
catch {

    exit 10
}


$RequestFile =
    Join-Path `
        $GridRoot `
        "_WEB_CONTROL\WebConsoleRequest.txt"


$RegionsRoot =
    [System.IO.Path]::GetFullPath(
        (
            Join-Path `
                $GridRoot `
                "Opensim\bin\Regions"
        )
    ).TrimEnd('\') + '\'


if (!(Test-Path $RegionsRoot -PathType Container)) {
    exit 11
}


if (!(Test-Path $RequestFile -PathType Leaf)) {
    exit 12
}


$RegionFolder =
    (
        Get-Content `
            $RequestFile `
            -Raw
    ).Trim()


if ([string]::IsNullOrWhiteSpace($RegionFolder)) {
    exit 13
}


# Region folders must be direct children of Regions.
if (
    [System.IO.Path]::GetFileName($RegionFolder) -ne
    $RegionFolder
) {
    exit 14
}


$FolderPath =
    Join-Path `
        $RegionsRoot `
        $RegionFolder


try {

    $FullFolderPath =
        [System.IO.Path]::GetFullPath(
            $FolderPath
        )

}
catch {

    exit 15
}


if (
    !$FullFolderPath.StartsWith(
        $RegionsRoot,
        [System.StringComparison]::OrdinalIgnoreCase
    )
) {
    exit 16
}


if (!(Test-Path $FullFolderPath -PathType Container)) {
    exit 17
}


$Needle =
    '-inidirectory="./Regions/' +
    $RegionFolder +
    '"'


$Process =
    Get-CimInstance Win32_Process |
    Where-Object {

        $_.Name -eq "OpenSim.exe" -and
        $_.CommandLine -and
        $_.CommandLine.IndexOf(
            $Needle,
            [System.StringComparison]::OrdinalIgnoreCase
        ) -ge 0

    } |
    Select-Object -First 1


if (!$Process) {
    exit 18
}


# The request has now been successfully resolved.
# Remove it so an accidental later task start cannot reopen
# a stale region console.
Remove-Item `
    $RequestFile `
    -Force `
    -ErrorAction SilentlyContinue


Add-Type @"
using System;
using System.Runtime.InteropServices;

public static class GridConsoleWindow
{
    [DllImport("user32.dll")]
    public static extern bool ShowWindowAsync(
        IntPtr hWnd,
        int nCmdShow
    );

    [DllImport("user32.dll")]
    public static extern bool SetForegroundWindow(
        IntPtr hWnd
    );

    [DllImport("user32.dll")]
    public static extern bool BringWindowToTop(
        IntPtr hWnd
    );
}
"@


$P =
    Get-Process `
        -Id $Process.ProcessId `
        -ErrorAction Stop


$Handle =
    $P.MainWindowHandle


if ($Handle -eq 0) {
    exit 19
}


# SW_RESTORE = 9
[GridConsoleWindow]::ShowWindowAsync(
    $Handle,
    9
) | Out-Null


Start-Sleep -Milliseconds 250


[GridConsoleWindow]::BringWindowToTop(
    $Handle
) | Out-Null


[GridConsoleWindow]::SetForegroundWindow(
    $Handle
) | Out-Null


exit 0