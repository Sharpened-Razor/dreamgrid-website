#requires -Version 5.1
param(
    [Parameter(Mandatory)][string]$Root,
    [string]$PackageDirectory=$PSScriptRoot,
    [string]$ArchiveParent=[Environment]::GetFolderPath('Desktop'),
    [int]$SimulateFailureAfterFiles=0,
    [switch]$FreshInstall,
    [switch]$SimulateFailureAfterOther,
    [switch]$SimulateFailureAfterHook
)
$ErrorActionPreference='Stop'
$Root=(Resolve-Path -LiteralPath $Root).Path
$PackageDirectory=(Resolve-Path -LiteralPath $PackageDirectory).Path
# No destination writes precede this read-only compatibility check.
$prerequisite=& (Join-Path $PackageDirectory 'Test-DreamGridWebsitePrerequisites.ps1') -Root $Root -AllowEmptyOther:$FreshInstall
$stageFirst=$prerequisite.mode -eq 'STAGE-FIRST'
. (Join-Path $PackageDirectory 'DreamGrid.OtherConfiguration.ps1')
$otherPlan=if($stageFirst){New-OtherSettingsPlan (Join-Path $Root 'Settings.ini')}else{$null}
. (Join-Path $PackageDirectory 'WebsiteProcess.ps1')
$prefix=$Root.TrimEnd('\')+'\'
function Target-Path([string]$relative){
    if([IO.Path]::IsPathRooted($relative) -or $relative -match '(^|[/\\])\.\.([/\\]|$)'){throw 'Unsafe payload path'}
    if($relative -notmatch '^(Apache/htdocs/|_WEB_CONTROL/)' -or $relative -match '(?i)(session_secret\.php|\.(key|port|log)$|/jobs/|/map3d-cache/|/profile-notes/)'){throw "Protected or unsupported payload path: $relative"}
    if($relative -match '/private/' -and $relative -ne 'Apache/htdocs/Other/private/.htaccess' -and $relative -notmatch '^Apache/htdocs/Other/private/map3d-tools/'){throw 'Private user state must not be installed from a package'}
    $target=[IO.Path]::GetFullPath((Join-Path $Root $relative))
    if(-not $target.StartsWith($prefix,[StringComparison]::OrdinalIgnoreCase)){throw 'Payload leaves DreamGrid root'}
    $probe=[IO.DirectoryInfo](Split-Path $target -Parent)
    while($probe -and $probe.FullName.StartsWith($Root,[StringComparison]::OrdinalIgnoreCase)){
        if($probe.Exists -and ($probe.Attributes -band [IO.FileAttributes]::ReparsePoint)){throw 'Reparse points are not supported in payload destinations'}
        $probe=$probe.Parent
    }
    if((Test-Path -LiteralPath $target) -and ((Get-Item -LiteralPath $target).Attributes -band [IO.FileAttributes]::ReparsePoint)){throw 'Reparse-point payload file'}
    return $target
}
$manifest=Get-Content -LiteralPath (Join-Path $PackageDirectory 'manifest.json') -Raw|ConvertFrom-Json
$files=@();$seen=[Collections.Generic.HashSet[string]]::new([StringComparer]::OrdinalIgnoreCase)
foreach($file in $manifest.files){
    if(-not $seen.Add($file.path)){throw 'Duplicate payload path'}
    $target=Target-Path $file.path
    $source=[IO.Path]::GetFullPath((Join-Path (Join-Path $PackageDirectory 'payload') $file.path))
    if(-not $source.StartsWith((Join-Path $PackageDirectory 'payload')+'\',[StringComparison]::OrdinalIgnoreCase)){throw 'Payload source leaves package'}
    if((Get-FileHash -LiteralPath $source -Algorithm SHA256).Hash -ne $file.sha256){throw "Payload hash mismatch: $($file.path)"}
    if($file.preserveExisting -and (Test-Path -LiteralPath $target)){continue}
    if($manifest.requireOriginalHashes -and (Test-Path -LiteralPath $target)){
        $actual=(Get-FileHash -LiteralPath $target).Hash
        if($actual -ne $file.originalSha256 -and $actual -ne $file.sha256){throw "Destination changed since verification: $($file.path)"}
    }
    $files += @{source=$source;target=$target;relative=$file.path;sha256=$file.sha256}
}
if($manifest.files.path -contains '_WEB_CONTROL/search/query.php'){
    foreach($relative in @('PHP7/php.exe','PHP7/ext/php_xmlrpc.dll')){if(-not (Test-Path -LiteralPath (Join-Path $Root $relative))){throw 'The native DreamGrid PHP 7 XML-RPC runtime is required for legacy search. Retain the PHP 7 runtime supplied with DreamGrid.'}}
}
if($manifest.files.path -match '^Apache/htdocs/(PerlExample|Stats)/.*\.plx?$'){
    if(-not (Get-Command perl.exe -ErrorAction SilentlyContinue)){throw 'Install/configure the Perl dependency required by native DreamGrid CGI first; perl.exe must resolve through PATH.'}
}
if($manifest.files.path -match '^Apache/htdocs/Other/private/map3d-tools/.*runtimeconfig\.json$'){
    $dotnet=Get-Command dotnet.exe -ErrorAction SilentlyContinue
    if(-not $dotnet){throw 'The existing 3D map tools require the .NET 8 runtime.'}
    $runtimes=& $dotnet.Source --list-runtimes
    if($LASTEXITCODE -ne 0 -or -not ($runtimes -match '^Microsoft.NETCore.App 8\.')){throw 'Install the .NET 8 runtime required by the existing 3D map tools before installing this website.'}
    foreach($relative in @('Opensim/bin/PrimMesher.dll','Opensim/bin/OpenMetaverse.dll','Opensim/bin/OpenMetaverseTypes.dll','Opensim/bin/OpenMetaverse.Rendering.Meshmerizer.dll','Opensim/bin/System.Drawing.Common.dll','Opensim/bin/lib64/openjpeg-dotnet-x86_64.dll')){
        if(-not (Test-Path -LiteralPath (Join-Path $Root $relative))){throw "Configure the existing native DreamGrid runtime first; required native 3D-map dependency missing: $relative"}
    }
}
$parent=Split-Path $Root -Parent
$runtime=Join-Path $parent 'Start.runtimeconfig.json'
if(-not (Test-Path -LiteralPath $runtime)){throw 'The existing native DreamGrid Start.runtimeconfig.json was not found beside its data folder.'}
$hook=Join-Path $Root '_WEB_CONTROL/DreamGrid.NativeBridge.StartupHook.dll'
if(-not ($files|Where-Object {$_.target -eq $hook}) -and -not (Test-Path -LiteralPath $hook)){throw 'Website bridge assembly is missing'}
. (Join-Path $PackageDirectory 'DreamGrid.NativeHookConfiguration.ps1')
$hookPlan=New-NativeHookPlan $runtime $hook
# Refuse active destination services; isolated services in other roots are unrelated.
function Assert-DestinationStopped {
foreach($process in Get-CimInstance Win32_Process){
    if($process.Name -match '^(Start|httpd|OpenSim|Robust)\.exe$'){
        $processPath=Get-WebsiteProcessPath $process
        if(-not $processPath){throw 'Cannot verify an active DreamGrid/Apache process path. Run the installer with sufficient Windows permissions after stopping the destination.'}
        $exe=[IO.Path]::GetFullPath($processPath)
        if($exe.StartsWith($prefix,[StringComparison]::OrdinalIgnoreCase) -or ($process.Name -eq 'Start.exe' -and (Split-Path $exe -Parent) -eq $parent)){throw 'Stop destination DreamGrid and Apache before installing.'}
    }
}
}
Assert-DestinationStopped
if(-not $ArchiveParent){throw 'A Desktop/archive location is required'}
$archive=Join-Path $ArchiveParent ('DreamGrid-Website-Install-'+(Get-Date -Format 'yyyyMMdd-HHmmssfff'))
New-Item -ItemType Directory -Path $archive -Force|Out-Null
$archive=(Resolve-Path -LiteralPath $archive).Path
if($archive.StartsWith($prefix,[StringComparison]::OrdinalIgnoreCase)){throw 'Backups must be outside DreamGrid'}
$records=[Collections.Generic.List[object]]::new()
function Backup-Target([string]$target,[string]$relative,[string]$reason){
    if($records|Where-Object {$_.originalPath -eq $target}){return}
    $exists=Test-Path -LiteralPath $target
    $archived=Join-Path $archive ('files/'+$records.Count.ToString('D5')+[IO.Path]::GetExtension($target))
    $sha=$null
    if($exists){
        New-Item -ItemType Directory -Path (Split-Path $archived -Parent) -Force|Out-Null
        Copy-Item -LiteralPath $target -Destination $archived
        $sha=(Get-FileHash -LiteralPath $target).Hash
        if((Get-FileHash -LiteralPath $archived).Hash -ne $sha){throw 'Backup verification failed'}
    }
    $records.Add([pscustomobject]@{originalPath=$target;archivedPath=if($exists){$archived}else{$null};filename=[IO.Path]::GetFileName($target);sha256=$sha;previouslyExisted=$exists;reason=$reason})
}
$generated=@(
    @{target=(Join-Path $Root '_WEB_CONTROL/website-runtime/local-host.json');relative='OutworldzFiles/_WEB_CONTROL/website-runtime/local-host.json';text=(@{loopbackHost=[Net.IPAddress]::Loopback.ToString()}|ConvertTo-Json);reason='Destination-generated local control endpoint'}
)
$key=Join-Path $Root '_WEB_CONTROL/DreamGrid.NativeBridge.key'
$portFile=Join-Path $Root '_WEB_CONTROL/DreamGrid.NativeBridge.port'
if(-not (Test-Path -LiteralPath $key)){
    $bytes=[byte[]]::new(32);$random=[Security.Cryptography.RandomNumberGenerator]::Create();try{$random.GetBytes($bytes)}finally{$random.Dispose()}
    $generated+=@{target=$key;relative='OutworldzFiles/_WEB_CONTROL/DreamGrid.NativeBridge.key';text=([BitConverter]::ToString($bytes).Replace('-',''));reason='New destination-only random bridge authentication key'}
}
if(-not (Test-Path -LiteralPath $portFile)){
    $listener=[Net.Sockets.TcpListener]::new([Net.IPAddress]::Loopback,0);$listener.Start();$port=$listener.LocalEndpoint.Port;$listener.Stop()
    $generated+=@{target=$portFile;relative='OutworldzFiles/_WEB_CONTROL/DreamGrid.NativeBridge.port';text=[string]$port;reason='Destination-selected available bridge port'}
}
foreach($item in (@($generated)+@(@{target=$runtime}))){
    $probe=Get-Item -LiteralPath (Split-Path $item.target -Parent) -ErrorAction SilentlyContinue
    while($probe){
        if($probe.Attributes -band [IO.FileAttributes]::ReparsePoint){throw 'Runtime metadata must not traverse a reparse point'}
        $probe=$probe.Parent
    }
    if((Test-Path -LiteralPath $item.target) -and ((Get-Item -LiteralPath $item.target).Attributes -band [IO.FileAttributes]::ReparsePoint)){throw 'Runtime metadata must not be a reparse point'}
}
try{
    if($stageFirst -and -not (Test-EmptyOtherWebsite $Root)){throw 'Other content changed after fresh-install confirmation; no deployment performed'}
    foreach($file in $files){Backup-Target $file.target ('OutworldzFiles/'+$file.relative) 'Original website file before verified portability repair'}
    foreach($item in $generated){Backup-Target $item.target $item.relative $item.reason}
    Backup-Target $runtime 'native-parent/Start.runtimeconfig.json' 'Original bytes before verified NativeBridge registration'
    if($stageFirst){Backup-Target $otherPlan.path 'OutworldzFiles/Settings.ini' 'Only effective CMS/OtherCMS after complete fresh website validation'}
    $records|ConvertTo-Json -Depth 8|Set-Content -LiteralPath (Join-Path $archive 'manifest.json') -Encoding UTF8
    @{root=$Root;nativeParent=$parent;status='Backup completed before deployment'}|ConvertTo-Json|Set-Content -LiteralPath (Join-Path $archive 'installation.json') -Encoding UTF8
    $deployedCount=0
    foreach($file in $files){
        New-Item -ItemType Directory -Path (Split-Path $file.target -Parent) -Force|Out-Null
        Copy-Item -LiteralPath $file.source -Destination $file.target -Force
        if((Get-FileHash -LiteralPath $file.target).Hash -ne $file.sha256){throw 'Installed payload verification failed'}
        $deployedCount++;if($SimulateFailureAfterFiles -gt 0 -and $deployedCount -ge $SimulateFailureAfterFiles){throw 'Requested installer failure simulation'}
    }
    foreach($item in $generated){
        New-Item -ItemType Directory -Path (Split-Path $item.target -Parent) -Force|Out-Null
        [IO.File]::WriteAllText($item.target,$item.text,[Text.UTF8Encoding]::new($false))
        if([IO.File]::ReadAllText($item.target) -ne $item.text){throw 'Installed metadata verification failed'}
    }
    if($stageFirst){
        # Re-verify the entire intended website before ever selecting OTHER.
        foreach($file in $manifest.files){
            $target=Target-Path $file.path
            if(-not (Test-Path -LiteralPath $target) -or (-not $file.preserveExisting -and (Get-FileHash -LiteralPath $target).Hash -ne $file.sha256)){throw 'Fresh website is incomplete or invalid; OTHER was not selected'}
        }
        foreach($entry in @('Apache/htdocs/Other/index.php','Apache/htdocs/Other/login.php')){
            $target=Target-Path $entry
            if(-not (Test-Path -LiteralPath $target) -or (Get-Item -LiteralPath $target).Length -eq 0){throw 'Fresh website entry page is missing or empty'}
        }
        Assert-DestinationStopped
        Set-VerifiedOtherSettings $otherPlan
        & (Join-Path $PackageDirectory 'Test-DreamGridWebsitePrerequisites.ps1') -Root $Root | Out-Null
        if($SimulateFailureAfterOther){throw 'Requested post-OTHER failure simulation'}
    }
    # Payload and CMS selection are verified before committing the native hook.
    Assert-DestinationStopped
    $runtimeBackup=$records|Where-Object {$_.originalPath -eq $runtime}
    if((Get-FileHash -LiteralPath $runtimeBackup.archivedPath).Hash -ne $runtimeBackup.sha256){throw 'Runtime backup changed before hook commit'}
    if((Get-FileHash -LiteralPath $hook).Hash -ne ($manifest.files|Where-Object {$_.path -eq '_WEB_CONTROL/DreamGrid.NativeBridge.StartupHook.dll'}).sha256){throw 'NativeBridge assembly verification failed'}
    Set-VerifiedNativeHook $hookPlan
    if($SimulateFailureAfterHook){throw 'Requested post-hook failure simulation'}
    [pscustomobject]@{status='Installed and hash verified';root=$Root;archive=$archive;installedFiles=$files.Count;freshWebsite=$stageFirst;nativeSettingsChanged=($stageFirst -and $otherPlan.changes.Count -gt 0);settingsChanges=if($stageFirst){$otherPlan.changes}else{@()};nativeApachePhpTemplatesChanged=$false;servicesStarted=$false}
}catch{
    $failureIndex=0
    foreach($record in @($records|Sort-Object {if($_.originalPath -eq (Join-Path $Root 'Settings.ini')){0}else{1}})){
        $failureIndex++
        if($record.previouslyExisted){Copy-Item -LiteralPath $record.archivedPath -Destination $record.originalPath -Force}
        elseif(Test-Path -LiteralPath $record.originalPath){
            $failed=Join-Path $archive ('failed-new/'+$failureIndex.ToString('D5')+[IO.Path]::GetExtension($record.originalPath))
            New-Item -ItemType Directory -Path (Split-Path $failed -Parent) -Force|Out-Null
            Move-Item -LiteralPath $record.originalPath -Destination $failed
        }
    }
    foreach($record in $records){
        if($record.previouslyExisted -and (Get-FileHash -LiteralPath $record.originalPath).Hash -ne $record.sha256){throw 'Automatic rollback hash verification failed'}
        if(-not $record.previouslyExisted -and (Test-Path -LiteralPath $record.originalPath)){throw 'Automatic rollback removal verification failed'}
    }
    throw
}
