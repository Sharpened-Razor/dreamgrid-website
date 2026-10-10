# Installation guide — v1.0.3

This is a website add-on for an existing, configured Windows DreamGrid installation. Keep its native internal layout. Select the data folder containing **Settings.ini**, **Apache** and **Opensim**.

**Required: ASCII Windows path · DIVA OFF · existing sites use OTHER · fresh installs use staged flow · Folder = Other.**

**Recommended:** [Download DreamGrid-Website-Setup.exe](https://github.com/Sharpened-Razor/dreamgrid-website/releases/download/v1.0.3/DreamGrid-Website-Setup.exe). [Release notes](https://github.com/Sharpened-Razor/dreamgrid-website/releases/tag/v1.0.3).

DreamGrid-Website-Setup.exe is currently unsigned. Windows may display an Unknown Publisher or Microsoft Defender SmartScreen warning. The SHA-256 checksum is published below so the downloaded file can be verified.

## Normal GUI installation

1. DreamGrid must already be installed and configured.
2. DIVA must be OFF.
3. Existing sites require OTHER; fresh installs deploy content before enabling OTHER.
4. Website folder must be **Other**.
5. Download **DreamGrid-Website-Setup.exe**.
6. Double-click it.
7. Confirm the detected DreamGrid installation, or select it with **Browse**.
8. Read the prerequisite guidance and stop the destination DreamGrid and Apache normally.
9. For a fresh installation choose the fresh-install option. Click **Install / upgrade**. The installer runs its checks before changing files.
10. Wait for installation and hash validation to complete.
11. Start DreamGrid normally, then open your configured website URL.

The installer finds DreamGrid automatically, verifies prerequisites and package checksums, backs up replaced files, preserves custom/user content, installs the website, validates installed files and supports **Restore backup**. A failed deployment automatically rolls back and verifies the backed-up files. It saves a readable log and a dated backup archive on the Desktop. It does not stop or start DreamGrid automatically.

The EXE is self-contained for Windows x64 and uses the Windows PowerShell 5.1 engine supplied with Windows. It requires no PowerShell 7 or manual script execution to install. Prerequisites and all payload hashes are checked before deployment; resolve a reported failure and try again. Browse can be used to choose a different folder. Checks run when **Install / upgrade** is clicked.

## Release checksums

```text
2F95AF499818E2A6FECE9861D58EB4FCB9F5F467E13187C4EDEA8A5705A89763  DreamGrid-Website-Setup.exe
B254D3873631D9CBDB10D6973C9F967529173FC84F7AB70666375056B39BDCF8  DreamGrid-Website-Portable-v1.0.3.zip
```

These hashes identify the exact downloadable files; they are not a digital signature. The ZIP is for **Advanced / Manual Installation**: [download it here](https://github.com/Sharpened-Razor/dreamgrid-website/releases/download/v1.0.3/DreamGrid-Website-Portable-v1.0.3.zip). To verify either file, the optional built-in Windows PowerShell command is `Get-FileHash -LiteralPath 'PATH-TO-DOWNLOAD' -Algorithm SHA256`.

## Open the website

After validation, start DreamGrid normally and open your browser at your actual website URL. Installation does not automatically open it. Use your configured domain, scheme and web port:

| Page | Portable example URL |
| --- | --- |
| Public website | `http://YOUR-GRID-DOMAIN/Other/` |
| Login | `http://YOUR-GRID-DOMAIN/Other/login.php` |
| Admin Control Center | `http://YOUR-GRID-DOMAIN/Other/admin-home.php` |
| User Dashboard | `http://YOUR-GRID-DOMAIN/Other/FreshUserDashboardExact/user-dashboard.php` |
| Page Designer | `http://YOUR-GRID-DOMAIN/Other/admin-page-designer.php` |

Include a nonstandard web port when necessary. Admin pages require an authorized administrator login. Read the [usage guide](USAGE.md) and [current live gallery](SCREENSHOTS.md).

## Existing DreamGrid settings reference

## 1. Prepare the existing grid

Use DreamGrid's normal setup to create the grid and regions and confirm they work before adding the website. Keep the native internal folder layout. The folder selected during installation must contain that installation's **Settings.ini**, Apache and Opensim directories. Choose your own folder; the source grid's installation path is not a requirement.

Every folder in the Windows installation path must use ASCII characters. Spaces, different folders and different drive letters are supported. Full Unicode paths are unsupported: the bundled legacy Perl stack did not pass the complete Unicode regression. Do not rely on a mapped drive to claim Unicode compatibility.

## 2. Find the DreamGrid settings

Open **DreamGrid → Setup → Settings**. Native labels can differ across DreamGrid versions. The approved screenshot list covers website pages, so no native settings screenshot is included.

## 3. Enable Apache and select OTHER

Open **Apache Webserver / Apache Settings**. Check **Enable Apache Web server**, turn **Enable Diva Page OFF**, select **Enable Other**, and set its folder textbox to **Other**. Save the settings. The installer checks the selection and stops if it is wrong; it does not change it silently.

| Setting | What it does and why it matters |
| --- | --- |
| Enable Apache Web server | Allows DreamGrid to run the web server that serves the website. DreamGrid running without Apache does not serve this site. |
| Enable Diva Page | Selects DreamGrid's alternative DIVA website. Leave it off for this add-on. |
| Enable Other | Selects the custom website folder used by this add-on. |
| Folder = Other | Makes DreamGrid serve the supplied Other website. Keep the spelling Other. |
| Web Port | The HTTP port visitors use. Read your installation's value; do not assume the example value. |
| PHP selection | Selects the native PHP runtime used by Apache. The release was tested with PHP 7 and PHP 8. Keep required native dependencies. |
| Freeze Apache Conf file | Prevents native regeneration. This add-on supports DreamGrid's normal regeneration; use the existing native configuration. Do not freeze or hand-edit configuration merely to install the add-on. |
| SSL 443 | Opens native TLS setup. HTTPS works only when your destination has a valid certificate and corresponding Apache configuration. It is not enabled by installing the website. |
| Automatic Site Map | Native DreamGrid's sitemap facility. Keep your existing choice unless you have a reason to change it. It does not select DIVA or OTHER. |

## 4. Find the public domain and website URL

Open **Setup → Settings → Hypergrid DNS Name**. The DNS Name field is where the grid's hostname can be checked. Use your actual configured public host, without copying the example grid's name. Ensure your DNS, router/firewall and selected Apache port allow visitors to reach your server; the website installer does not configure network forwarding.

Open **Web Control Panel** to inspect the **Splash Screen URL**. This is the URL shown as the viewer's welcome/splash page. It should point to your intended website if you want the viewer to show it. It does not start Apache or create a DNS record. The left-hand legacy DIVA administrator fields are not website login fields and are not needed for OTHER.

For your installation, use **http://YOUR-GRID-DOMAIN/Other/**. If the HTTP web port is not the standard port, use **http://YOUR-GRID-DOMAIN:YOUR-WEB-PORT/Other/**. If you configured HTTPS successfully, use **https://YOUR-GRID-DOMAIN/Other/** or include your nonstandard HTTPS port. The Hypergrid service port is a different service; it is not automatically the Apache web port.


## Supported installation paths

Use an **ASCII Windows path throughout the folder hierarchy**. Spaces, a different drive letter and a different outer installation folder are supported. Retain the internal directory layout required by native DreamGrid. The website discovers its destination root and reads that installation's grid name, domains, ports and database configuration.

Full Unicode-path support is not claimed. Direct Unicode-path tests passed Apache/PHP checks but failed all four native Perl CGI checks. A mapped-drive alias does not qualify as Unicode support. An experimental gateway is excluded.

If moving an existing DreamGrid installation, stop it first. After following DreamGrid's relocation procedure, rerun this website installer with the **new root before launching DreamGrid**. This refreshes the destination-generated .NET startup-hook registration. Do not manually copy the old `Start.runtimeconfig.json` hook path into the new location. Apache/PHP configuration remains owned and regenerated by DreamGrid.

## Required destination environment

- Existing native DreamGrid Apache/PHP runtime and configured Robust/MySQL services.
- Windows PowerShell 5.1, supplied with Windows, is used internally by the setup EXE. PowerShell 7 is not required to install. Existing optional website workers retain their own runtime prerequisites. Use sufficient Windows permissions for the destination and service inspection; an administrator PowerShell is appropriate for a protected installation.
- Native PHP 7 plus its XML-RPC extension, retained for the legacy search adapter even when Apache uses PHP 8.
- The existing Perl dependency, with `perl.exe` available through PATH for native CGI.
- .NET 8 runtime for the existing 3D-map helper executables, in addition to the runtime required by DreamGrid itself. Retain DreamGrid's native OpenSim meshing, drawing and JPEG2000 DLLs.
- Destination SMTP and TLS configuration, if those optional facilities are used. No credentials, certificates or source-machine configuration are supplied.

The installer checks the native runtime, PHP 7 search dependency, Perl, .NET 8 and the required native map DLLs. It refuses a running destination; it does not stop or start services automatically.

## Advanced / Manual Installation

Extract the complete package into a separate folder. Keep `manifest.json`, the installation scripts and `payload` together. For Advanced / Manual Installation, open Windows PowerShell 5.1 or PowerShell 7 in that folder:

```powershell
$gridRoot = Read-Host 'Existing DreamGrid data folder containing Settings.ini'
.\Test-DreamGridWebsitePrerequisites.ps1 -Root $gridRoot
.\Install-DreamGridWebsite.ps1 -Root $gridRoot
```

If Windows blocks scripts downloaded from a source you trust, unblock the extracted package using Windows' normal file/script controls. Do not change DreamGrid configuration to bypass a failed prerequisite check.

The installer verifies every payload hash before deployment. It backs up replaced files in a dated **DreamGrid-Website-Install** folder on the current user's Desktop, stores backups with indexed filenames, and records original/archive paths, filename, SHA-256 and reason in `manifest.json`. It then verifies installed hashes. Existing images at matching asset paths are preserved.

The installer generates only destination-local website metadata and, when absent, a random private bridge key and an available loopback bridge port. It preserves existing bridge credentials and other native runtime properties. Its automatic bridge registration is the only change to the existing native `Start.runtimeconfig.json`; native Apache/PHP templates are left alone.

Start DreamGrid normally after installation. Its normal startup regenerates its own Apache/PHP configuration. Then check the public site, login, Control Center, User Dashboard, Page Designer, maps and any existing custom pages. Refresh previously open map pages so they receive the installation-specific map token.

## Saved content and rollback

The package does not include or overwrite `_WEB_PAGE_DESIGNER`, `_WEB_PRIVATE`, database files, regions, OARs, IARs, inventories, simulator data, uploaded custom branding or messenger backgrounds. Existing saved pages remain in their current format; conversion is an explicit builder action.

For normal restore, stop the destination DreamGrid and Apache, open the EXE, choose **Restore backup** and select the dated Desktop archive containing `manifest.json` and `installation.json`. For Advanced / Manual restore, keep the package scripts together and run:

```powershell
$backup = Read-Host 'Full path of the dated Desktop installation archive'
.\Restore-DreamGridWebsite.ps1 -ArchiveDirectory $backup
```

Rollback validates backup hashes and destination paths. It preserves the current files in a further archive before restoring originals, and moves newly installed files into that archive. It does not permanently delete files.

## Verification limits

Automated PHP 7/8 tests cover native configuration regeneration, sessions/access, routes/assets, builder editing and persistence, publishing, forms with a local inbox/mock mail transport, templates/import/export, interactions/SEO models, search, map helpers and private-file protection. Relocated ASCII paths and an alternate drive letter were tested.

The final live smoke uses short-lived signed sessions for existing accounts without changing accounts or passwords. Live password login was not attempted; password login/logout was tested against an isolated synthetic database. No live test pages, form submissions, imports or publishing writes were introduced. Rendered visual/mobile acceptance, real SMTP delivery and optional TLS remain installation-specific checks. These are not a claim of a complete penetration test or support for every DreamGrid version.

## v1.0.3 compatibility and fresh installations

Install and configure DreamGrid first. The website installer discovers the DreamGrid installation; select and confirm the destination or use Browse. Use an ASCII Windows installation path, turn DIVA off, and retain Folder = Other. Existing websites require OTHER selected. Fresh installations do not need OTHER enabled beforehand: choose the fresh-install option so the installer deploys and verifies website content before selecting CMS=Other / OtherCMS=Other and committing the approved startup hook. Stop DreamGrid, regions and Apache before installation or rollback.

NativeBridge requires .NET 9 and is qualified on DreamGrid 7.2114 with .NET 9.0.20. Requalify other DreamGrid versions. The setup EXE bundles its own installer runtime; that does not replace DreamGrid's runtime requirement. The Windows installer is unsigned and may trigger SmartScreen / Unknown Publisher warnings. Verify published SHA-256 checksums. HTTPS is strongly recommended for internet-facing grids; production TLS and code signing are not claimed.
