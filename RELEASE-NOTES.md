# DreamGrid Website v1.0.3

- Corrected NativeBridge startup compatibility: targets .NET 9 and waits for DreamGrid Start/FormSetup initialization before opening its socket listener, fixing the DiagnosticSource startup-order failure.
- Preserves native region DEREGISTER and permanent DELETE, loopback/key authentication and health/readiness.
- Sanitized the bridge release build: compiler/PDB filesystem paths are omitted; bridge source and all 31 method bodies are unchanged from the functionally qualified build.
- Hardened Start.runtimeconfig.json validation, atomic hook commit and exact rollback.
- Corrected fresh installation: deploy and verify website content before selecting CMS=Other / OtherCMS=Other.
- Added shared login brute-force throttling; removed public session_secret_exists information; improved HTTPS/session/admin protections.
- Retained installer payload verification, backups, content preservation and rollback protections.

Qualification passed: clean baseline startup; full website installation; CMS=Other persistence; DreamGrid Ready and Start; Robust; Apache; PHP/PHP DB; Welcome; NativeBridge health; disposable-region DEREGISTER and DELETE; exact rollback; restored baseline restart. The sanitized build also passed focused isolated load, Initialize, health, full startup/CMS persistence and exact rollback checks.

Qualified on **DreamGrid 7.2114 / .NET 9.0.20**. NativeBridge requires .NET 9. Other DreamGrid versions require requalification.

The Windows installer is **unsigned**; Microsoft SmartScreen / Unknown Publisher warnings may appear. HTTPS is strongly recommended for internet-facing grids. Code signing and full production TLS qualification are not claimed. Previously completed security tests retain their documented isolated scope.

## SHA-256

```text
2F95AF499818E2A6FECE9861D58EB4FCB9F5F467E13187C4EDEA8A5705A89763  DreamGrid-Website-Setup.exe
B254D3873631D9CBDB10D6973C9F967529173FC84F7AB70666375056B39BDCF8  DreamGrid-Website-Portable-v1.0.3.zip
```

Bridge DLL inside the ZIP: `35D3BE3BED664B1108C88321EF8B8F4713414F98FA32768B3BE7F0111ACB02AA`.

Use the downloadable portable ZIP for manual installation; GitHub source archives represent the source tree.
