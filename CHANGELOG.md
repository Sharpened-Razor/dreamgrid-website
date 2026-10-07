# Changelog

## 1.0.1 — 2026-10-07

- Fixes both local Robust health probes using shared dynamic local-service discovery and configured ports.
- Retains the already-correct Stats history collector.
- Passes 132 targeted regression checks; retains installer safeguards, exclusions and sanitized binaries.
- Keeps the rejected gallery withdrawn pending approval of current-live replacements.

## Screenshot set withdrawn — 2026-10-07

The previous gallery and screenshot verification claim have been withdrawn. Replacement captures require explicit readiness/identity checks, a manifest and duplicate review before publication. The portable installer ZIP is unchanged.

## Documentation update — 2026-10-07

- Adds full illustrated installation and usage guides, a captioned screenshot gallery and searchable GitHub Pages gallery.
- Shows native Apache/DIVA/OTHER/folder settings, the public hostname location, viewer splash URL and running service indicators with private fields/logs covered.
- Explains opening the website manually, all important portable URLs, nonstandard web ports and troubleshooting.
- Captures every Control Center and User Dashboard menu item separately and the major Page Designer tools, template conversion, V10 conversion, forms, history, device views and editing workflows.
- Records older standalone route failures and documentation-fixture service limits explicitly in SCREENSHOT-COVERAGE.md.
- Changes documentation only. The verified v1.0.0 installer ZIP and SHA-256 remain unchanged; no production settings or saved content were modified.

## 1.0.0 — 2026-10-07

First public portable website add-on release.

- Includes the completed visual Page Designer and existing working public website, Control Center, User Dashboard and related website tools.
- Uses destination discovery for grid identity, installation paths, services, domains, ports and database configuration.
- Keeps native DreamGrid Apache/PHP regeneration compatible with website startup integration.
- Includes verified installation, prerequisite checks, hashes, Desktop backup manifests and rollback.
- Preserves existing page formats, saved content and destination media; conversion remains explicit.
- Repairs native/legacy events-schema compatibility and PHP 8 search JSON compatibility.
- Derives the public map token per installation from its existing private bridge key.
- Requires ASCII Windows paths, DIVA OFF, OTHER enabled and Folder = Other.
- Excludes source-installation region-state markers and custom-branding files from publication; originals remain local.
- Removes optional compiler PDB build-folder metadata from five release binaries; executable code and assembly identities are unchanged. Thirteen targeted checks passed afterward. Live files and the earlier local ZIP were not modified by this publication cleanup.

See [verification results](VERIFICATION.md) for tested scope and remaining destination checks.
