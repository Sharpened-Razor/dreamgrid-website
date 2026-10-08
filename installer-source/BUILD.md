Build DreamGrid Website Setup 1.0.2 on Windows with .NET SDK 9.0.318 (target .NET 8). Place the exact v1.0.2 portable ZIP beside this directory; its SHA-256 is checked by the launcher.

```powershell
dotnet publish Setup.csproj -c Release -o build
```

Normal users download the unsigned release EXE. It bundles its own Windows x64 runtime and uses Windows PowerShell 5.1 internally.
