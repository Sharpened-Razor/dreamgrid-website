# DreamGrid Website v1.0.0

Portable website add-on for an existing, configured DreamGrid installation.

**Required: ASCII Windows installation path · DIVA OFF · OTHER enabled · Folder = Other.**

Download the portable ZIP and its SHA-256 file below. Install DreamGrid and configure your grid/regions first, stop it before installation, and follow [INSTALLATION.md](https://github.com/Sharpened-Razor/dreamgrid-website/blob/main/INSTALLATION.md).

The installer verifies the payload, checks prerequisites, preserves existing content/media, creates dated Desktop backups and records rollback hashes. Native DreamGrid continues to own its configuration and region setup.

Includes the completed visual Page Designer, Control Center, User Dashboard and website tools. Automated acceptance passed: 1,628 builder assertions, 182 live smoke checks and seven live search checks. Publication metadata cleanup passed 13 additional bridge/map checks.

Full Unicode paths are unsupported. Real SMTP, optional TLS and final destination visual/mobile checks remain user checks. See the [verification summary](https://github.com/Sharpened-Razor/dreamgrid-website/blob/main/VERIFICATION.md).

The publication copy removes optional compiler build-folder metadata and excludes installation-specific region-state and custom-branding files. No live data, credentials, certificates, backups or source-machine configuration is included.

## Complete illustrated documentation

- [Installation guide with native DreamGrid settings](https://sharpened-razor.github.io/dreamgrid-website/installation.html)
- [Usage guide with illustrated workflows](https://sharpened-razor.github.io/dreamgrid-website/usage.html)
- [Searchable gallery — 220 captioned screenshots](https://sharpened-razor.github.io/dreamgrid-website/gallery.html)
- [Capture coverage and older-route/service limitations](https://sharpened-razor.github.io/dreamgrid-website/coverage.html)

**Installing does not automatically open the webpage.** Start DreamGrid normally, then enter `http://YOUR-GRID-DOMAIN/Other/` in your browser. Use your actual scheme and include a nonstandard web port when applicable. The illustrated guide documents login, Control Center, User Dashboard and Page Designer URLs.

Settings screenshots show DIVA OFF, OTHER enabled, Folder = Other, Apache/web port selection, hostname location, viewer splash URL and running service status. Native examples are labelled as examples from the AUSTRALIA grid; sensitive fields/logs are covered. Website screenshots use synthetic accounts and content.

The documentation sweep records several older standalone route failures and demo-dependent map limitations explicitly. It is not a claim that every legacy route or optional external service works. This is a documentation update only; **the v1.0.0 ZIP and SHA-256 have not changed**.
