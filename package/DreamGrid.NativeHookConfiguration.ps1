# Strict JSON parsing with token offsets lets the installer change only the hook
# value, preserving every unrelated character, BOM and newline. No runtime code is loaded.
function Read-HookJson([string]$Text){
    if($Text.Length -gt 1048576){throw 'Runtime configuration exceeds supported size'}
    $cursor=@{position=0}
    function Skip-HookWhitespace {while($cursor.position -lt $Text.Length -and $Text[$cursor.position] -match '[ \t\r\n]'){$cursor.position++}}
    function Read-HookNode([int]$Depth){
        if($Depth -gt 64){throw 'Runtime configuration nesting is unsupported'}
        Skip-HookWhitespace
        $start=$cursor.position
        if($start -ge $Text.Length){throw 'Incomplete runtime JSON'}
        $ch=$Text[$start]
        $members=[Collections.Generic.Dictionary[string,object]]::new([StringComparer]::OrdinalIgnoreCase)
        $value=$null;$kind=''
        if($ch -eq '{' -or $ch -eq '['){
            $kind=if($ch -eq '{'){'object'}else{'array'}
            $end=if($ch -eq '{'){'}'}else{']'}
            $cursor.position++;Skip-HookWhitespace
            if($cursor.position -lt $Text.Length -and $Text[$cursor.position] -ne $end){
                while($true){
                    if($kind -eq 'object'){
                        $key=Read-HookNode ($Depth+1)
                        if($key.kind -ne 'string'){throw 'JSON object key must be a string'}
                        Skip-HookWhitespace
                        if($cursor.position -ge $Text.Length -or $Text[$cursor.position] -ne ':'){throw 'Missing JSON colon'}
                        $cursor.position++
                        if($members.ContainsKey($key.value)){throw 'Duplicate/ambiguous runtime JSON property'}
                        $members.Add($key.value,(Read-HookNode ($Depth+1)))
                    }else{$ignored=Read-HookNode ($Depth+1)}
                    Skip-HookWhitespace
                    if($cursor.position -ge $Text.Length){throw 'Incomplete runtime JSON container'}
                    if($Text[$cursor.position] -eq $end){break}
                    if($Text[$cursor.position] -ne ','){throw 'Missing JSON comma'}
                    $cursor.position++;Skip-HookWhitespace
                    if($cursor.position -ge $Text.Length -or $Text[$cursor.position] -eq $end){throw 'Trailing JSON comma'}
                }
            }
            if($cursor.position -ge $Text.Length -or $Text[$cursor.position] -ne $end){throw 'Incomplete runtime JSON container'}
            $cursor.position++
        }elseif($ch -eq '"'){
            $match=[regex]::Match($Text.Substring($start),'^"(?:[^"\\\x00-\x1f]|\\["\\/bfnrt]|\\u[0-9a-fA-F]{4})*"')
            if(-not $match.Success){throw 'Invalid runtime JSON string'}
            $value=ConvertFrom-Json -InputObject $match.Value
            $cursor.position+=$match.Length;$kind='string'
        }else{
            $match=[regex]::Match($Text.Substring($start),'^(?:true|false|null|-?(?:0|[1-9][0-9]*)(?:\.[0-9]+)?(?:[eE][+-]?[0-9]+)?)')
            if(-not $match.Success){throw 'Invalid runtime JSON token'}
            $cursor.position+=$match.Length;$kind='scalar'
        }
        return [pscustomobject]@{kind=$kind;start=$start;finish=$cursor.position;members=$members;value=$value}
    }
    $root=Read-HookNode 0;Skip-HookWhitespace
    if($cursor.position -ne $Text.Length -or $root.kind -ne 'object'){throw 'Runtime JSON must be one object'}
    return $root
}
function Get-HookOptions($Tree){
    if(-not ($Tree.members.Keys -ccontains 'runtimeOptions')){throw 'runtimeOptions property casing is unsupported'}
    $options=$Tree.members['runtimeOptions']
    if(-not $options -or $options.kind -ne 'object'){throw 'runtimeOptions must be an object'}
    $tfm=$options.members['tfm']
    if(-not $tfm -or $tfm.kind -ne 'string' -or $tfm.value -notmatch '^net9\.0(?:-windows.*)?$'){throw 'This NativeBridge build requires DreamGrid .NET 9; unsupported runtime rejected'}
    $properties=$options.members['configProperties']
    if($properties -and -not ($options.members.Keys -ccontains 'configProperties')){throw 'configProperties property casing is unsupported'}
    if($properties -and $properties.kind -ne 'object'){throw 'configProperties must be an object'}
    if($properties -and $properties.members.ContainsKey('STARTUP_HOOKS') -and $properties.members['STARTUP_HOOKS'].kind -ne 'string'){throw 'STARTUP_HOOKS must be a string'}
    if($properties -and $properties.members.ContainsKey('STARTUP_HOOKS') -and -not ($properties.members.Keys -ccontains 'STARTUP_HOOKS')){throw 'STARTUP_HOOKS property casing is unsupported'}
    return $options
}
function New-NativeHookPlan([string]$Path,[string]$Hook){
    if(-not [IO.Path]::IsPathRooted($Hook) -or $Hook.Contains(';')){throw 'NativeBridge hook requires an absolute path without semicolons'}
    $original=[IO.File]::ReadAllBytes($Path);$offset=0
    if($original.Length -ge 3 -and $original[0] -eq 239 -and $original[1] -eq 187 -and $original[2] -eq 191){$offset=3}
    $encoding=[Text.UTF8Encoding]::new($false,$true)
    $text=$encoding.GetString($original,$offset,$original.Length-$offset)
    $tree=Read-HookJson $text;$options=Get-HookOptions $tree
    $properties=$options.members['configProperties'];$node=if($properties){$properties.members['STARTUP_HOOKS']}else{$null}
    $others=[Collections.Generic.List[string]]::new()
    if($node -and $node.value -ne ''){
        foreach($entry in ($node.value -split ';')){
            if(-not $entry.Trim()){throw 'Empty startup-hook entry is unsupported'}
            if([IO.Path]::GetFileName($entry) -ine 'DreamGrid.NativeBridge.StartupHook.dll'){$others.Add($entry)}
        }
    }
    $expected=(@($others.ToArray())+@($Hook)) -join ';'
    if($node -and $node.value -ceq $expected){$updated=$original}
    else{
        $quoted=ConvertTo-Json -InputObject $expected -Compress
        if($node){$updatedText=$text.Substring(0,$node.start)+$quoted+$text.Substring($node.finish)}
        else{
            $container=if($properties){$properties}else{$options}
            $addition='"STARTUP_HOOKS":'+$quoted
            if(-not $properties){$addition='"configProperties":{'+$addition+'}'}
            if($container.members.Count){$addition=','+$addition}
            $position=$container.finish-1
            $updatedText=$text.Substring(0,$position)+$addition+$text.Substring($position)
        }
        $validated=Get-HookOptions (Read-HookJson $updatedText)
        if($validated.members['configProperties'].members['STARTUP_HOOKS'].value -cne $expected){throw 'Planned startup hook validation failed'}
        $body=$encoding.GetBytes($updatedText);$updated=[byte[]]::new($offset+$body.Length)
        if($offset){[Array]::Copy($original,0,$updated,0,$offset)}
        [Array]::Copy($body,0,$updated,$offset,$body.Length)
    }
    return [pscustomobject]@{path=$Path;original=$original;updated=$updated;expected=$expected;changed=([Convert]::ToBase64String($original) -cne [Convert]::ToBase64String($updated))}
}
function Set-VerifiedNativeHook($Plan){
    if([Convert]::ToBase64String([IO.File]::ReadAllBytes($Plan.path)) -cne [Convert]::ToBase64String($Plan.original)){throw 'Runtime configuration changed since planning'}
    if(-not $Plan.changed){return}
    $temporary=$Plan.path+'.website-'+[Guid]::NewGuid().ToString('N')+'.tmp'
    try{
        [IO.File]::WriteAllBytes($temporary,$Plan.updated)
        $options=Get-HookOptions (Read-HookJson ([IO.File]::ReadAllText($temporary)))
        if($options.members['configProperties'].members['STARTUP_HOOKS'].value -cne $Plan.expected){throw 'Staged hook verification failed'}
        [IO.File]::Replace($temporary,$Plan.path,[System.Management.Automation.Language.NullString]::Value)
        if([Convert]::ToBase64String([IO.File]::ReadAllBytes($Plan.path)) -cne [Convert]::ToBase64String($Plan.updated)){throw 'Runtime configuration write verification failed'}
        $options=Get-HookOptions (Read-HookJson ([IO.File]::ReadAllText($Plan.path)))
        if($options.members['configProperties'].members['STARTUP_HOOKS'].value -cne $Plan.expected){throw 'Installed hook verification failed'}
    }finally{if(Test-Path -LiteralPath $temporary){Remove-Item -LiteralPath $temporary}}
}
