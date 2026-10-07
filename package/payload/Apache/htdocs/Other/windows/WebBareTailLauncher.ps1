param(
    [switch]$ValidateOnly
)

$ErrorActionPreference = "Stop"


# ============================================================
# DISCOVER DREAMGRID ROOT
# ============================================================

$Cursor =
    $PSScriptRoot

$GridRoot =
    $null


for($i = 0; $i -lt 12; $i++) {

    $Settings =
        Join-Path `
            $Cursor `
            "Settings.ini"

    $OpenSim =
        Join-Path `
            $Cursor `
            "Opensim"


    if(
        (Test-Path -LiteralPath $Settings -PathType Leaf) -and
        (Test-Path -LiteralPath $OpenSim -PathType Container)
    ) {

        $GridRoot =
            $Cursor

        break
    }


    $Parent =
        Split-Path `
            $Cursor `
            -Parent


    if(
        [string]::IsNullOrWhiteSpace($Parent) -or
        $Parent -eq $Cursor
    ) {
        break
    }

    $Cursor =
        $Parent
}


if(
    [string]::IsNullOrWhiteSpace(
        $GridRoot
    )
) {
    exit 20
}


# ============================================================
# DYNAMIC PATHS
# ============================================================

$InstallParent =
    Split-Path `
        $GridRoot `
        -Parent


$BareTailCandidates =
    @(
        (
            Join-Path `
                $InstallParent `
                "baretail.exe"
        ),
        (
            Join-Path `
                $PSScriptRoot `
                "baretail.exe"
        )
    )


$BareTail =
    $null


foreach($Candidate in $BareTailCandidates) {

    if(
        Test-Path `
            -LiteralPath $Candidate `
            -PathType Leaf
    ) {

        $BareTail =
            $Candidate

        break
    }
}


$RequestFile =
    Join-Path `
        $GridRoot `
        "_WEB_CONTROL\WebBareTailRequest.txt"


$RegionsPath =
    Join-Path `
        $GridRoot `
        "Opensim\bin\Regions"


$RegionsRoot =
    [System.IO.Path]::GetFullPath(
        $RegionsPath
    ).TrimEnd('\') + '\'


# ============================================================
# VALIDATE
# ============================================================

if(
    [string]::IsNullOrWhiteSpace(
        $BareTail
    )
) {
    exit 10
}


if(
    !(Test-Path $RequestFile -PathType Leaf)
) {
    exit 11
}


$RequestedPath =
    (
        Get-Content `
            -LiteralPath $RequestFile `
            -Raw
    ).Trim()


if(
    [string]::IsNullOrWhiteSpace(
        $RequestedPath
    )
) {
    exit 12
}


try {

    $FullPath =
        [System.IO.Path]::GetFullPath(
            $RequestedPath
        )
}
catch {

    exit 13
}


if(
    !$FullPath.StartsWith(
        $RegionsRoot,
        [System.StringComparison]::OrdinalIgnoreCase
    )
) {
    exit 14
}


if(
    [System.IO.Path]::GetFileName(
        $FullPath
    ) -ne
    "OpenSim.log"
) {
    exit 15
}


if(
    !(Test-Path $FullPath -PathType Leaf)
) {
    exit 16
}


if($ValidateOnly) {
    exit 0
}


Start-Process `
    -FilePath $BareTail `
    -ArgumentList (
        '"{0}"' -f $FullPath
    )