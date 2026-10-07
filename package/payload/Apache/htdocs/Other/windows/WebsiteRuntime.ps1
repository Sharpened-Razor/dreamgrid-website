function Get-DgWebsiteRuntime {
    param([string]$StartDirectory=$PSScriptRoot)
    $directory=[IO.DirectoryInfo]$StartDirectory
    while($directory -and -not (Test-Path -LiteralPath (Join-Path $directory.FullName 'Settings.ini'))){$directory=$directory.Parent}
    if(-not $directory){throw 'DreamGrid settings were not found.'}
    $php=Join-Path $directory.FullName 'PHP8\php.exe'
    $helper=Join-Path $directory.FullName '_WEB_CONTROL\DreamGrid.WebsiteEnvironment.php'
    if(-not (Test-Path -LiteralPath $php) -or -not (Test-Path -LiteralPath $helper)){throw 'Website environment runtime is unavailable.'}
    $output=& $php -n $helper
    if($LASTEXITCODE -ne 0){throw 'Website environment could not be read.'}
    return ($output -join "`n" | ConvertFrom-Json)
}
