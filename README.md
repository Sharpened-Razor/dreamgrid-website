# DreamGrid Website

A portable website add-on for an existing, configured Windows DreamGrid installation, with a visual Page Designer, Control Center and User Dashboard.

**Required before installation: ASCII Windows path · DIVA OFF · OTHER enabled · Folder = Other.**

Install and configure DreamGrid and your regions first. This package adapts to that installation; it does not install DreamGrid or replace grid/region data.

## Download and install

**Recommended:** [Download DreamGrid-Website-Setup.exe — v1.0.2](https://github.com/Sharpened-Razor/dreamgrid-website/releases/download/v1.0.2/DreamGrid-Website-Setup.exe) (Windows x64).

DreamGrid-Website-Setup.exe is currently unsigned. Windows may display an Unknown Publisher or Microsoft Defender SmartScreen warning. The SHA-256 checksum is published below so the downloaded file can be verified.

SHA-256 of the exact release files:

```text
850728c818437e262351aea23839c80e3f61ea02dc23381c8cbbadb10f747666  DreamGrid-Website-Setup.exe
aeb331caea3ed90e005c0ce83a15fdb04cd9a711d05d16bd6796bd3c7e789403  DreamGrid-Website-Portable-v1.0.2.zip
```

[Release notes and downloads](https://github.com/Sharpened-Razor/dreamgrid-website/releases/tag/v1.0.2) · [Checksums](https://github.com/Sharpened-Razor/dreamgrid-website/releases/download/v1.0.2/SHA256SUMS.txt)

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

The EXE includes its own .NET runtime and uses Windows PowerShell 5.1 internally; ordinary installation requires no PowerShell 7 or manual script execution. The destination website's native runtime requirements still apply.

**Advanced / Manual Installation:** [Portable ZIP](https://github.com/Sharpened-Razor/dreamgrid-website/releases/download/v1.0.2/DreamGrid-Website-Portable-v1.0.2.zip) and scripts remain available for manual installation and recovery. Keep the complete ZIP together; GitHub's automatic source archives are repository snapshots.

- [Full installation and restore instructions](INSTALLATION.md)
- [Illustrated usage guide](USAGE.md)
- [Live screenshot gallery](SCREENSHOTS.md)
- [Screenshot coverage and known limitations](SCREENSHOT-COVERAGE.md)
- [Required components](REQUIREMENTS.md)
- [Verification results and limits](VERIFICATION.md)
- [Download website](https://sharpened-razor.github.io/dreamgrid-website/)

**Installing does not automatically open the webpage.** Use your configured domain, scheme and web port, for example `http://YOUR-GRID-DOMAIN/Other/`.

| Screen | Portable example URL |
| --- | --- |
| Public website | `http://YOUR-GRID-DOMAIN/Other/` |
| Login | `http://YOUR-GRID-DOMAIN/Other/login.php` |
| Admin Control Center | `http://YOUR-GRID-DOMAIN/Other/admin-home.php` |
| User Dashboard | `http://YOUR-GRID-DOMAIN/Other/FreshUserDashboardExact/user-dashboard.php` |
| Page Designer | `http://YOUR-GRID-DOMAIN/Other/admin-page-designer.php` |

The installation guide explains prerequisite settings and portable URLs. The live screenshot gallery covers the approved website pages.

Use the prerequisite settings and runtime requirements in the installation guide.

Use your own configured website domain, scheme and web port.

## Included

- Visual page building with sections, columns, text, images, buttons and layouts.
- Device styling and visibility, layers/breadcrumbs, locks, copy/paste and recovery.
- Reusable sections, image library, templates, site styles, navigation and shared headers/footers.
- Drafts, publishing, revisions, explicit conversion of existing pages, import/export and template import.
- Forms, publishing/link checks, interactions, SEO/site tools and built-in tutorials.
- Dynamic destination discovery, prerequisite checks, per-file hashes, Desktop backups and rollback.

These are fresh captures of the current live website after the DreamGrid 7.2115 update. Personal names, email addresses and private identifiers use capture-only labels. No stored account data, map widgets or failed textures were changed for the images. Grid identity and content shown are examples from the live destination; configure your own installation.

## Path and runtime requirements

Use an **ASCII path throughout the complete Windows directory hierarchy**. Spaces and other drive letters are supported. Full Unicode paths are unsupported because the bundled legacy Perl stack failed those tests. Preserve DreamGrid's native internal folder layout.

The setup EXE uses built-in Windows PowerShell 5.1. Existing optional website workers retain their runtime requirements. The existing native Apache/PHP environment, native PHP 7 XML-RPC support, Perl on PATH, .NET 8 and DreamGrid's native OpenSim map dependencies are required. See [installation instructions](INSTALLATION.md) for the complete details.

When relocating DreamGrid, stop it, follow its native relocation procedure, then rerun this website installer with the new root **before starting DreamGrid** to refresh its generated startup-hook registration. Native DreamGrid still owns and regenerates Apache/PHP configuration.

## Content preservation

Saved pages, uploaded images, private content, databases, regions, inventories, credentials, certificates and native configuration are not distributed. Existing destination media is preserved. Existing pages are not automatically converted. Replaced website files are backed up with hashes and an archive manifest.

## Version and verification

Current add-on release: **1.0.2**, released 8 October 2026. The corrected package has 1,276 payload files and 1,276 manifest entries, with zero missing files or hash mismatches. Custom Texture slot infrastructure and generic default artwork are included; personal uploaded images are excluded. The existing preservation and rollback checks passed, and the final GUI Browse check passed against valid, invalid and returning-valid selections. The two local Robust health probes are corrected; 132 targeted regression checks passed. Automated acceptance includes 1,628 builder assertions, 182 live smoke checks and seven live search checks. See [changelog](CHANGELOG.md) and [verification](VERIFICATION.md).

Approved plan: 34 entries. Verified fresh live images: 32. Withheld 3D images: 2. The full 34-image set is not complete. ADMIN-3D-Map.png and USER-3D-Map.png are withheld because the live 3D scene has unresolved grid-content texture failures. No incomplete 3D image, old capture or substitute texture is presented. Texture repair is outside this website/documentation task.

Rendered visual/mobile acceptance, real SMTP delivery and optional TLS remain destination checks. This is not a complete penetration test or a guarantee for every DreamGrid version.

Existing third-party components and artwork retain their respective notices and licenses. This release does not relicense third-party material. Retained legacy filenames and format/CSS identifiers are compatibility identifiers; runtime grid identity comes from the destination configuration.
