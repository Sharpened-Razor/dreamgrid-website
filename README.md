# DreamGrid Website

A portable website add-on for an existing, configured Windows DreamGrid installation, with a visual Page Designer, Control Center and User Dashboard.

**Required before installation: ASCII Windows path · DIVA OFF · OTHER enabled · Folder = Other.**

Install and configure DreamGrid and your regions first. This package adapts to that installation; it does not install DreamGrid or replace grid/region data.

## Download and install

- [Download v1.0.1 portable ZIP](https://github.com/Sharpened-Razor/dreamgrid-website/releases/download/v1.0.1/DreamGrid-Website-Portable-v1.0.1.zip)
- [SHA-256 checksum](https://github.com/Sharpened-Razor/dreamgrid-website/releases/download/v1.0.1/DreamGrid-Website-Portable-v1.0.1.sha256)
- [Full installation and rollback instructions](INSTALLATION.md)
- [Illustrated usage guide](USAGE.md)
- [Live screenshot gallery](SCREENSHOTS.md)
- [Screenshot coverage and known limitations](SCREENSHOT-COVERAGE.md)
- [Required components](REQUIREMENTS.md)
- [Verification results and limits](VERIFICATION.md)
- [Download website](https://sharpened-razor.github.io/dreamgrid-website/)

In **DreamGrid > Setup > Settings > Apache Settings**, turn **DIVA OFF**, select/enable **OTHER**, and set its folder to **Other**. Save, then stop DreamGrid and Apache. The installer checks this setting and never changes it silently.

Extract the complete release ZIP, open PowerShell 7 in the extracted folder containing the scripts, and run:

```powershell
$gridRoot = Read-Host 'Existing DreamGrid data folder containing Settings.ini'
.\Test-DreamGridWebsitePrerequisites.ps1 -Root $gridRoot
.\Install-DreamGridWebsite.ps1 -Root $gridRoot
```

Start DreamGrid normally afterward. Use the release ZIP for installation; GitHub's automatic source archives are repository snapshots, not the verified installer ZIP.

**Installing does not automatically open the webpage.** Open your browser and enter `http://YOUR-GRID-DOMAIN/Other/`, using your actual domain, scheme and web port. A nonstandard HTTP port uses `http://YOUR-GRID-DOMAIN:YOUR-WEB-PORT/Other/`.

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

PowerShell 7, the existing native Apache/PHP environment, native PHP 7 XML-RPC support, Perl on PATH, .NET 8 and DreamGrid's native OpenSim map dependencies are required. See [installation instructions](INSTALLATION.md) for the complete details.

When relocating DreamGrid, stop it, follow its native relocation procedure, then rerun this website installer with the new root **before starting DreamGrid** to refresh its generated startup-hook registration. Native DreamGrid still owns and regenerates Apache/PHP configuration.

## Content preservation

Saved pages, uploaded images, private content, databases, regions, inventories, credentials, certificates and native configuration are not distributed. Existing destination media is preserved. Existing pages are not automatically converted. Replaced website files are backed up with hashes and an archive manifest.

## Version and verification

Current portable add-on release: **1.0.1**, verified 7 October 2026. The two local Robust health probes are corrected; 132 targeted regression checks passed. Automated acceptance includes 1,628 builder assertions, 182 live smoke checks and seven live search checks. See [changelog](CHANGELOG.md) and [verification](VERIFICATION.md).

Approved plan: 34 entries. Verified fresh live images: 32. Withheld 3D images: 2. The full 34-image set is not complete. ADMIN-3D-Map.png and USER-3D-Map.png are withheld because the live 3D scene has unresolved grid-content texture failures. No incomplete 3D image, old capture or substitute texture is presented. Texture repair is outside this website/documentation task.

Rendered visual/mobile acceptance, real SMTP delivery and optional TLS remain destination checks. This is not a complete penetration test or a guarantee for every DreamGrid version.

Existing third-party components and artwork retain their respective notices and licenses. This release does not relicense third-party material. Retained legacy filenames and format/CSS identifiers are compatibility identifiers; runtime grid identity comes from the destination configuration.
