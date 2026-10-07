# DreamGrid Website v1.0.1

Fixed the Stats dashboard Grid login service health probe so it checks the dynamically discovered local Robust service rather than the public/browser hostname. The configured Robust port remains dynamic.

Only stats-api.php and health-diagnostics-api.php changed in the website payload. StatsHistoryCollector.ps1 already used the correct local target. DreamGrid networking and Robust configuration are unchanged. All 132 targeted regression checks passed, including alternate ports, public host independence, IPv4 health when IPv6 loopback fails, and relocated installs.

**Required: ASCII Windows installation path · DIVA OFF · OTHER enabled · Folder = Other.**

Install DreamGrid and configure your grid/regions first. Stop DreamGrid before installing this add-on. Follow [installation instructions](https://github.com/Sharpened-Razor/dreamgrid-website/blob/main/INSTALLATION.md). The installer preserves existing content and media, verifies hashes, and creates dated backups with rollback records.

Approved plan: 34 entries. Verified fresh live images: 32. Withheld 3D images: 2. The full 34-image set is not complete. ADMIN-3D-Map.png and USER-3D-Map.png are withheld because the live 3D scene has unresolved grid-content texture failures. No incomplete 3D image, old capture or substitute texture is presented. Texture repair is outside this website/documentation task.
