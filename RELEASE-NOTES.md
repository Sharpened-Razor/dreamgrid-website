# DreamGrid Website v1.0.4



## DreamGrid 7.2115 NativeBridge compatibility



- Supports `MysqlInterface.DeregisterRegionUuid(Guid)` on 7.2114 and `Database.DeregisterRegionUuid(Guid)` on 7.2115.

- Validates the exact supported static, non-generic, void-returning, by-value Guid signature. Missing or ambiguous supported methods fail safely before native operation side effects.

- Preserves loopback-only NativeBridge binding, authentication, health/readiness checks and the startup-order fix.

- Adds no extra runtime DLLs, loaders, binding hacks or DreamGrid binary patches.

- Retains v1.0.3 security and installer hardening. The installer footer correctly identifies v1.0.4.



## Qualification and compatibility scope



**Fully exercised on DreamGrid 7.2115 / .NET 9.0.20.**



Live AUSTRALIA checks passed: DreamGrid Ready; Robust; Apache from the correct installation; PHP/MySQL; all 11 regions running; CMS=Other; NativeBridge Initialize and authenticated health HTTP 200 with `ok:true` and `ready:true`; unauthorized health HTTP 403; public website; Member Login; Admin Control Center; User Dashboard; Region Manager; Stats; Site & Page Design. The owner confirmed the protected website pages. Themes, branding and custom content were preserved. No live DELETE or DEREGISTER operations were performed.



One live region initially remained at script initialization. After a restart recorded by DreamGrid, it completed boot and all 11 regions passed current-state checks. This was not a clean first boot of every region; no agent region operation or live patch was performed.



The isolated 7.2115 test installation passed startup, disposable-region DEREGISTER, disposable-region DELETE, verified rollback and restored-baseline startup.



7.2114 compatibility was retained through native API inspection and focused bridge regression tests, but a complete new 7.2114 installation/destructive-operation cycle was not repeated for v1.0.4. Other DreamGrid versions require requalification.



## Installation and security



DreamGrid must already be installed and configured. NativeBridge requires .NET 9; the setup EXE includes its own installer runtime. For fresh installations, the staged flow deploys and verifies the website before switching CMS=Other. Supported existing custom website content is preserved, with verified backups and rollback support. Stop the destination before installation or rollback.



The Windows installer remains **unsigned**. Microsoft SmartScreen / Unknown Publisher warnings may appear. Verify the published SHA-256 hashes. HTTPS is strongly recommended for internet-facing grids. NativeBridge binds to loopback only and requires its destination-specific authentication key. Compiler/PDB paths are omitted from the published bridge and installer; no personal/local compiler paths are present. Production TLS and code signing are not claimed.



## SHA-256 — exact qualified release artifacts



```text

231DC51395AEF4AE3DF2FA042B9671811B8EE732312D7DB79DDD892C71A5FE69  DreamGrid-Website-Setup.exe

8B42C8D3020423E19F6E3C8F78E5C7B875D652F7E1A5EFFB5F679FFFFC9CE4FB  DreamGrid-Website-Portable-v1.0.4.zip

```



NativeBridge DLL inside the ZIP: `DFF01BE4B168D39E5329FEEB2D10DD962DA8C5CCECBE271B878708BA7FC19608`.



Use the downloadable portable ZIP for manual installation. GitHub's automatic source archives are source snapshots. The release artifacts were not rebuilt or repacked after live qualification or documentation updates.


Some bundled documentation and installer labels retain candidate wording from qualification. They remain unchanged to preserve the exact approved artifact bytes; the v1.0.4 release notes describe the completed qualification.
