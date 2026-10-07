Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

Add-Type -AssemblyName System.Windows.Forms
Add-Type -AssemblyName System.Drawing

[System.Windows.Forms.Application]::EnableVisualStyles()


# ============================================================
# PORTABLE DREAMGRID PATHS
# ============================================================

$GridRoot =
    [System.IO.Path]::GetFullPath(
        (
            Join-Path `
                $PSScriptRoot `
                '..\..\..\..'
        )
    )


$RegionsRoot =
    Join-Path `
        $GridRoot `
        'Opensim\bin\Regions'


$AutobackupRoot =
    Join-Path `
        $GridRoot `
        'Autobackup'


$QueueDir =
    Join-Path `
        $GridRoot `
        '_WEB_CONTROL\region-export-picker'


if (
    -not (
        Test-Path `
            -LiteralPath $RegionsRoot `
            -PathType Container
    )
) {
    exit 10
}


if (
    -not (
        Test-Path `
            -LiteralPath $AutobackupRoot `
            -PathType Container
    )
) {
    exit 11
}


New-Item `
    -ItemType Directory `
    -Path $QueueDir `
    -Force |
    Out-Null


# ============================================================
# HELPERS
# ============================================================

function Get-RegionNameFromIni {

    param(
        [Parameter(Mandatory)]
        [string]$Path
    )


    try {

        $Lines =
            Get-Content `
                -LiteralPath $Path `
                -TotalCount 100 `
                -ErrorAction Stop


        foreach (
            $Line in $Lines
        ) {

            $Text =
                [string]$Line


            if (
                $Text -match
                '^\s*\[([^\]]+)\]\s*$'
            ) {

                return $Matches[1].Trim()
            }
        }
    }
    catch {
    }


    return (
        [System.IO.Path]::GetFileNameWithoutExtension(
            $Path
        )
    )
}


function Get-AvailableRegionMap {

    $Map =
        @{}


    $Pattern =
        Join-Path `
            $RegionsRoot `
            '*\Region\*.ini'


    $Files =
        @(
            Get-ChildItem `
                -Path $Pattern `
                -File `
                -ErrorAction SilentlyContinue
        )


    foreach (
        $File in $Files
    ) {

        $Name =
            Get-RegionNameFromIni `
                -Path $File.FullName


        if (
            [string]::IsNullOrWhiteSpace(
                $Name
            )
        ) {
            continue
        }


        $Map[
            $Name.ToLowerInvariant()
        ] =
            [pscustomobject]@{
                Name =
                    $Name

                Path =
                    $File.FullName
            }
    }


    return $Map
}


function Get-SafeIniFilename {

    param(
        [Parameter(Mandatory)]
        [string]$Name
    )


    $Safe =
        $Name -replace
        '[<>:"/\\|?*\x00-\x1F]',
        '_'


    $Safe =
        $Safe.Trim()


    if (
        [string]::IsNullOrWhiteSpace(
            $Safe
        )
    ) {

        $Safe =
            'Region'
    }


    return (
        $Safe +
        '.ini'
    )
}


function Show-ExportFolderDialog {

    $Dialog =
        New-Object `
            System.Windows.Forms.FolderBrowserDialog


    $Dialog.Description =
        'Select the folder where the DreamGrid Region INI file(s) will be exported.'


    # Set both properties.
    # Current PowerShell/.NET uses InitialDirectory.
    # Older Windows Forms uses SelectedPath.

    $Dialog.SelectedPath =
        $AutobackupRoot


    if (
        $Dialog.PSObject.Properties.Name -contains
        'InitialDirectory'
    ) {

        $Dialog.InitialDirectory =
            $AutobackupRoot
    }


    $Dialog.ShowNewFolderButton =
        $true


    if (
        $Dialog.PSObject.Properties.Name -contains
        'UseDescriptionForTitle'
    ) {

        $Dialog.UseDescriptionForTitle =
            $true
    }


    if (
        $Dialog.PSObject.Properties.Name -contains
        'AutoUpgradeEnabled'
    ) {

        $Dialog.AutoUpgradeEnabled =
            $true
    }


    $Owner =
        New-Object `
            System.Windows.Forms.Form


    $Owner.Text =
        'DreamGrid Region Export'


    $Owner.ShowInTaskbar =
        $false


    $Owner.TopMost =
        $true


    $Owner.FormBorderStyle =
        [System.Windows.Forms.FormBorderStyle]::None


    $Owner.Width =
        1


    $Owner.Height =
        1


    $Owner.Opacity =
        0.01


    $Owner.StartPosition =
        [System.Windows.Forms.FormStartPosition]::CenterScreen


    try {

        $Owner.Show()


        $Owner.Activate()


        $Result =
            $Dialog.ShowDialog(
                $Owner
            )


        if (
            $Result -ne
            [System.Windows.Forms.DialogResult]::OK
        ) {

            return $null
        }


        return $Dialog.SelectedPath
    }
    finally {

        $Dialog.Dispose()


        $Owner.Close()


        $Owner.Dispose()
    }
}


# ============================================================
# ONE INTERACTIVE BRIDGE AT A TIME
# ============================================================

$Mutex =
    [System.Threading.Mutex]::new(
        $false,
        'DreamGridRegionExportPickerBridge'
    )


$HasMutex =
    $false


try {

    $HasMutex =
        $Mutex.WaitOne(
            0
        )


    if (
        -not $HasMutex
    ) {
        exit 0
    }


    $IdleRounds =
        0


    while (
        $IdleRounds -lt 5
    ) {

        $RequestFile =
            Get-ChildItem `
                -LiteralPath $QueueDir `
                -File `
                -Filter '*.request.json' `
                -ErrorAction SilentlyContinue |
            Sort-Object `
                LastWriteTime,
                Name |
            Select-Object `
                -First 1


        if (
            -not $RequestFile
        ) {

            $IdleRounds++


            Start-Sleep `
                -Milliseconds 500


            continue
        }


        $IdleRounds =
            0


        if (
            $RequestFile.Name -notmatch
            '^([0-9a-fA-F]{32})\.request\.json$'
        ) {

            Remove-Item `
                -LiteralPath $RequestFile.FullName `
                -Force `
                -ErrorAction SilentlyContinue


            continue
        }


        $RequestId =
            $Matches[1].ToLowerInvariant()


        $WorkingFile =
            Join-Path `
                $QueueDir `
                "$RequestId.working.json"


        try {

            Move-Item `
                -LiteralPath $RequestFile.FullName `
                -Destination $WorkingFile `
                -Force


            $Request =
                Get-Content `
                    -LiteralPath $WorkingFile `
                    -Raw `
                    -ErrorAction Stop |
                ConvertFrom-Json `
                    -ErrorAction Stop


            $RequestedRegions =
                @()


            foreach (
                $RawRegion in
                @($Request.regions)
            ) {

                $RegionName =
                    ([string]$RawRegion).Trim()


                if (
                    -not (
                        [string]::IsNullOrWhiteSpace(
                            $RegionName
                        )
                    )
                ) {

                    $RequestedRegions +=
                        $RegionName
                }
            }


            if (
                $RequestedRegions.Count -eq 0
            ) {

                throw 'No regions were supplied for export.'
            }


            $Available =
                Get-AvailableRegionMap


            $Selected =
                @()


            $Missing =
                @()


            foreach (
                $RegionName in
                $RequestedRegions
            ) {

                $Key =
                    $RegionName.ToLowerInvariant()


                if (
                    -not $Available.ContainsKey(
                        $Key
                    )
                ) {

                    $Missing +=
                        $RegionName


                    continue
                }


                $Selected +=
                    $Available[$Key]
            }


            if (
                $Missing.Count -gt 0
            ) {

                throw (
                    "Region INI file not found:`r`n`r`n" +
                    (
                        $Missing -join
                        "`r`n"
                    )
                )
            }


            $DestinationFolder =
                Show-ExportFolderDialog


            if (
                [string]::IsNullOrWhiteSpace(
                    $DestinationFolder
                )
            ) {

                continue
            }


            if (
                -not (
                    Test-Path `
                        -LiteralPath $DestinationFolder `
                        -PathType Container
                )
            ) {

                throw 'The selected folder does not exist.'
            }


            $Existing =
                @()


            foreach (
                $Item in $Selected
            ) {

                $Filename =
                    Get-SafeIniFilename `
                        -Name $Item.Name


                $Destination =
                    Join-Path `
                        $DestinationFolder `
                        $Filename


                if (
                    Test-Path `
                        -LiteralPath $Destination `
                        -PathType Leaf
                ) {

                    $Existing +=
                        $Filename
                }
            }


            if (
                $Existing.Count -gt 0
            ) {

                $Answer =
                    [System.Windows.Forms.MessageBox]::Show(
                        (
                            "$($Existing.Count) Region INI file(s) already exist.`r`n`r`n" +
                            "Replace the existing file(s)?"
                        ),
                        'DreamGrid Region Export',
                        [System.Windows.Forms.MessageBoxButtons]::YesNo,
                        [System.Windows.Forms.MessageBoxIcon]::Warning
                    )


                if (
                    $Answer -ne
                    [System.Windows.Forms.DialogResult]::Yes
                ) {

                    continue
                }
            }


            foreach (
                $Item in $Selected
            ) {

                $Filename =
                    Get-SafeIniFilename `
                        -Name $Item.Name


                $Destination =
                    Join-Path `
                        $DestinationFolder `
                        $Filename


                Copy-Item `
                    -LiteralPath $Item.Path `
                    -Destination $Destination `
                    -Force
            }
        }
        catch {

            try {

                [System.Windows.Forms.MessageBox]::Show(
                    $_.Exception.Message,
                    'DreamGrid Region Export',
                    [System.Windows.Forms.MessageBoxButtons]::OK,
                    [System.Windows.Forms.MessageBoxIcon]::Error
                ) |
                Out-Null
            }
            catch {
            }
        }
        finally {

            Remove-Item `
                -LiteralPath $WorkingFile `
                -Force `
                -ErrorAction SilentlyContinue
        }
    }
}
finally {

    if (
        $HasMutex
    ) {

        try {
            $Mutex.ReleaseMutex()
        }
        catch {
        }
    }


    $Mutex.Dispose()
}