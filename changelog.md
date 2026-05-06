# Changelog

All notable changes to `helper` will be documented in this file.

## Version 1.1

### Changed
- Added compatibility with Laravel 10, 11, 12, and 13 (`illuminate/support: ^9.0|^10.0|^11.0|^12.0|^13.0`).
- Bumped minimum PHP requirement to `^8.3` (supports up to PHP 8.5).
- Allow `giggsey/libphonenumber-for-php: ^9.0` alongside `^8.12.51`.
- Bumped PHPUnit dev dependency to `^10.0|^11.0|^12.0` and migrated `phpunit.xml` to the PHPUnit 12 schema (replaced deprecated `whitelist` with `<source>`, removed deprecated attributes).
- Added explicit return types to `HelperServiceProvider::boot()`, `register()`, `bootForConsole()` and `Facades\Helper::getFacadeAccessor()`.
- Replaced implicitly nullable parameter in `Helper::checkPhone()` (`string $locale = null` → `?string $locale = null`) to remove PHP 8.4+ deprecation notice.

## Version 1.0

### Added
- Everything
