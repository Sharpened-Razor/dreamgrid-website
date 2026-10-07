> **Screenshot set withdrawn on 7 October 2026.** The previous captures failed identity/loading and duplication review. Replacement images will remain unpublished until the entire set is checked against a capture manifest.

# installation guide

This website is an add-on for an **existing Windows DreamGrid installation**. Install DreamGrid, configure your grid and create your regions first. The website uses that installation's name, services, domains and ports. It does not create regions or replace your grid data.

**Required: ASCII Windows installation path · DIVA OFF · OTHER enabled · Folder = Other.**

[Download the verified ZIP](https://github.com/Sharpened-Razor/dreamgrid-website/releases/download/v1.0.1/DreamGrid-Website-Portable-v1.0.1.zip) · [Usage guide](USAGE.md) · [screenshot review status](SCREENSHOTS.md)

## 1. Prepare the existing grid

Use DreamGrid's normal setup to create the grid and regions and confirm they work before adding the website. Keep the native internal folder layout. The folder selected during installation must contain that installation's **Settings.ini**, Apache and Opensim directories. Choose your own folder; the source grid's installation path is not a requirement.

Every folder in the Windows installation path must use ASCII characters. Spaces, different folders and different drive letters are supported. Full Unicode paths are unsupported: the bundled legacy Perl stack did not pass the complete Unicode regression. Do not rely on a mapped drive to claim Unicode compatibility.

## 2. Find the DreamGrid settings

Open **DreamGrid → Setup → Settings**. The screenshots show the native DreamGrid interface used for this release; labels can differ across native versions.

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
| Freeze Apache Conf file | Prevents native regeneration. This add-on supports DreamGrid's normal regeneration; the reference screenshot leaves this unchecked. Do not freeze or hand-edit configuration merely to install the add-on. |
| SSL 443 | Opens native TLS setup. HTTPS works only when your destination has a valid certificate and corresponding Apache configuration. It is not enabled by installing the website. |
| Automatic Site Map | Native DreamGrid's sitemap facility. Keep your existing choice unless you have a reason to change it. It does not select DIVA or OTHER. |

## 4. Find the public domain and website URL

Open **Setup → Settings → Hypergrid DNS Name**. The DNS Name field is where the grid's hostname can be checked. Use your actual configured public host, without copying the example grid's name. Ensure your DNS, router/firewall and selected Apache port allow visitors to reach your server; the website installer does not configure network forwarding.

Open **Web Control Panel** to inspect the **Splash Screen URL**. This is the URL shown as the viewer's welcome/splash page. It should point to your intended website if you want the viewer to show it. It does not start Apache or create a DNS record. The left-hand legacy DIVA administrator fields are not website login fields and are not needed for OTHER.

For your installation, use **http://YOUR-GRID-DOMAIN/Other/**. If the HTTP web port is not the standard port, use **http://YOUR-GRID-DOMAIN:YOUR-WEB-PORT/Other/**. If you configured HTTPS successfully, use **https://YOUR-GRID-DOMAIN/Other/** or include your nonstandard HTTPS port. The Hypergrid service port is a different service; it is not automatically the Apache web port.

## 5. Download, verify and extract

Download the portable ZIP and its SHA-256 file from the release. GitHub's automatic source-code archives are repository snapshots, not the verified installer ZIP. In PowerShell 7, choose the downloaded file and calculate its checksum:

```powershell
$zip = Read-Host 'Full path of downloaded portable ZIP'
Get-FileHash -LiteralPath $zip -Algorithm SHA256
```

Expected SHA-256 for v1.0.1:

```text
FB60A7E8EADA3A286435CD338B66316493426AA4996DD4D98B6FC132A6DAB651
```

Extract the complete ZIP into a separate folder. Keep manifest.json, the scripts and payload together. Do not flatten the payload or copy the whole ZIP into Apache's public web root. The installer places the files into their matching locations in the existing grid and preserves destination media.

## 6. Stop the destination and run the installer

Save the prerequisite selections, then stop DreamGrid and Apache normally. Open **PowerShell 7** in the extracted package folder. Use an administrator terminal where your destination requires it. Run:

```powershell
$gridRoot = Read-Host 'Existing DreamGrid data folder containing Settings.ini'
.\Test-DreamGridWebsitePrerequisites.ps1 -Root $gridRoot
.\Install-DreamGridWebsite.ps1 -Root $gridRoot
```

Enter the existing DreamGrid data folder when prompted, not the extracted package folder. The prerequisite check validates the selection and required runtimes. Resolve reported problems and run the check again; do not bypass it. If Windows blocks a trusted downloaded script, use Windows' normal unblock controls.

The installer validates payload hashes, archives replaced files to a dated Desktop folder, generates destination-local website startup metadata and verifies installed hashes. It preserves the native Apache/PHP templates and does not start services. Saved pages, user databases, regions, inventories and matching destination images are preserved. Keep the Desktop archive and its manifest for rollback.

## 7. Start DreamGrid normally

Use DreamGrid's normal Start workflow after installation. Apache and the services required by the website must be running. Native DreamGrid regenerates its Apache/PHP configuration in the normal way; the website integrates with that startup instead of depending on a one-time manual configuration edit.

## 8. Open the website yourself

**Installation does not automatically open a webpage.** Open your browser, type your installation's URL in the address bar, and press Enter:

```text
http://YOUR-GRID-DOMAIN/Other/
```

| Page | Portable example URL |
| --- | --- |
| Public website | http://YOUR-GRID-DOMAIN/Other/ |
| Login | http://YOUR-GRID-DOMAIN/Other/login.php |
| Admin Control Center | http://YOUR-GRID-DOMAIN/Other/admin-home.php |
| User Dashboard | http://YOUR-GRID-DOMAIN/Other/FreshUserDashboardExact/user-dashboard.php |
| Page Designer | http://YOUR-GRID-DOMAIN/Other/admin-page-designer.php |

The examples assume standard HTTP. Substitute your actual scheme and include a nonstandard web port when necessary. The admin pages require an authorized administrator session; typing an admin URL does not grant administrator access.

## 9. Check the installed site

Open the public site, login, your account/dashboard and, for an administrator, the Control Center and Page Designer. Check existing custom pages, navigation, images and maps. Refresh previously open map pages so they get the installation-specific map token. Test optional mail delivery and HTTPS using your own destination configuration. Read the [illustrated usage guide](USAGE.md) and [gallery](SCREENSHOTS.md) for each screen.

## 10. If the webpage does not open

| Symptom or check | What to do |
| --- | --- |
| DreamGrid stopped | Start it normally and wait for services to initialize. |
| Apache stopped | Check Enable Apache Web server and Apache's status/logs in DreamGrid. Restart through the normal native workflow. |
| Wrong domain/host | Compare the browser hostname with Hypergrid DNS Name and the intended website host. Check DNS and external access. |
| Wrong HTTP/HTTPS port | Read Web Port and your TLS setup. Use the correct scheme and append a nonstandard port. Do not substitute the Hypergrid port. |
| DIVA still enabled | Return to Apache Settings, turn DIVA OFF, enable OTHER, save and restart normally. |
| OTHER not enabled | Select Enable Other in Apache Settings and save. |
| Folder is not Other | Set the OTHER folder to Other. Check the installer prerequisite report. |
| Works on server, fails elsewhere | Check router forwarding, firewall and public DNS for the selected web port. Network setup is outside the website installer. |
| Public site works, admin URL redirects | Login first with an authorized administrator account. Clear stale sessions by logging out and in again. |
| Maps/search fail | Check their native dependencies and service status; retain PHP 7 XML-RPC, Perl and map DLLs. |
| Old content after installation | Refresh the browser. Check the selected website folder and installed hash report before making manual edits. |

## Runtime details, relocation, rollback and verification limits

The following reference details remain applicable to the illustrated steps above.

## Required destination environment

- Existing native DreamGrid Apache/PHP runtime and configured Robust/MySQL services.
- PowerShell 7 (`pwsh`) for installation and the existing website workers. Use sufficient Windows permissions for the destination and service inspection; an administrator PowerShell is appropriate for a protected installation.
- Native PHP 7 plus its XML-RPC extension, retained for the legacy search adapter even when Apache uses PHP 8.
- The existing Perl dependency, with `perl.exe` available through PATH for native CGI.
- .NET 8 runtime for the existing 3D-map helper executables, in addition to the runtime required by DreamGrid itself. Retain DreamGrid's native OpenSim meshing, drawing and JPEG2000 DLLs.
- Destination SMTP and TLS configuration, if those optional facilities are used. No credentials, certificates or source-machine configuration are supplied.

The installer checks the native runtime, PHP 7 search dependency, Perl, .NET 8 and the required native map DLLs. It refuses a running destination; it does not stop or start services automatically.

## Install

Extract the complete package into a separate folder. Keep `manifest.json`, the installation scripts and `payload` together. Open PowerShell 7 in that folder:

```powershell
$gridRoot = Read-Host 'Existing DreamGrid data folder containing Settings.ini'
.\Test-DreamGridWebsitePrerequisites.ps1 -Root $gridRoot
.\Install-DreamGridWebsite.ps1 -Root $gridRoot
```

If Windows blocks scripts downloaded from a source you trust, unblock the extracted package using Windows' normal file/script controls. Do not change DreamGrid configuration to bypass a failed prerequisite check.

The installer verifies every payload hash before deployment. It backs up replaced files in a dated **DreamGrid-Website-Install** folder on the current user's Desktop, preserves their folder structure, and records original/archive paths, filename, SHA-256 and reason in `manifest.json`. It then verifies installed hashes. Existing images at matching asset paths are preserved.

The installer generates only destination-local website metadata and, when absent, a random private bridge key and an available loopback bridge port. It preserves existing bridge credentials and other native runtime properties. Its automatic bridge registration is the only change to the existing native `Start.runtimeconfig.json`; native Apache/PHP templates are left alone.

Start DreamGrid normally after installation. Its normal startup regenerates its own Apache/PHP configuration. Then check the public site, login, Control Center, User Dashboard, Page Designer, maps and any existing custom pages. Refresh previously open map pages so they receive the installation-specific map token.

## Saved content and rollback

The package does not include or overwrite `_WEB_PAGE_DESIGNER`, `_WEB_PRIVATE`, database files, regions, OARs, IARs, inventories, simulator data, uploaded custom branding or messenger backgrounds. Existing saved pages remain in their current format; conversion is an explicit builder action.

To roll back, stop the destination DreamGrid and Apache, keep this package's scripts together, and run:

```powershell
$backup = Read-Host 'Full path of the dated Desktop installation archive'
.\Restore-DreamGridWebsite.ps1 -ArchiveDirectory $backup
```

Rollback validates backup hashes and destination paths. It preserves the current files in a further archive before restoring originals, and moves newly installed files into that archive. It does not permanently delete files.

## Verification limits

Automated PHP 7/8 tests cover native configuration regeneration, sessions/access, routes/assets, builder editing and persistence, publishing, forms with a local inbox/mock mail transport, templates/import/export, interactions/SEO models, search, map helpers and private-file protection. Relocated ASCII paths and an alternate drive letter were tested.

The final live smoke uses short-lived signed sessions for existing accounts without changing accounts or passwords. Live password login was not attempted; password login/logout was tested against an isolated synthetic database. No live test pages, form submissions, imports or publishing writes were introduced. Rendered visual/mobile acceptance, real SMTP delivery and optional TLS remain installation-specific checks. These are not a claim of a complete penetration test or support for every DreamGrid version.

