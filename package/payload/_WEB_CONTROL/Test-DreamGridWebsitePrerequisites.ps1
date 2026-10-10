param([string]$Root,[switch]$AllowEmptyOther)
$ErrorActionPreference='Stop'
if(-not $Root){
    $directory=[IO.DirectoryInfo]$PSScriptRoot
    while($directory -and -not (Test-Path -LiteralPath (Join-Path $directory.FullName 'Settings.ini'))){$directory=$directory.Parent}
    if(-not $directory){throw 'Specify -Root pointing to the existing configured DreamGrid folder containing Settings.ini.'}
    $Root=$directory.FullName
}
$Root=(Resolve-Path -LiteralPath $Root).Path
. (Join-Path $PSScriptRoot 'DreamGrid.OtherConfiguration.ps1')
$stageFirst=$false
if($AllowEmptyOther){if(-not (Test-EmptyOtherWebsite $Root)){throw 'Fresh-install staging refused: Other contains existing content. Use the normal backed-up installation path.'};$stageFirst=$true}
if($Root -match '[^\x00-\x7f]'){throw 'This release requires an ASCII Windows installation path; spaces are supported. Native Perl CGI failed direct Unicode-path verification.'}
$values=[Collections.Generic.Dictionary[string,string]]::new([StringComparer]::Ordinal);$section=''
foreach($line in Get-Content -LiteralPath (Join-Path $Root 'Settings.ini')){
    if($line -match '^\s*\[([^]]+)\]\s*$'){$section=$Matches[1];continue}
    if($section -cne 'Data'){continue}
    if($line -match '^\s*([^;#=]+?)\s*=\s*(.*?)\s*$'){
        $name=$Matches[1].Trim();$value=$Matches[2].Trim()
        $values[$name]=$value
    }
}
# DreamGrid stores the selected radio option as CMS. Its DIVA option stores
# "DreamGrid"; OTHER stores the folder selected in its OTHER field.
# 7.2115 LoadIni.GetIni gives nonempty [Data]CMS / [Data]OtherCMS overrides
# in the [Data] section precedence over the plain keys. Global keys are ignored.
$cms=if($values.ContainsKey('[Data]CMS') -and $values['[Data]CMS'].Length){$values['[Data]CMS']}elseif($values.ContainsKey('CMS')){$values['CMS']}else{'DreamGrid'}
$other=if($values.ContainsKey('[Data]OtherCMS') -and $values['[Data]OtherCMS'].Length){$values['[Data]OtherCMS']}elseif($values.ContainsKey('OtherCMS')){$values['OtherCMS']}else{'Other'}
$cms=$cms.Replace('"','').Trim();$other=$other.Replace('"','').Trim()
if(-not $stageFirst -and ($cms -ne 'Other' -or $other -ne 'Other')){
    throw 'DreamGrid prerequisite not met. Open Setup > Settings > Apache Settings. Turn DIVA OFF, select/enable OTHER, and set the OTHER folder to Other. Save the setting and verify it persists. DreamGrid 7.2115 may fail to save the OTHER radio selection; the setup candidate offers an explicit backed-up Configure DreamGrid for OTHER action. Stop DreamGrid and Apache before installing the website. No DreamGrid setting has been changed.'
}
$runtime=Get-ChildItem -LiteralPath $Root -Directory | Where-Object {$_.Name -match '^PHP\d+$' -and (Test-Path -LiteralPath (Join-Path $_.FullName 'php.exe'))}
if(-not (Test-Path -LiteralPath (Join-Path $Root 'Apache/bin/httpd.exe')) -or -not $runtime){throw 'Install and configure DreamGrid first: its existing Apache/PHP runtime was not found.'}
[pscustomobject]@{root=$Root;mode=if($stageFirst){'STAGE-FIRST'}else{'OTHER'};divaEnabled=($cms -eq 'DreamGrid');settingsChanged=$false;asciiPath=$true;existingRuntime=$true}
