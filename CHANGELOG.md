# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.3] - 2026-09-24

### Changed

- README and integration examples use canonical production API base URL `https://telemedicina.althoapp.com`.

## [0.1.2] - 2026-07-29

### Changed

- Republish current package contents under a new immutable Packagist version.

## [0.1.1] - 2026-07-29

### Changed

- Clarify the current stable version in the README install section.

## [0.1.0] - 2026-07-29

### Added

- Initial public release of the typed PHP SDK for the AlthoTelemedicina API.
- `TelemedicineClient` with `me()`, `createSession()`, `getSession()`, and `endSession()`.
- Multi-tenant support via immutable `withApiKey()`.
- cURL transport (`CurlTransport`) with injectable `HttpTransportInterface`.
- Typed DTOs (`Session`, `ApplicationCredentials`) and exception hierarchy.
- PHPUnit, PHPStan level 8, PHP CS Fixer, and GitHub Actions CI (PHP 8.2–8.4).

[0.1.3]: https://github.com/gonzaloalonsod/althos-telemedicina-php/releases/tag/v0.1.3
[0.1.2]: https://github.com/gonzaloalonsod/althos-telemedicina-php/releases/tag/v0.1.2
[0.1.1]: https://github.com/gonzaloalonsod/althos-telemedicina-php/releases/tag/v0.1.1
[0.1.0]: https://github.com/gonzaloalonsod/althos-telemedicina-php/releases/tag/v0.1.0
