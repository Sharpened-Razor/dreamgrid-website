Option Explicit

Dim shell
Dim fso
Dim scriptFolder
Dim ps1
Dim command
Dim q
Dim result

q = Chr(34)

Set fso = CreateObject("Scripting.FileSystemObject")

scriptFolder = fso.GetParentFolderName(WScript.ScriptFullName)

ps1 = fso.BuildPath(scriptFolder, "RegionSettingsBridge.ps1")

If Not fso.FileExists(ps1) Then
    WScript.Quit 10
End If

command = _
    "powershell.exe -NoProfile -WindowStyle Hidden -ExecutionPolicy Bypass -File " & _
    q & ps1 & q

Set shell = CreateObject("WScript.Shell")

result = shell.Run(command, 0, True)

WScript.Quit result
