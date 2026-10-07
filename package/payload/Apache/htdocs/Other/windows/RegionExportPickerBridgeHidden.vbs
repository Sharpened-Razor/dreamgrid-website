Option Explicit

Dim shell
Dim fso
Dim q
Dim scriptFolder
Dim ps1
Dim psExe
Dim programFiles
Dim systemRoot
Dim command
Dim result

q = Chr(34)

Set shell = CreateObject("WScript.Shell")
Set fso = CreateObject("Scripting.FileSystemObject")

scriptFolder = fso.GetParentFolderName(WScript.ScriptFullName)
ps1 = fso.BuildPath(scriptFolder, "RegionExportPickerBridge.ps1")

If Not fso.FileExists(ps1) Then
    WScript.Quit 10
End If

programFiles = shell.ExpandEnvironmentStrings("%ProgramFiles%")
psExe = fso.BuildPath(programFiles, "PowerShell\7\pwsh.exe")

If Not fso.FileExists(psExe) Then
    systemRoot = shell.ExpandEnvironmentStrings("%SystemRoot%")
    psExe = fso.BuildPath(systemRoot, "System32\WindowsPowerShell\v1.0\powershell.exe")
End If

If Not fso.FileExists(psExe) Then
    WScript.Quit 11
End If

command = q & psExe & q & _
          " -NoProfile -STA -ExecutionPolicy Bypass -File " & _
          q & ps1 & q

result = shell.Run(command, 0, True)

WScript.Quit result