# Release verification

Version 1.0.0. Verified 7 October 2026. This public summary is derived from the completed installation verification report; private logs, account identifiers, paths, configuration and archive manifests are not published.

## Passed automated checks

| Area | Evidence |
| --- | --- |
| Builder | 1,628 model, HTTP and DOM assertions across PHP 7/8 |
| Live website | 182 smoke checks, zero failures |
| Live search | Seven endpoint checks, zero failures |
| Native regeneration | Seven checks per PHP selection |
| Relocated HTTP matrix | 25 session/access, 115 routing/assets/JavaScript, 26 builder, 13 search, nine private-file and four Perl checks per PHP selection |
| Real relocated map tools | Four executable/native DLL checks |
| Installer | 17 checks; repeat install, preservation, backups and fail-before-write behavior |
| Running destination guard | Rejected a running destination without writes |
| Rollback | 142 records restored in an isolated installation; no permanent deletion |
| Native/legacy events | Four populated schema checks on PHP 7/8 |
| Map token | Nine derivation/rendering/HTTP guard checks |
| Source syntax | 422 PHP/JavaScript files; 328 files under PHP 7; PowerShell checks passed |
| Public binary metadata cleanup | 13 additional bridge/map-helper checks passed |

The package contains 1,272 manifest-listed payload files. Every payload SHA-256 and ZIP entry is checked again for publication. Compiler debug filenames in five binaries were neutralized in the release copy only; runtime code is unchanged. Installation-specific region-state and custom-branding files are excluded. The public release checksum therefore differs from the earlier local ZIP.

## Preservation

Saved page/image/private-content hashes and bridge credentials were unchanged by deployment and testing. Website installation/test operations did not alter production passwords, database schemas, regions, inventories or saved pages. Normal native startup regenerated its own configuration/sitemap and updated ordinary runtime state. Production services resume their normal data activity; running databases are not claimed to be byte-identical.

Production writes for publishing, imports, forms and recovery were avoided: those workflows were exercised in isolated copies. Live authenticated tests used short-lived signed sessions for existing accounts; password login/logout was tested in an isolated synthetic database.

## Supported path scope and limits

- ASCII Windows directory hierarchies, including spaces and relocation to another drive, were tested.
- Full Unicode paths are unsupported: direct Unicode-path Apache/PHP checks passed, but native Perl CGI failed all four checks.
- Current-builder demo screenshots were captured using local Edge automation with synthetic content and no reported JavaScript errors. They do not constitute full visual/mobile acceptance.
- Real SMTP delivery, optional TLS and destination-specific visual/mobile behavior remain installation checks.
- This is not a full penetration test or a guarantee for every DreamGrid release/runtime combination.

**Prerequisites: ASCII Windows path; DIVA OFF; OTHER enabled; Folder = Other.**
