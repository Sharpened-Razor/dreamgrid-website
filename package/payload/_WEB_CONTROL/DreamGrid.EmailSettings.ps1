param(
    [Parameter(Mandatory = $true)]
    [string]$StartDll,

    [Parameter(Mandatory = $true)]
    [string]$DreamGridRoot
)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest


function Write-JsonResult {

    param(
        [hashtable]$Value
    )

    [Console]::Out.WriteLine(
        (
            $Value |
            ConvertTo-Json `
                -Compress `
                -Depth 10
        )
    )
}


function Fail-Worker {

    param(
        [string]$Message
    )

    Write-JsonResult @{
        ok    = $false
        error = $Message
    }

    exit 1
}


try {

    $Raw =
        [Console]::In.ReadToEnd()


    if (
        [string]::IsNullOrWhiteSpace(
            $Raw
        )
    ) {
        Fail-Worker 'SMTP settings request was empty.'
    }


    $Request =
        $Raw |
        ConvertFrom-Json


    if (
        -not (
            Test-Path `
                -LiteralPath `
                $StartDll
        )
    ) {
        Fail-Worker 'Start.dll was not found.'
    }


    $Assembly =
        [Reflection.Assembly]::LoadFrom(
            $StartDll
        )


    $SettingsType =
        $Assembly.GetType(
            'Outworldz.ClassMySettings',
            $true
        )


    $Constructor =
        $SettingsType.
        GetConstructors() |
        Where-Object {

            $Parameters =
                $_.GetParameters()

            (
                $Parameters.Count -eq 1 -and
                $Parameters[0].ParameterType -eq [string]
            )
        } |
        Select-Object -First 1


    if (-not $Constructor) {
        Fail-Worker 'Could not locate Outworldz.ClassMySettings constructor.'
    }


    $Settings =
        $Constructor.Invoke(
            [object[]]@(
                $DreamGridRoot
            )
        )


    $Action =
        [string]$Request.action


    $PropertyNames = @(
        'EmailEnabled',
        'SmtPropUserName',
        'SmtpPassword',
        'SmtpHost',
        'SmtpPort',
        'SmtpSecure',
        'SslType',
        'VerifyCertCheckBox',
        'EnableEmailToExternalObjects',
        'OutboundEnabled',
        'MailsFromOwnerPerHour',
        'MailsToPrimAddressPerHour',
        'MailsPerDay',
        'EmailsToSmtpAddressPerHour',
        'EmailPauseTime',
        'MaxMailSize'
    )


    if ($Action -eq 'get') {

        $Values =
            [ordered]@{}


        foreach ($Name in $PropertyNames) {

            $Property =
                $SettingsType.GetProperty(
                    $Name,
                    [Reflection.BindingFlags]'Public,Instance'
                )


            if (-not $Property) {
                Fail-Worker "Start.dll setting property missing: $Name"
            }


            $Values[$Name] =
                $Property.GetValue(
                    $Settings
                )
        }


        $Password =
            [string]$Values['SmtpPassword']


        $Values.Remove(
            'SmtpPassword'
        )


        Write-JsonResult @{
            ok          = $true
            settings    = $Values
            passwordSet = (
                -not [string]::IsNullOrWhiteSpace($Password) -and
                $Password -ne 'Password'
            )
        }


        exit 0
    }


    if ($Action -ne 'save') {
        Fail-Worker 'Unsupported SMTP settings action.'
    }


    $SetMethod =
        $SettingsType.GetMethod(
            'SetMySetting',
            [Reflection.BindingFlags]'Public,Instance'
        )


    $SaveMethod =
        $SettingsType.GetMethod(
            'SaveSettings',
            [Reflection.BindingFlags]'Public,Instance'
        )


    if (
        -not $SetMethod -or
        -not $SaveMethod
    ) {
        Fail-Worker 'Start.dll settings methods could not be located.'
    }


    $Allowed =
        [System.Collections.Generic.HashSet[string]]::new(
            [StringComparer]::Ordinal
        )


    @(
        'EmailEnabled',
        'SmtPropUserName',
        'SmtpPassword',
        'SmtpHost',
        'SmtpPort',
        'SmtpSecure',
        'SSLType',
        'VerifyCertCheckBox',
        'enableEmailToExternalObjects',
        'OutboundEnabled',
        'MailsFromOwnerPerHour',
        'MailsToPrimAddressPerHour',
        'MailsPerDay',
        'EmailsToSMTPAddressPerHour',
        'EmailPauseTime',
        'MaxMailSize'
    ) |
    ForEach-Object {
        [void]$Allowed.Add($_)
    }


    foreach (
        $Setting in
        $Request.settings.PSObject.Properties
    ) {

        $Name =
            [string]$Setting.Name


        if (-not $Allowed.Contains($Name)) {
            Fail-Worker "Unsupported DreamGrid SMTP setting: $Name"
        }


        [void]$SetMethod.Invoke(
            $Settings,
            [object[]]@(
                $Name,
                [string]$Setting.Value
            )
        )
    }


    [void]$SaveMethod.Invoke(
        $Settings,
        @()
    )


    Write-JsonResult @{
        ok = $true
    }


    exit 0
}
catch {

    Fail-Worker (
        $_.Exception.Message
    )
}