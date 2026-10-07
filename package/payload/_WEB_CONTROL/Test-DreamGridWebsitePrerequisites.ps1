param([string]$Root)
$ErrorActionPreference='Stop'
if(-not $Root){
    $directory=[IO.DirectoryInfo]$PSScriptRoot
    while($directory -and -not (Test-Path -LiteralPath (Join-Path $directory.FullName 'Settings.ini'))){$directory=$directory.Parent}
    if(-not $directory){throw 'Specify -Root pointing to the existing configured DreamGrid folder containing Settings.ini.'}
    $Root=$directory.FullName
}
$Root=(Resolve-Path -LiteralPath $Root).Path
if($Root -match '[^\x00-\x7f]'){throw 'This release requires an ASCII Windows installation path; spaces are supported. Native Perl CGI failed direct Unicode-path verification.'}
$values=@{};$section=''
foreach($line in Get-Content -LiteralPath (Join-Path $Root 'Settings.ini')){
    if($line -match '^\s*\[([^]]+)\]\s*$'){$section=$Matches[1];continue}
    if($section -notin @('','Data')){continue}
    if($line -match '^\s*([^;#=]+?)\s*=\s*(.*?)\s*$'){
        $name=$Matches[1].Trim();$value=$Matches[2].Trim()
        if($value.Length -ge 2 -and (($value.StartsWith('"') -and $value.EndsWith('"')) -or ($value.StartsWith("'") -and $value.EndsWith("'")))){$value=$value.Substring(1,$value.Length-2)}
        $values[$name]=$value
    }
}
# DreamGrid stores the selected radio option as CMS. Its DIVA option stores
# "DreamGrid"; OTHER stores the folder selected in its OTHER field.
$cms=if($values.ContainsKey('CMS')){$values.CMS}else{'DreamGrid'}
$other=if($values.ContainsKey('OtherCMS')){$values.OtherCMS}else{'Other'}
if($cms -ne 'Other' -or $other -ne 'Other'){
    throw 'DreamGrid prerequisite not met. Open Setup > Settings > Apache Settings. Turn DIVA OFF, select/enable OTHER, and set the OTHER folder to Other. Save the setting, then stop DreamGrid and Apache before installing the website. No DreamGrid setting has been changed.'
}
$runtime=Get-ChildItem -LiteralPath $Root -Directory | Where-Object {$_.Name -match '^PHP\d+$' -and (Test-Path -LiteralPath (Join-Path $_.FullName 'php.exe'))}
if(-not (Test-Path -LiteralPath (Join-Path $Root 'Apache/bin/httpd.exe')) -or -not $runtime){throw 'Install and configure DreamGrid first: its existing Apache/PHP runtime was not found.'}
[pscustomobject]@{root=$Root;mode='OTHER';divaEnabled=$false;settingsChanged=$false;asciiPath=$true;existingRuntime=$true}
