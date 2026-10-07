param(
    [switch]$ForceRun
)

. (Join-Path $PSScriptRoot 'WebsiteRuntime.ps1')
$WebsiteRuntime = Get-DgWebsiteRuntime -StartDirectory $PSScriptRoot

$ErrorActionPreference = 'Stop'
$WindowsDir = $PSScriptRoot

#
# This runner lives under:
#
#   <htdocs>\Other\windows\backup-scheduler-runner.ps1
#
# Resolve the current /Other and htdocs roots dynamically
# from the runner location. No installation drive or grid
# folder name is hard-coded.
#
$OtherDir =
    Split-Path -Parent $WindowsDir

$WebRoot =
    Split-Path -Parent $OtherDir

#
# Control Panel scheduler state/config live under:
#
#   <htdocs>\Other\jobs
#
$JobsDir =
    Join-Path $OtherDir 'jobs'
# AG_SHARED_SCHEDULER_JOBS
$HtdocsRoot = $WebRoot

$ConfigFile =
    Join-Path $JobsDir 'backup_schedule.json'

$StateFile =
    Join-Path $JobsDir 'backup_scheduler_state.json'

$LockFile =
    Join-Path $JobsDir 'backup_scheduler.lock'

$LogFile =
    Join-Path $JobsDir 'backup_scheduler.log'


#
# Locate the DreamGrid root dynamically by walking upward
# until an ancestor contains both Settings.ini and Opensim.
#
$GridRoot =
    $null

$Probe =
    $WebRoot

while(
    -not [string]::IsNullOrWhiteSpace(
        $Probe
    )
) {

    $ProbeSettings =
        Join-Path $Probe 'Settings.ini'

    $ProbeOpenSim =
        Join-Path $Probe 'Opensim'


    if(
        (Test-Path -LiteralPath $ProbeSettings) -and
        (Test-Path -LiteralPath $ProbeOpenSim)
    ) {

        $GridRoot =
            (Resolve-Path $Probe).Path

        break
    }


    $Parent =
        Split-Path `
            -Parent `
            $Probe

    if(
        [string]::IsNullOrWhiteSpace(
            $Parent
        )
    ) {
        break
    }

    if($Parent -eq $Probe) {
        break
    }

    $Probe =
        $Parent
}


if(
    [string]::IsNullOrWhiteSpace(
        $GridRoot
    )
) {

    throw (
        'Unable to locate the DreamGrid root. ' +
        'Expected an ancestor containing Settings.ini and Opensim.'
    )
}


$AutoBackupRoot =
    Join-Path $GridRoot 'Autobackup'

$SettingsFile =
    Join-Path $GridRoot 'Settings.ini'

New-Item -ItemType Directory -Path $JobsDir -Force | Out-Null

function Log([string]$Text) {
    $line = "$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss') | $Text"
    Add-Content -LiteralPath $LogFile -Value $line -Encoding UTF8
}

function Add-History([string]$Type,[string]$Source,[string]$Status,[string]$Subject,[string]$File,[int64]$Size,[string]$Message,[datetime]$When) {
    $historyFile = Join-Path $JobsDir 'backup_history.json'
    $rows = @()
    if(Test-Path -LiteralPath $historyFile) {
        try { $rows = @(Get-Content -LiteralPath $historyFile -Raw | ConvertFrom-Json) } catch { $rows = @() }
    }
    $entry = [ordered]@{
        id = 'scheduled_' + [guid]::NewGuid().ToString('N')
        time = $When.ToString('o')
        type = $Type
        source = $Source
        status = $Status
        subject = $Subject
        file = $File
        size = $Size
        duration = 0
        message = $Message
        actor = 'Windows Scheduler'
    }
    $all = @($entry) + @($rows)
    if($all.Count -gt 2000){ $all = @($all | Select-Object -First 2000) }
    $all | ConvertTo-Json -Depth 8 | Set-Content -LiteralPath $historyFile -Encoding UTF8
}

function Write-State([hashtable]$State) {
    $State.updated = (Get-Date).ToString('o')
    ($State | ConvertTo-Json -Depth 8) | Set-Content -LiteralPath $StateFile -Encoding UTF8
}

function Read-State {
    $state = @{
        lastCheck = $null
        lastCheckResult = 'Never checked'
        lastRun = $null
        lastSlot = $null
        lastResult = 'Never run'
        lastBackupRun = $null
        lastBackupFinished = $null
        lastBackupResult = 'Never run'
        lastBackupCompleted = 0
        lastBackupSkipped = 0
        lastBackupErrors = 0
        lastBackupRegions = @()
        completed = 0
        skipped = 0
        errors = 0
        regions = @()
    }

    if(Test-Path -LiteralPath $StateFile) {
        try {
            $o = Get-Content -LiteralPath $StateFile -Raw | ConvertFrom-Json

            foreach($name in @(
                'lastCheck','lastCheckResult','lastRun','lastSlot','lastResult',
                'lastBackupRun','lastBackupFinished','lastBackupResult'
            )) {
                if($null -ne $o.$name) { $state[$name] = [string]$o.$name }
            }

            foreach($name in @(
                'lastBackupCompleted','lastBackupSkipped','lastBackupErrors',
                'completed','skipped','errors'
            )) {
                if($null -ne $o.$name) { $state[$name] = [int]$o.$name }
            }

            if($null -ne $o.regions) { $state.regions = @($o.regions) }
            if($null -ne $o.lastBackupRegions) { $state.lastBackupRegions = @($o.lastBackupRegions) }

            # Backward compatibility with the already-completed V57 test:
            # if the old state contains a completed run, promote it into the
            # durable "last backup" fields the first time this new runner reads it.
            if(!$state.lastBackupRun -and $state.lastRun -and $state.completed -gt 0) {
                $state.lastBackupRun = $state.lastRun
                $state.lastBackupFinished = $state.updated
                $state.lastBackupCompleted = $state.completed
                $state.lastBackupSkipped = $state.skipped
                $state.lastBackupErrors = $state.errors
                $state.lastBackupRegions = @($state.regions)
                $state.lastBackupResult = "FINISHED completed=$($state.completed) skipped=$($state.skipped) errors=$($state.errors)"
            }
        } catch {}
    }

    return $state
}

function Acquire-Lock {
    if(Test-Path -LiteralPath $LockFile) {
        try {
            $age = (Get-Date) - (Get-Item -LiteralPath $LockFile).LastWriteTime
            if($age.TotalHours -lt 12) {
                Log "Another scheduler run appears active; exiting."
                return $false
            }
        } catch {}
        Remove-Item -LiteralPath $LockFile -Force -ErrorAction SilentlyContinue
    }
    "$(Get-Date -Format o) PID=$PID" | Set-Content -LiteralPath $LockFile -Encoding ASCII
    return $true
}

function Release-Lock {
    Remove-Item -LiteralPath $LockFile -Force -ErrorAction SilentlyContinue
}

function Get-MachineHash {
    if(!(Test-Path -LiteralPath $SettingsFile)) { throw "Settings.ini not found: $SettingsFile" }
    $line = Get-Content -LiteralPath $SettingsFile | Where-Object { $_ -match '^\s*MachineHash\s*=' } | Select-Object -First 1
    if(!$line) { throw 'MachineHash not found in Settings.ini' }
    $v = ($line -replace '^\s*MachineHash\s*=\s*','').Trim().Trim('"')
    if(!$v) { throw 'MachineHash is blank' }
    return $v
}

function Get-DiagnosticsPort {
    if(!(Test-Path -LiteralPath $SettingsFile)) {
        return $WebsiteRuntime.diagnosticsPort
    }

    $lines = Get-Content -LiteralPath $SettingsFile

    foreach($key in @(
        'DiagnosticPort',
        'DiagnosticsPort',
        'DiagnosticsPortWas'
    )) {
        $line = $lines |
            Where-Object {
                $_ -match ('^\s*' + [regex]::Escape($key) + '\s*=')
            } |
            Select-Object -First 1

        if(!$line) { continue }

        $value = (($line -split '=',2)[1]).Trim().Trim('"')
        $port = 0

        if(
            [int]::TryParse($value,[ref]$port) -and
            $port -ge 1 -and
            $port -le 65535
        ) {
            return $port
        }
    }

    return $WebsiteRuntime.diagnosticsPort
}

$DiagnosticsPort = Get-DiagnosticsPort

function Invoke-DreamGrid([hashtable]$Params) {
    $pairs = foreach($k in $Params.Keys) {
        '{0}={1}' -f [uri]::EscapeDataString([string]$k), [uri]::EscapeDataString([string]$Params[$k])
    }
    $url = $WebsiteRuntime.diagnosticsBase + '/API/?' + ($pairs -join '&')
    return (Invoke-WebRequest -UseBasicParsing -Uri $url -TimeoutSec 30).Content.Trim()
}

function Get-RegionStatus([string]$Region) {
    try {
        $url = $WebsiteRuntime.diagnosticsBase + '/?command=RegionStatus&RegionName=' + [uri]::EscapeDataString($Region)
        return (Invoke-WebRequest -UseBasicParsing -Uri $url -TimeoutSec 10).Content.Trim()
    } catch {
        return 'Unknown'
    }
}

function Is-Online([string]$Status) {
    return @('booted','running','online') -contains $Status.Trim().ToLowerInvariant()
}

function Find-NewOar([string]$Region, [datetime]$Started) {
    if(!(Test-Path -LiteralPath $AutoBackupRoot)) { return $null }

    $dirs = Get-ChildItem -LiteralPath $AutoBackupRoot -Directory -Filter 'AutoBackup-*' -ErrorAction SilentlyContinue |
        Sort-Object Name -Descending

    foreach($d in $dirs) {
        $oarDir = Join-Path $d.FullName 'OAR'
        if(!(Test-Path -LiteralPath $oarDir)) { continue }

        $f = Get-ChildItem -LiteralPath $oarDir -File -Filter "$Region`_*.oar" -ErrorAction SilentlyContinue |
            Where-Object { $_.LastWriteTime -ge $Started.AddSeconds(-10) } |
            Sort-Object LastWriteTime -Descending |
            Select-Object -First 1

        if($f) { return $f }
    }
    return $null
}

function Wait-OarComplete([string]$Region, [datetime]$Started) {
    $lastSize = -1L
    $stable = 0
    $maxPolls = 2880 # four hours at five seconds

    for($i=0; $i -lt $maxPolls; $i++) {
        $f = Find-NewOar -Region $Region -Started $Started
        if($f) {
            $size = [int64]$f.Length
            if($size -gt 0 -and $size -eq $lastSize) { $stable++ } else { $stable = 0 }
            $lastSize = $size

            if($size -gt 0 -and $stable -ge 12) {
                return $f
            }
        }
        Start-Sleep -Seconds 5
    }
    throw "Timed out waiting for $Region OAR to finish"
}

function Apply-ScheduledRetention(
    [string]$Region,
    [int]$KeepLast,
    [System.IO.FileInfo]$CurrentFile
) {
    if($KeepLast -lt 1) {
        $KeepLast = 1
    }

    $historyFile =
        Join-Path $JobsDir 'backup_history.json'

    #
    # Only successful OAR backups explicitly recorded as
    # SCHEDULED are eligible for automatic retention.
    #
    # Manual DreamGrid backups, Grid Backup button backups,
    # and unidentified OAR files are protected.
    #
    $scheduledNames = @{}

    if(Test-Path -LiteralPath $historyFile) {

        try {

            $rawHistory =
                Get-Content `
                    -LiteralPath $historyFile `
                    -Raw

            $historyRows = @()

            if(
                -not [string]::IsNullOrWhiteSpace(
                    $rawHistory
                )
            ) {

                $historyRows =
                    @(
                        $rawHistory |
                        ConvertFrom-Json
                    )
            }

            foreach($row in $historyRows) {

                $rowType =
                    ([string]$row.type).ToUpperInvariant()

                if($rowType -ne 'OAR') {
                    continue
                }

                $rowSource =
                    ([string]$row.source).ToUpperInvariant()

                if($rowSource -ne 'SCHEDULED') {
                    continue
                }

                $rowStatus =
                    ([string]$row.status).ToUpperInvariant()

                if($rowStatus -ne 'SUCCESS') {
                    continue
                }

                $rowSubject =
                    [string]$row.subject

                if(
                    -not $rowSubject.Equals(
                        $Region,
                        [System.StringComparison]::OrdinalIgnoreCase
                    )
                ) {
                    continue
                }

                $rowFile =
                    [string]$row.file

                if(
                    [string]::IsNullOrWhiteSpace(
                        $rowFile
                    )
                ) {
                    continue
                }

                $key =
                    $rowFile.Trim().ToLowerInvariant()

                $scheduledNames[$key] =
                    $true
            }
        }
        catch {

            Log "RETENTION history read failed region=$Region message=$($_.Exception.Message)"
        }
    }


    #
    # Current scheduled backup is not yet in history because
    # retention happens immediately before Add-History.
    #
    if($null -ne $CurrentFile) {

        $currentName =
            [string]$CurrentFile.Name

        if(
            -not [string]::IsNullOrWhiteSpace(
                $currentName
            )
        ) {

            $currentKey =
                $currentName.Trim().ToLowerInvariant()

            $scheduledNames[$currentKey] =
                $true
        }
    }


    $files = @()


    if(Test-Path -LiteralPath $AutoBackupRoot) {

        $backupDirs =
            Get-ChildItem `
                -LiteralPath $AutoBackupRoot `
                -Directory `
                -Filter 'AutoBackup-*' `
                -ErrorAction SilentlyContinue

        foreach($d in $backupDirs) {

            $oarDir =
                Join-Path `
                    $d.FullName `
                    'OAR'

            if(
                -not (
                    Test-Path `
                        -LiteralPath $oarDir
                )
            ) {
                continue
            }

            $regionFiles =
                Get-ChildItem `
                    -LiteralPath $oarDir `
                    -File `
                    -Filter "$Region`_*.oar" `
                    -ErrorAction SilentlyContinue

            foreach($f in $regionFiles) {

                $fileKey =
                    $f.Name.ToLowerInvariant()

                if(
                    $scheduledNames.ContainsKey(
                        $fileKey
                    )
                ) {
                    $files += $f
                }
            }
        }
    }


    $files =
        @(
            $files |
            Sort-Object LastWriteTime -Descending
        )


    $old =
        @(
            $files |
            Select-Object -Skip $KeepLast
        )


    foreach($f in $old) {

        Log "SCHEDULED RETENTION deleting old scheduled backup: $($f.FullName)"

        Remove-Item `
            -LiteralPath $f.FullName `
            -Force
    }


    return $old.Count
}

function Get-ScheduledSlot($Cfg, [datetime]$Now) {
    $timeParts = [string]$Cfg.time -split ':'
    if($timeParts.Count -ne 2) { return $null }
    $hh = [int]$timeParts[0]
    $mm = [int]$timeParts[1]

    $candidate = Get-Date -Year $Now.Year -Month $Now.Month -Day $Now.Day -Hour $hh -Minute $mm -Second 0
    $dow = [int]$candidate.DayOfWeek # Sunday=0

    switch([string]$Cfg.type) {
        'daily' { $allowed = $true }
        'weekly' { $allowed = @($Cfg.days) -contains $dow }
        'selected' { $allowed = @($Cfg.days) -contains $dow }
        default { $allowed = $false }
    }
    if(!$allowed) { return $null }

    # Task checks every 5 minutes. Only claim today's slot if within 12 minutes
    # after the requested time, so installing V57 later cannot trigger an old slot.
    $delta = ($Now - $candidate).TotalMinutes
    if($delta -ge 0 -and $delta -le 12) { return $candidate }
    return $null
}

$state = Read-State
$state.lastCheck = (Get-Date).ToString('o')
$state.lastCheckResult = 'Checking schedule'
Write-State $state

if(!(Test-Path -LiteralPath $ConfigFile)) {
    $state.lastCheckResult = 'No schedule configuration found'
    Write-State $state
    exit 0
}

try {
    $cfg = Get-Content -LiteralPath $ConfigFile -Raw | ConvertFrom-Json
} catch {
    $state.lastCheckResult = "Invalid schedule configuration: $($_.Exception.Message)"
    Write-State $state
    Log $state.lastCheckResult
    exit 1
}

if(-not $ForceRun -and -not [bool]$cfg.enabled) {
    $state.lastCheckResult = 'Scheduler disabled'
    Write-State $state
    exit 0
}

$slot = if($ForceRun) { Get-Date } else { Get-ScheduledSlot -Cfg $cfg -Now (Get-Date) }
if(!$slot) {
    $state.lastCheckResult = 'Checked - not due'
    Write-State $state
    exit 0
}

$slotKey = $slot.ToString('yyyy-MM-ddTHH:mm')
if(-not $ForceRun -and [string]$state.lastSlot -eq $slotKey) {
    $state.lastCheckResult = 'Checked - slot already completed/attempted'
    Write-State $state
    exit 0
}

if(!(Acquire-Lock)) { exit 0 }

try {
    # Claim the slot before starting so another five-minute task instance cannot duplicate it.
    $state.lastSlot = $slotKey
    $state.lastRun = (Get-Date).ToString('o')
    $state.lastResult = 'RUNNING'
    $state.lastCheckResult = 'Backup running'
    $state.lastBackupRun = $state.lastRun
    $state.lastBackupFinished = $null
    $state.lastBackupResult = 'RUNNING'
    $state.lastBackupCompleted = 0
    $state.lastBackupSkipped = 0
    $state.lastBackupErrors = 0
    $state.lastBackupRegions = @()
    $state.completed = 0
    $state.skipped = 0
    $state.errors = 0
    $state.regions = @()
    Write-State $state

    $regions = @($cfg.regions)
    $keepLast = [int]$cfg.keepLast
    if($regions.Count -eq 0) { throw 'No regions selected in schedule' }

    $machineHash = Get-MachineHash
    Log "SCHEDULE START slot=$slotKey regions=$($regions.Count) keepLast=$keepLast"

    foreach($region in $regions) {
        $entry = @{ region=[string]$region; status='STARTING'; message=''; file=''; size=0 }
        $state.regions += $entry
        Write-State $state

        $status = Get-RegionStatus -Region ([string]$region)
        if(!(Is-Online $status)) {
            $entry.status = 'SKIPPED'
            $entry.message = "Region offline ($status)"
            $state.skipped++
            Write-State $state
            Add-History -Type 'OAR' -Source 'SCHEDULED' -Status 'SKIPPED' -Subject ([string]$region) -File '' -Size 0 -Message $entry.message -When (Get-Date)
            Log "SKIP region=$region status=$status"
            continue
        }

        try {
            $started = Get-Date
            $entry.status = 'BACKING UP'
            Write-State $state

            $resp = Invoke-DreamGrid @{
                command='SaveOAR'
                password=$machineHash
                RegionName=[string]$region
            }
            if($resp -match '^NAK') { throw "DreamGrid rejected SaveOAR: $resp" }

            Log "START region=$region response=$resp"
            $file = Wait-OarComplete -Region ([string]$region) -Started $started

            $entry.status = 'COMPLETE'
            $entry.file = $file.Name
            $entry.size = [int64]$file.Length
            $entry.message = 'Backup complete'
            $state.completed++

            $removed = Apply-ScheduledRetention -Region ([string]$region) -KeepLast $keepLast -CurrentFile $file
            if($removed -gt 0) { $entry.message = "Backup complete; scheduled retention removed $removed old scheduled backup(s)" }

            Write-State $state
            Add-History -Type 'OAR' -Source 'SCHEDULED' -Status 'SUCCESS' -Subject ([string]$region) -File $file.Name -Size ([int64]$file.Length) -Message $entry.message -When $started
            Log "COMPLETE region=$region file=$($file.Name) bytes=$($file.Length) retentionRemoved=$removed"
        } catch {
            $entry.status = 'ERROR'
            $entry.message = $_.Exception.Message
            $state.errors++
            Write-State $state
            Add-History -Type 'OAR' -Source 'SCHEDULED' -Status 'FAILED' -Subject ([string]$region) -File '' -Size 0 -Message $entry.message -When $started
            Log "ERROR region=$region message=$($_.Exception.Message)"
        }
    }

    $state.lastResult = "FINISHED completed=$($state.completed) skipped=$($state.skipped) errors=$($state.errors)"
    $state.lastCheckResult = 'Backup finished'
    $state.lastBackupFinished = (Get-Date).ToString('o')
    $state.lastBackupResult = $state.lastResult
    $state.lastBackupCompleted = $state.completed
    $state.lastBackupSkipped = $state.skipped
    $state.lastBackupErrors = $state.errors
    $state.lastBackupRegions = @($state.regions)
    Write-State $state
    Log $state.lastResult
} catch {
    $state.lastResult = "SCHEDULER ERROR: $($_.Exception.Message)"
    $state.lastCheckResult = 'Scheduler error'
    $state.errors = [int]$state.errors + 1
    if($state.lastBackupRun) {
        $state.lastBackupFinished = (Get-Date).ToString('o')
        $state.lastBackupResult = $state.lastResult
        $state.lastBackupCompleted = $state.completed
        $state.lastBackupSkipped = $state.skipped
        $state.lastBackupErrors = $state.errors
        $state.lastBackupRegions = @($state.regions)
    }
    Write-State $state
    Log $state.lastResult
    exit 1
} finally {
    Release-Lock
}