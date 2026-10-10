# Required website components

The release is a website add-on. The payload manifest is the exact distributable file list; installation discovers the target rather than shipping this machine's configuration.

## Website-owned runtime

- Apache web-root entry points, PHP sitemap and `.htaccess` routing/protection.
- `/Other` public website, shared core helpers, login/session integration, Control Center, User Dashboard, Page Designer and site tools.
- Builder PHP/JavaScript/CSS, native presets/templates, import/conversion models, asset handling, forms, publishing/revisions and interaction/SEO models.
- Website CSS, JavaScript, icons, images and fonts. Existing destination image/font/media files at matching paths are preserved.
- Legacy Search, MetroMap adapter and native CGI-facing website pages. MetroMap reads native regenerated centre coordinates; search discovers the destination schema and uses native PHP 7 for XML-RPC compatibility when needed.
- Private 3D-map executables, runtime descriptors and rebuild source. HTTP access to private/source files is denied.
- `_WEB_CONTROL` website email/environment helpers, native bridge assembly/source and private legacy XML-RPC handler.
- Self-contained Windows x64 setup EXE (bundled runtime; internal Windows PowerShell 5.1), read-only prerequisite checker, process ownership helper, rollback script and SHA-256 payload manifest.

Legacy asset filenames, CSS/DOM/cache identifiers and saved-format identifiers are retained for compatibility. They do not select the configured grid name, domain, port or installation path. Public grid labels use native configuration. Default artwork remains part of the existing website theme.

## Native DreamGrid supplies and owns

- DreamGrid application and its native startup/service management.
- Settings.ini and Robust/OpenSim configuration, databases, regions, inventories and assets.
- Apache binaries/modules, native configuration and templates, PHP runtimes/extensions, MySQL and the configured service environment.
- OpenSim managed/native DLLs used by meshing and texture tools.
- Destination Perl and .NET website prerequisites; existing optional workers retain their own PowerShell requirements; destination SMTP/TLS configuration and certificates where applicable.

These are not copied from the development installation into the website package. Native Apache/PHP regeneration was exercised from a relocated path under both PHP selections and again after website installation.

## Destination-only state: preserve and exclude from distribution

- `_WEB_PAGE_DESIGNER`: pages, drafts, revisions, images, trash/recovery and editor state.
- `_WEB_PRIVATE` and existing custom branding, messenger backgrounds and uploaded textures.
- Session secrets, bridge key/port, generated loopback metadata, logs, caches and jobs.
- Native generated MetroMap configuration and static sitemaps. The website's PHP sitemap routes work independently of the static files DreamGrid regenerates.
- All simulator/database/region/user data and all installation rollback archives.

The installer changes the discovered website bridge registration in native Start.runtimeconfig.json while retaining other properties and hooks. It does not replace that entire file with a source-machine copy. Rerun the installer at a new root before starting a relocated installation.

## v1.0.4 compatibility

Install and configure DreamGrid first. The website installer discovers the DreamGrid installation; select and confirm the destination or use Browse. Use an ASCII Windows installation path, turn DIVA off, and retain Folder = Other. Existing websites require OTHER selected. Fresh installations do not need OTHER enabled beforehand: choose the fresh-install option so the installer deploys and verifies website content before selecting CMS=Other / OtherCMS=Other and committing the approved startup hook. Stop DreamGrid, regions and Apache before installation or rollback.

NativeBridge requires .NET 9. v1.0.4 was fully exercised on DreamGrid 7.2115 / .NET 9.0.20. DreamGrid 7.2114 compatibility was retained through native API inspection and focused bridge regression tests; a complete new 7.2114 installation/destructive-operation cycle was not repeated for v1.0.4. Requalify other DreamGrid versions. The setup EXE bundles its own installer runtime; that does not replace DreamGrid's runtime requirement. The Windows installer is unsigned and may trigger SmartScreen / Unknown Publisher warnings. Verify published SHA-256 checksums. HTTPS is strongly recommended for internet-facing grids; production TLS and code signing are not claimed.
