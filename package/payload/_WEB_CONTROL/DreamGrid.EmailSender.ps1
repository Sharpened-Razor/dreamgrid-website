param(
    [Parameter(Mandatory = $true)]
    [string]$StartDll
)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

function Write-Result {
    param(
        [hashtable]$Value
    )

    [Console]::Out.WriteLine(
        (
            $Value |
            ConvertTo-Json `
                -Compress `
                -Depth 8
        )
    )
}

function Fail-Email {
    param(
        [string]$Message
    )

    Write-Result @{
        ok    = $false
        error = $Message
    }

    exit 1
}

$Raw =
    [Console]::In.ReadToEnd()

if ([string]::IsNullOrWhiteSpace($Raw)) {
    Fail-Email 'Email payload was empty.'
}

try {

    $Data =
        $Raw |
        ConvertFrom-Json

}
catch {

    Fail-Email 'Email payload was invalid.'
}


$HostName =
    [string]$Data.host

$UserName =
    [string]$Data.username

$Password =
    [string]$Data.password

$FromName =
    [string]$Data.fromName

$Subject =
    [string]$Data.subject

$Body =
    [string]$Data.message

$Port =
    [int]$Data.port

$Secure =
    [string]$Data.secure

$VerifyCertificate =
    [bool]$Data.verifyCertificate


if (
    [string]::IsNullOrWhiteSpace($HostName) -or
    [string]::IsNullOrWhiteSpace($UserName)
) {
    Fail-Email 'DreamGrid SMTP account is incomplete.'
}


$Recipients =
    @(
        $Data.recipients
    )


if ($Recipients.Count -lt 1) {
    Fail-Email 'No email recipients were supplied.'
}


if (-not (Test-Path -LiteralPath $StartDll)) {
    Fail-Email "DreamGrid Start.dll not found."
}


$TempDir =
    Join-Path `
        $env:TEMP `
        (
            'DreamGrid-Mail-' +
            [Guid]::NewGuid().ToString('N')
        )


New-Item `
    -ItemType Directory `
    -Path $TempDir `
    -Force |
    Out-Null


try {

    # ========================================================
    # TRY DREAMGRID'S EMBEDDED MAILKIT / MIMEKIT FIRST
    # ========================================================

    $MailKitWorked =
        $false


    try {

        $StartAssembly =
            [Reflection.Assembly]::LoadFrom(
                $StartDll
            )


        function Extract-EmbeddedAssembly {
            param(
                [Parameter(Mandatory = $true)]
                [string]$Suffix,

                [Parameter(Mandatory = $true)]
                [string]$FileName
            )

            $ResourceName =
                $StartAssembly.
                GetManifestResourceNames() |
                Where-Object {
                    $_ -like "*$Suffix"
                } |
                Select-Object -First 1


            if (-not $ResourceName) {
                throw "Embedded assembly $Suffix was not found."
            }


            $Stream =
                $StartAssembly.
                GetManifestResourceStream(
                    $ResourceName
                )


            if (-not $Stream) {
                throw "Could not read $ResourceName."
            }


            $Target =
                Join-Path `
                    $TempDir `
                    $FileName


            $FileStream =
                [IO.File]::Create(
                    $Target
                )


            try {

                $Stream.CopyTo(
                    $FileStream
                )

            }
            finally {

                $FileStream.Dispose()
                $Stream.Dispose()
            }


            return $Target
        }


        $Bouncy =
            Extract-EmbeddedAssembly `
                -Suffix 'Embedded_BouncyCastle_Cryptography.bin' `
                -FileName 'BouncyCastle.Cryptography.dll'


        $MimeKit =
            Extract-EmbeddedAssembly `
                -Suffix 'Embedded_MimeKit.bin' `
                -FileName 'MimeKit.dll'


        $MailKit =
            Extract-EmbeddedAssembly `
                -Suffix 'Embedded_MailKit.bin' `
                -FileName 'MailKit.dll'


        [void][Reflection.Assembly]::LoadFrom(
            $Bouncy
        )

        [void][Reflection.Assembly]::LoadFrom(
            $MimeKit
        )

        [void][Reflection.Assembly]::LoadFrom(
            $MailKit
        )


        $MimeMessage =
            [MimeKit.MimeMessage]::new()


        $MimeMessage.From.Add(
            [MimeKit.MailboxAddress]::new(
                $FromName,
                $UserName
            )
        )


        foreach ($Recipient in $Recipients) {

            $RecipientName =
                [string]$Recipient.name

            $RecipientEmail =
                [string]$Recipient.email


            if (
                [string]::IsNullOrWhiteSpace(
                    $RecipientEmail
                )
            ) {
                continue
            }


            $MimeMessage.Bcc.Add(
                [MimeKit.MailboxAddress]::new(
                    $RecipientName,
                    $RecipientEmail
                )
            )
        }


        if ($MimeMessage.Bcc.Count -lt 1) {
            throw 'No valid BCC recipients remained.'
        }


        $MimeMessage.Subject =
            $Subject


        $TextPart =
            [MimeKit.TextPart]::new(
                'plain'
            )

        $TextPart.Text =
            $Body

        $MimeMessage.Body =
            $TextPart


        $SocketOption =
            [MailKit.Security.SecureSocketOptions]::Auto


        $SecureText =
            $Secure.
            Trim().
            ToLowerInvariant()


        switch -Regex ($SecureText) {

            '^none$|^0$' {

                $SocketOption =
                    [MailKit.Security.SecureSocketOptions]::None
            }

            '^auto(matic)?$|^1$' {

                $SocketOption =
                    [MailKit.Security.SecureSocketOptions]::Auto
            }

            'ssl|connect|^2$' {

                $SocketOption =
                    [MailKit.Security.SecureSocketOptions]::SslOnConnect
            }

            '^starttls$|^start tls$|^3$' {

                $SocketOption =
                    [MailKit.Security.SecureSocketOptions]::StartTls
            }

            'available|whenavailable|when available|^4$' {

                $SocketOption =
                    [MailKit.Security.SecureSocketOptions]::StartTlsWhenAvailable
            }

            default {

                if ($Port -eq 465) {

                    $SocketOption =
                        [MailKit.Security.SecureSocketOptions]::SslOnConnect

                }
                elseif ($Port -eq 25) {

                    $SocketOption =
                        [MailKit.Security.SecureSocketOptions]::Auto

                }
                else {

                    $SocketOption =
                        [MailKit.Security.SecureSocketOptions]::StartTls
                }
            }
        }


        $Client =
            [MailKit.Net.Smtp.SmtpClient]::new()


        try {

            if (-not $VerifyCertificate) {

                $Client.ServerCertificateValidationCallback = {
                    param(
                        $sender,
                        $certificate,
                        $chain,
                        $sslPolicyErrors
                    )

                    return $true
                }
            }


            $Client.Connect(
                $HostName,
                $Port,
                $SocketOption
            )


            if (
                -not [string]::IsNullOrWhiteSpace(
                    $UserName
                )
            ) {

                $Client.Authenticate(
                    $UserName,
                    $Password
                )
            }


            [void]$Client.Send(
                $MimeMessage
            )


            $Client.Disconnect(
                $true
            )


            $MailKitWorked =
                $true

        }
        finally {

            $Client.Dispose()
        }

    }
    catch {

        $MailKitError =
            $_.Exception.Message
    }


    # ========================================================
    # FALLBACK FOR STANDARD STARTTLS SMTP
    # ========================================================

    if (-not $MailKitWorked) {

        try {

            $Mail =
                [System.Net.Mail.MailMessage]::new()


            try {

                $Mail.From =
                    [System.Net.Mail.MailAddress]::new(
                        $UserName,
                        $FromName
                    )


                foreach ($Recipient in $Recipients) {

                    $RecipientName =
                        [string]$Recipient.name

                    $RecipientEmail =
                        [string]$Recipient.email


                    if (
                        [string]::IsNullOrWhiteSpace(
                            $RecipientEmail
                        )
                    ) {
                        continue
                    }


                    $Mail.Bcc.Add(
                        [System.Net.Mail.MailAddress]::new(
                            $RecipientEmail,
                            $RecipientName
                        )
                    )
                }


                if ($Mail.Bcc.Count -lt 1) {
                    throw 'No valid BCC recipients remained.'
                }


                $Mail.Subject =
                    $Subject

                $Mail.Body =
                    $Body

                $Mail.IsBodyHtml =
                    $false


                $Smtp =
                    [System.Net.Mail.SmtpClient]::new(
                        $HostName,
                        $Port
                    )


                try {

                    $Smtp.UseDefaultCredentials =
                        $false

                    $Smtp.Credentials =
                        [Net.NetworkCredential]::new(
                            $UserName,
                            $Password
                        )


                    $SecureText =
                        $Secure.
                        Trim().
                        ToLowerInvariant()


                    $Smtp.EnableSsl =
                        -not (
                            $SecureText -eq 'none' -or
                            $SecureText -eq '0'
                        )


                    $Smtp.Send(
                        $Mail
                    )

                }
                finally {

                    $Smtp.Dispose()
                }

            }
            finally {

                $Mail.Dispose()
            }

        }
        catch {

            $FallbackError =
                $_.Exception.Message


            Fail-Email (
                'DreamGrid SMTP send failed. ' +
                'MailKit: ' +
                $MailKitError +
                ' | Fallback: ' +
                $FallbackError
            )
        }
    }


    Write-Result @{
        ok   = $true
        sent = $Recipients.Count
    }

}
finally {

    Remove-Item `
        -LiteralPath $TempDir `
        -Recurse `
        -Force `
        -ErrorAction SilentlyContinue
}