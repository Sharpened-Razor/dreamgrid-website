DreamGrid Website Setup 1.0.2 — Sharpened-Razor/dreamgrid-website

Run DreamGrid-Website-Setup.exe. Select the discovered DreamGrid folder or Browse. Stop the destination DreamGrid/Apache first. DIVA must be OFF, OTHER selected, Folder Other. The setup never edits Settings.ini or region configuration.

The unsigned EXE contains the complete corrected payload (1,276 files). Published SHA-256 checksums verify the release downloads. Its internal engine uses Windows PowerShell 5.1 supplied with Windows; PowerShell 7 and manual script execution are not required. Native website runtime prerequisites still apply.

Setup saves a readable log and a verified backup archive. Choose Restore backup in the EXE to revert, preserving current replaced files in the rollback archive. Uploaded images/custom textures are preserved on upgrade.

Installer-test automation: --install --root <path> --archive-parent <path> --log <path>; --restore <archive>; --simulate-failure-after <number> tests automatic rollback.

