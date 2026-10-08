# DreamGrid Website v1.0.2

**Recommended download: [DreamGrid-Website-Setup.exe](https://github.com/Sharpened-Razor/dreamgrid-website/releases/download/v1.0.2/DreamGrid-Website-Setup.exe)** — self-contained Windows x64 setup with automatic discovery, Browse, Install / upgrade and Restore backup. No manual PowerShell scripts or PowerShell 7 are required for normal installation.

DreamGrid-Website-Setup.exe is currently unsigned. Windows may display an Unknown Publisher or Microsoft Defender SmartScreen warning. The SHA-256 checksum is published below so the downloaded file can be verified.

1. DreamGrid must already be installed and configured.
2. DIVA must be OFF.
3. OTHER must be enabled/selected.
4. Website folder must be **Other**.
5. Download **DreamGrid-Website-Setup.exe**.
6. Double-click it.
7. Confirm the detected DreamGrid installation, or select it with **Browse**.
8. Read the prerequisite guidance and stop the destination DreamGrid and Apache normally.
9. Click **Install / upgrade**. The installer runs its checks before changing files.
10. Wait for installation and hash validation to complete.
11. Start DreamGrid normally, then open your configured website URL.

The installer finds DreamGrid automatically, verifies prerequisites and package checksums, backs up replaced files, preserves custom/user content, installs the website, validates installed files and supports **Restore backup**. A failed deployment automatically rolls back and verifies the backed-up files. It saves a readable log and a dated backup archive on the Desktop. It does not stop or start DreamGrid automatically.

The corrected package contains **1,276 payload files and 1,276 manifest entries**, with **zero missing files and zero hash mismatches**. It includes the Custom Texture slot schema/upload folders and generic default artwork. It excludes personal uploaded custom textures/images. Existing custom content and destination media are preserved on upgrade.

The established targeted install/preservation/rollback tests passed; the final manual GUI Browse test also passed for valid, invalid and returning-valid selections. The installer refuses an invalid destination before deployment. The EXE version and project/publisher metadata identify DreamGrid Website and Sharpened-Razor; it remains unsigned.

## SHA-256

```text
850728c818437e262351aea23839c80e3f61ea02dc23381c8cbbadb10f747666  DreamGrid-Website-Setup.exe
aeb331caea3ed90e005c0ce83a15fdb04cd9a711d05d16bd6796bd3c7e789403  DreamGrid-Website-Portable-v1.0.2.zip
```

**Advanced / Manual Installation:** [DreamGrid-Website-Portable-v1.0.2.zip](https://github.com/Sharpened-Razor/dreamgrid-website/releases/download/v1.0.2/DreamGrid-Website-Portable-v1.0.2.zip); prerequisite/install/restore scripts are retained for recovery and advanced use. [Installation and runtime details](https://github.com/Sharpened-Razor/dreamgrid-website/blob/main/INSTALLATION.md).

Existing gallery/screenshots are unchanged. Both 3D images remain withheld because of the documented live-grid texture-content blocker. ASCII paths are required; full Unicode paths are unsupported. Native website runtime prerequisites and destination SMTP/TLS/visual checks still apply. v1.0.1 remains available and is not overwritten.
