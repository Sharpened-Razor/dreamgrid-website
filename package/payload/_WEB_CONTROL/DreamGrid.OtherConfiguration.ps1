# Shared, narrowly scoped fresh-site detection and native CMS/OtherCMS editing.
function Test-EmptyOtherWebsite([string]$Root){
    $folder=Join-Path $Root 'Apache/htdocs/Other'
    if(-not (Test-Path -LiteralPath $folder)){return $true}
    $pending=[Collections.Generic.Queue[IO.DirectoryInfo]]::new()
    $pending.Enqueue([IO.DirectoryInfo]$folder)
    while($pending.Count){
        $directory=$pending.Dequeue()
        if($directory.Attributes -band [IO.FileAttributes]::ReparsePoint){throw 'OTHER must not contain reparse points'}
        foreach($item in Get-ChildItem -LiteralPath $directory.FullName -Force -ErrorAction Stop){
            if($item.Attributes -band [IO.FileAttributes]::ReparsePoint){throw 'OTHER content must not contain reparse points'}
            if($item.PSIsContainer){$pending.Enqueue($item)}
            elseif(-not ($item.Name -in @('.keep','.gitkeep') -and $item.Length -le 4096)){return $false}
        }
    }
    return $true
}
function Get-OtherSettingsValues([string]$Text){
    $values=[Collections.Generic.Dictionary[string,string]]::new([StringComparer]::Ordinal);$section=''
    foreach($line in [regex]::Split($Text,'\r\n|\n|\r')){
        if($line -match '^\s*\[([^]]+)\]\s*$'){$section=$Matches[1];continue}
        if($section -cne 'Data'){continue}
        if($line -match '^\s*([^;#=]+?)\s*=\s*(.*?)\s*$'){
            $key=$Matches[1].Trim();$value=$Matches[2].Trim()
            if($key -cin @('CMS','OtherCMS','[Data]CMS','[Data]OtherCMS')){
                if($values.ContainsKey($key)){throw "Ambiguous duplicate DIVA/OTHER setting: $key"}
                $values.Add($key,$value)
            }
        }
    }
    return ,$values
}
function Get-EffectiveOtherValue($Values,[string]$Key,[string]$Default){
    $override='[Data]'+$Key
    $raw=if($Values.ContainsKey($override) -and $Values[$override].Length){$Values[$override]}elseif($Values.ContainsKey($Key)){$Values[$Key]}else{$Default}
    return $raw.Replace('"','').Trim()
}
function New-OtherSettingsPlan([string]$SettingsPath){
    $bytes=[IO.File]::ReadAllBytes($SettingsPath);$offset=0;$encoding=[Text.UTF8Encoding]::new($false,$true)
    if($bytes.Length -ge 3 -and $bytes[0] -eq 239 -and $bytes[1] -eq 187 -and $bytes[2] -eq 191){$offset=3}
    elseif($bytes.Length -ge 2 -and $bytes[0] -eq 255 -and $bytes[1] -eq 254){$offset=2;$encoding=[Text.UnicodeEncoding]::new($false,$false,$true)}
    elseif($bytes.Length -ge 2 -and $bytes[0] -eq 254 -and $bytes[1] -eq 255){$offset=2;$encoding=[Text.UnicodeEncoding]::new($true,$false,$true)}
    $text=$encoding.GetString($bytes,$offset,$bytes.Length-$offset);$values=Get-OtherSettingsValues $text
    $updates=[Collections.Generic.Dictionary[string,string]]::new([StringComparer]::Ordinal)
    foreach($key in @('CMS','OtherCMS')){
        $fallback=if($key -ceq 'CMS'){'DreamGrid'}else{'Other'}
        $plain=if($values.ContainsKey($key)){$values[$key].Replace('"','').Trim()}else{$fallback}
        if($plain -ne 'Other'){$updates.Add($key,'Other')}
        $over='[Data]'+$key
        if($values.ContainsKey($over) -and $values[$over].Length -and $values[$over].Replace('"','').Trim() -ne 'Other'){$updates.Add($over,'Other')}
    }
    $changes=@($updates.Keys|ForEach-Object {"[Data] $_ : $(if($values.ContainsKey($_)){$values[$_]}else{'(absent/default)'}) -> Other"})
    $pieces=[regex]::Split($text,'(\r\n|\n|\r)');$section='';$dataCount=0
    $remaining=[Collections.Generic.HashSet[string]]::new([StringComparer]::Ordinal)
    foreach($key in $updates.Keys){[void]$remaining.Add($key)}
    for($i=0;$i -lt $pieces.Length;$i+=2){
        if($pieces[$i] -match '^\s*\[([^]]+)\]\s*$'){$section=$Matches[1];if($section -ceq 'Data'){$dataCount++};continue}
        if($section -cne 'Data'){continue}
        $match=[regex]::Match($pieces[$i],'^(\s*)([^;#=]+?)(\s*=\s*)(.*?)(\s*)$')
        if($match.Success){$key=$match.Groups[2].Value.Trim();if($updates.ContainsKey($key)){
            $pieces[$i]=$match.Groups[1].Value+$match.Groups[2].Value+$match.Groups[3].Value+'Other'+$match.Groups[5].Value
            [void]$remaining.Remove($key)
        }}
    }
    if($dataCount -ne 1){throw 'Expected exactly one [Data] section; settings unchanged'}
    $updatedText=$pieces -join '';$newline=if($text.Contains("`r`n")){"`r`n"}else{"`n"}
    if($remaining.Count){
        $addition=($remaining|ForEach-Object {$_+'=Other'}) -join $newline
        $replace=[Text.RegularExpressions.MatchEvaluator]{param($m) $m.Groups[1].Value+$newline+$addition+$newline}
        $updatedText=[regex]::Replace($updatedText,'(?m)^([ \t]*\[Data\][ \t]*)(\r?\n|$)',$replace)
    }
    $content=$encoding.GetBytes($updatedText);$updated=[byte[]]::new($offset+$content.Length)
    if($offset){[Array]::Copy($bytes,0,$updated,0,$offset)};[Array]::Copy($content,0,$updated,$offset,$content.Length)
    $check=Get-OtherSettingsValues $updatedText
    if((Get-EffectiveOtherValue $check CMS DreamGrid) -ne 'Other' -or (Get-EffectiveOtherValue $check OtherCMS Other) -ne 'Other'){throw 'OTHER plan validation failed'}
    return [pscustomobject]@{path=$SettingsPath;original=$bytes;updated=$updated;changes=$changes}
}
function Set-VerifiedOtherSettings($Plan){
    $actual=[IO.File]::ReadAllBytes($Plan.path)
    if([Convert]::ToBase64String($actual) -cne [Convert]::ToBase64String($Plan.original)){throw 'Settings changed since the fresh-install plan; no setting was written'}
    [IO.File]::WriteAllBytes($Plan.path,$Plan.updated)
    if([Convert]::ToBase64String([IO.File]::ReadAllBytes($Plan.path)) -cne [Convert]::ToBase64String($Plan.updated)){throw 'OTHER settings write verification failed'}
}
