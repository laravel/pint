# Release Notes

## [Unreleased](https://github.com/laravel/pint/compare/v1.7.0...main)

## [v1.7.0](https://github.com/laravel/pint/compare/v1.6.0...v1.7.0) - 2023-03-21

- Enhancement: Enable self_static_accessor fixer by @localheinz in https://github.com/laravel/pint/pull/154

## [v1.6.0](https://github.com/laravel/pint/compare/v1.5.0...v1.6.0) - 2023-02-21

- Migrates to Laravel Zero 10 by @nunomaduro in https://github.com/laravel/pint/pull/149
- Support Windows paths for --dirty option by @Rigby90 in https://github.com/laravel/pint/pull/150

## [v1.5.0](https://github.com/laravel/pint/compare/v1.4.1...v1.5.0) - 2023-02-14

### Changed

- Removes superfluous annotations by @nunomaduro in https://github.com/laravel/pint/pull/126

## [v1.4.1](https://github.com/laravel/pint/compare/v1.4.0...v1.4.1) - 2023-01-31

### Changed

- Add option to ignore no changes for --dirty by @joelbutcher in https://github.com/laravel/pint/pull/140

## [v1.4.0](https://github.com/laravel/pint/compare/v1.3.0...v1.4.0) - 2023-01-10

### Changed

- Adds `--dirty` option by @nunomaduro in https://github.com/laravel/pint/pull/130

## [v1.3.0](https://github.com/laravel/pint/compare/v1.2.1...v1.3.0) - 2022-12-20

### Changed

- Use native php-cs-fixers for phpdoc order and separation by @GrahamCampbell in https://github.com/laravel/pint/pull/133

## [v1.2.1](https://github.com/laravel/pint/compare/v1.2.0...v1.2.1) - 2022-11-29

### Changed

- Enable types_spaces rule in Laravel preset by @zepfietje in https://github.com/laravel/pint/pull/128

## [v1.2.0](https://github.com/laravel/pint/compare/v1.1.3...v1.2.0) - 2022-09-13

### Added

- Add configuration option to overwrite cache file location by @wouter2203 in https://github.com/laravel/pint/pull/111

## [v1.1.3](https://github.com/laravel/pint/compare/v1.1.2...v1.1.3) - 2022-09-06

### Changed

- Ignores `build` folder by default by @nunomaduro in https://github.com/laravel/pint/pull/108
- Update to PHP-CS-Fixer v3.11 by @Jubeki in https://github.com/laravel/pint/pull/109

## [v1.1.2](https://github.com/laravel/pint/compare/v1.1.1...v1.1.2) - 2022-08-30

### Changed

- Adds support for `friendsofphp/php-cs-fixer:^3.10.0` by @nunomaduro in https://github.com/laravel/pint/pull/107

## [v1.1.1](https://github.com/laravel/pint/compare/v1.1.0...v1.1.1) - 2022-08-02

### Changed

- Laravel Preset - include `continue` in `blank_line_before_statement` by @jrseliga in https://github.com/laravel/pint/pull/95

## [v1.1.0](https://github.com/laravel/pint/compare/v1.0.0...v1.1.0) - 2022-07-26

### Added

- [1.x] Adds `--format` option by @nunomaduro in https://github.com/laravel/pint/pull/87

### Fixed

- [1.x] Ensures the configuration file is valid by @nunomaduro in https://github.com/laravel/pint/pull/86

## [v1.0.0](https://github.com/laravel/pint/compare/v0.2.4...v1.0.0) - 2022-07-14

### Added

- Stable release

## [v0.2.4](https://github.com/laravel/pint/compare/v0.2.3...v0.2.4) - 2022-07-13

**Full Changelog**: https://github.com/laravel/pint/compare/v0.2.3...v0.2.4

## [v0.2.3](https://github.com/laravel/pint/compare/v0.2.2...v0.2.3) - 2022-07-04

### What's Changed

- Keep {@inheritdoc} unchanged. by @lucasmichot in https://github.com/laravel/pint/pull/68
- Also ensure that the double arrow has a single space on each side. by @lucasmichot in https://github.com/laravel/pint/pull/67

**Full Changelog**: https://github.com/laravel/pint/compare/v0.2.2...v0.2.3

## [v0.2.2](https://github.com/laravel/pint/compare/v0.2.1...v0.2.2) - 2022-07-01

### What's Changed

- Fix the tag version extraction. by @lucasmichot in https://github.com/laravel/pint/pull/57
- List syntax rule by @brandonferens in https://github.com/laravel/pint/pull/66

### New Contributors

- @brandonferens made their first contribution in https://github.com/laravel/pint/pull/66

**Full Changelog**: https://github.com/laravel/pint/compare/v0.2.1...v0.2.2

## [v0.2.1](https://github.com/laravel/pint/compare/v0.2.0...v0.2.1) - 2022-06-27

### What's Changed

- Fix actions versions for PHAR deployment job. by @lucasmichot in https://github.com/laravel/pint/pull/49
- Remove unused imports by @shuvroroy in https://github.com/laravel/pint/pull/48
- [0.x] Tests against windows by @nunomaduro in https://github.com/laravel/pint/pull/53

### New Contributors

- @shuvroroy made their first contribution in https://github.com/laravel/pint/pull/48
- @nunomaduro made their first contribution in https://github.com/laravel/pint/pull/53

**Full Changelog**: https://github.com/laravel/pint/compare/v0.2.0...v0.2.1

## [v0.2.0](https://github.com/laravel/pint/compare/v0.1.7...v0.2.0) - 2022-06-24

### What's Changed

- Fix readme code styling by @Jubeki in https://github.com/laravel/pint/pull/25
- [0.x] Add Fixers for Laravel specific PHPDocs by @Jubeki in https://github.com/laravel/pint/pull/3
- Ignore `node_modules` folder by @aryehraber in https://github.com/laravel/pint/pull/27
- Adjust description by @calebporzio in https://github.com/laravel/pint/pull/33
- [0.x] Ignore Laravel actions IDE helper file by @edwinvdpol in https://github.com/laravel/pint/pull/36
- [0.x] Publish PHAR by @lucasmichot in https://github.com/laravel/pint/pull/34
- [0.x] Exclude file via pint json by @michalkortas in https://github.com/laravel/pint/pull/40
- Remove unused $path variable by @michalkortas in https://github.com/laravel/pint/pull/41

### New Contributors

- @Jubeki made their first contribution in https://github.com/laravel/pint/pull/25
- @aryehraber made their first contribution in https://github.com/laravel/pint/pull/27
- @calebporzio made their first contribution in https://github.com/laravel/pint/pull/33
- @edwinvdpol made their first contribution in https://github.com/laravel/pint/pull/36
- @lucasmichot made their first contribution in https://github.com/laravel/pint/pull/34
- @michalkortas made their first contribution in https://github.com/laravel/pint/pull/40

**Full Changelog**: https://github.com/laravel/pint/compare/v0.1.6...v0.2.0

## [v0.1.7](https://github.com/laravel/pint/compare/v0.1.6...v0.1.7) - 2022-06-23

### What's Changed

- Fix readme code styling by @Jubeki in https://github.com/laravel/pint/pull/25
- [0.x] Add Fixers for Laravel specific PHPDocs by @Jubeki in https://github.com/laravel/pint/pull/3
- Ignore `node_modules` folder by @aryehraber in https://github.com/laravel/pint/pull/27

### New Contributors

- @Jubeki made their first contribution in https://github.com/laravel/pint/pull/25
- @aryehraber made their first contribution in https://github.com/laravel/pint/pull/27

**Full Changelog**: https://github.com/laravel/pint/compare/v0.1.6...v0.1.7

## [v0.1.6](https://github.com/laravel/pint/compare/v0.1.5...v0.1.6) - 2022-06-23

### What's Changed

- Doc: Clarifies rules documentation && add options by @julien-boudry in https://github.com/laravel/pint/pull/18

### New Contributors

- @julien-boudry made their first contribution in https://github.com/laravel/pint/pull/18

**Full Changelog**: https://github.com/laravel/pint/compare/v0.1.5...v0.1.6

## [v0.1.5](https://github.com/laravel/pint/compare/v0.1.4...v0.1.5) - 2022-06-23

### What's Changed

- Ignore .phpstorm.meta.php by @fieu in https://github.com/laravel/pint/pull/16

### New Contributors

- @fieu made their first contribution in https://github.com/laravel/pint/pull/16

**Full Changelog**: https://github.com/laravel/pint/compare/v0.1.4...v0.1.5

## [v0.1.4](https://github.com/laravel/pint/compare/v0.1.3...v0.1.4) - 2022-06-23

### What's Changed

- [0.x] Add multiple additional fixers by @claudiodekker in https://github.com/laravel/pint/pull/10

### New Contributors

- @claudiodekker made their first contribution in https://github.com/laravel/pint/pull/10

**Full Changelog**: https://github.com/laravel/pint/compare/v0.1.3...v0.1.4

## [v0.1.3](https://github.com/laravel/pint/compare/v0.1.2...v0.1.3) - 2022-06-22

**Full Changelog**: https://github.com/laravel/pint/compare/v0.1.2...v0.1.3

## [v0.1.2](https://github.com/laravel/pint/compare/v0.1.1...v0.1.2) - 2022-06-22

### What's Changed

- Ignore _ide_helper_models.php by @vinkla in https://github.com/laravel/pint/pull/5

### New Contributors

- @vinkla made their first contribution in https://github.com/laravel/pint/pull/5

**Full Changelog**: https://github.com/laravel/pint/compare/v0.1.1...v0.1.2

## [v0.1.1](https://github.com/laravel/pint/compare/v0.1.0...v0.1.1) - 2022-06-22

**Full Changelog**: https://github.com/laravel/pint/compare/v0.1.0...v0.1.1

## v0.1.0 (2022-06-22)

Initial pre-release.
