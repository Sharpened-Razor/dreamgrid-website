#requires -Version 7.0
param([Parameter(Mandatory)][string]$ArchiveDirectory)
$ErrorActionPreference='Stop'
. (Join-Path $PSScriptRoot 'WebsiteProcess.ps1')
$ArchiveDirectory=(Resolve-Path -LiteralPath $ArchiveDirectory).Path
$installation=Get-Content -LiteralPath (Join-Path $ArchiveDirectory 'installation.json') -Raw|ConvertFrom-Json
$root=[IO.Path]::GetFullPath($installation.root);$parent=[IO.Path]::GetFullPath($installation.nativeParent)
$rootPrefix=$root.TrimEnd('\')+'\'
if(-not (Test-Path -LiteralPath (Join-Path $root 'Settings.ini'))){throw 'Original DreamGrid installation is missing'}
foreach($process in Get-CimInstance Win32_Process){
    if($process.Name -match '^(Start|httpd|OpenSim|Robust)\.exe$'){
        $processPath=Get-WebsiteProcessPath $process
        if(-not $processPath){throw 'Cannot verify an active process path; stop the destination and run rollback with sufficient Windows permissions'}
        if($processPath.StartsWith($rootPrefix,[StringComparison]::OrdinalIgnoreCase) -or ($process.Name -eq 'Start.exe' -and (Split-Path $processPath -Parent) -eq $parent)){throw 'Stop destination DreamGrid and Apache before rollback'}
    }
}
$records=@(Get-Content -LiteralPath (Join-Path $ArchiveDirectory 'manifest.json') -Raw|ConvertFrom-Json)
foreach($record in $records){
    $target=[IO.Path]::GetFullPath($record.originalPath)
    if(-not $target.StartsWith($rootPrefix,[StringComparison]::OrdinalIgnoreCase) -and $target -ne (Join-Path $parent 'Start.runtimeconfig.json')){throw 'Rollback target outside recorded installation'}
    if($record.previouslyExisted){
        $source=[IO.Path]::GetFullPath($record.archivedPath)
        if(-not $source.StartsWith($ArchiveDirectory+'\',[StringComparison]::OrdinalIgnoreCase) -or (Get-FileHash -LiteralPath $source).Hash -ne $record.sha256){throw 'Rollback backup hash/path mismatch'}
    }
    $probe=[IO.DirectoryInfo](Split-Path $target -Parent)
    while($probe){if($probe.Exists -and ($probe.Attributes -band [IO.FileAttributes]::ReparsePoint)){throw 'Reparse point in rollback destination'};$probe=$probe.Parent}
    if((Test-Path -LiteralPath $target) -and ((Get-Item -LiteralPath $target).Attributes -band [IO.FileAttributes]::ReparsePoint)){throw 'Reparse-point rollback file'}
}
$saved=Join-Path $ArchiveDirectory ('rollback-current-'+(Get-Date -Format 'yyyyMMdd-HHmmssfff'))
foreach($record in $records){
    if(Test-Path -LiteralPath $record.originalPath){
        $destination=Join-Path $saved ([IO.Path]::GetRelativePath($parent,$record.originalPath))
        New-Item -ItemType Directory -Path (Split-Path $destination -Parent) -Force|Out-Null
        Copy-Item -LiteralPath $record.originalPath -Destination $destination
        if((Get-FileHash -LiteralPath $record.originalPath).Hash -ne (Get-FileHash -LiteralPath $destination).Hash){throw 'Current-file preservation failed'}
    }
}
foreach($record in $records){
    if($record.previouslyExisted){
        Copy-Item -LiteralPath $record.archivedPath -Destination $record.originalPath -Force
        if((Get-FileHash -LiteralPath $record.originalPath).Hash -ne $record.sha256){throw 'Rollback verification failed'}
    }elseif(Test-Path -LiteralPath $record.originalPath){
        $destination=Join-Path $saved ('new-files/'+[IO.Path]::GetRelativePath($parent,$record.originalPath))
        New-Item -ItemType Directory -Path (Split-Path $destination -Parent) -Force|Out-Null
        Move-Item -LiteralPath $record.originalPath -Destination $destination
    }
}
[pscustomobject]@{status='Rollback verified';restoredRecords=$records.Count;currentFilesPreserved=$saved;permanentDeletions=0}
