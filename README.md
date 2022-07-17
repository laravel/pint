<p align="center"><img src="https://github.com/laravel/pint/raw/HEAD/art/logo.svg" width="50%" alt="Logo Laravel Pint"></p>

<p align="center">
    <img src="/art/overview.png" alt="Overview Laravel Pint" style="width:70%;">
</p>

<p align="center">
    <a href="https://github.com/laravel/pint/actions"><img src="https://github.com/laravel/pint/workflows/tests/badge.svg" alt="Build Status"></a>
    <a href="https://packagist.org/packages/laravel/pint"><img src="https://img.shields.io/packagist/dt/laravel/pint" alt="Total Downloads"></a>
    <a href="https://packagist.org/packages/laravel/pint"><img src="https://img.shields.io/packagist/v/laravel/pint" alt="Latest Stable Version"></a>
    <a href="https://packagist.org/packages/laravel/pint"><img src="https://img.shields.io/packagist/l/laravel/pint" alt="License"></a>
</p>

<a name="introduction"></a>
## Introduction

**Laravel Pint** is an opinionated PHP code style fixer for minimalists. Pint is built on top of **[PHP-CS-Fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer)** and makes it simple to ensure that your code style stays **clean** and **consistent**.

<a name="installation"></a>
## Installation

> **Laravel Pint requires [PHP 8.0+](https://php.net/releases/).**

You may use Composer to install Pint into your PHP project:

```bash
composer require laravel/pint --dev
```

Once Pint has been installed, the `pint` binary will be available in your project's `vendor/bin` directory:

```bash
./vendor/bin/pint
```

<a name="running"></a>
## Running Pint

When running Pint, it will output a list of files that have been fixed. It is possible to see the changes made in more detail using the `-v` option:

```bash
./vendor/bin/pint -v
```

In addition, if you would like Pint to simply inspect your code for style errors without actually changing the files, you may use the `--test` option:

```bash
./vendor/bin/pint --test
```

<a name="configuring"></a>
## Configuring Pint

**By default, Pint does not require any configuration** and will fix code style issues in your code by following the opinionated coding style of Laravel.

However, if you wish to customize the presets, rules, or inspected folders, you may do so by creating a `pint.json` file in your project's root directory:

```json
{
    "preset": "laravel"
}
```

In addition, if you wish to use a `pint.json` from a specific directory, you may use the `--config` option:

```bash
pint --config vendor/my-company/coding-style/pint.json
```

<a name="presets"></a>
### Presets

Presets define a set of rules that can be used to fix code style issues in your code. By default, Pint uses the `laravel` preset, which fixes issues by following the opinionated coding style of Laravel.

However, you can use a different preset by passing the `--preset` option:

```bash
pint --preset psr12
```

If you wish, you may also set the preset in your project's `pint.json` file:

```json
{
    "preset": "psr12"
}
```

The currently supported presets are: `laravel`, `psr12`, and `symfony`.

<a name="rules"></a>
### Rules

Rules are style guidelines that Pint will use to fix code style issues in your code. As mentioned above, presets are predefined groups of rules that should be perfect for most PHP projects, so you typically will not need to worry about the individual rules they contain.

However, if you wish, you may enable or disable specific rules in your `pint.json` file:

```json
{
    "preset": "laravel",
    "rules": {
        "simplified_null_return": true,
        "braces": false,
        "new_with_braces": {
            "anonymous_class": false,
            "named_class": false
        }
    }
}
```

Pint is built on top of [PHP-CS-Fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer). Therefore, you may use any of its rules to fix code style issues in your project: [PHP-CS-Fixer Configurator](https://mlocati.github.io/php-cs-fixer-configurator/).

<a name="exclude-folders"></a>
### Exclude Folders

By default, Pint will inspect all `.php` files in your project except those in the `vendor` folder. If you wish to exclude more folders, you may do so by using the `exclude` configuration option:

```json
{
    "exclude": [
        "my-specific/folder"
    ]
}
```

<a name="exclude-files"></a>
### Exclude Files

If you wish to exclude file with specified name, you may do so by using the `notName` configuration option:

```json
{
    "notName": [
        "*-my-file.php"
    ]
}
```

If you want to exclude file from exact path, you may do so by using the `notPath` configuration option:

```json
{
    "notPath": [
        "path/to/excluded-file.php"
    ]
}
```

### Format output

You can output results in different [format](https://cs.symfony.com/doc/usage.html) supported by PHP-CS-Fixer. 
This is especially useful in CI/CD pipelines (for example, [Annotations via the Checks API](https://docs.github.com/en/rest/checks)):

```bash
pint --test --format=checkstyle
```

Then you can send output to the tool that will read it and create annotations for you. For example, for GitHub Actions 
you can implement following job:

```yaml
- name: Show Pint results in PR
run: pint --test --format=checkstyle | cs2pr
```

### Save report to the file
If you run `pint` with [--format](#format-output) option, it will suppress standard pint's output. If you want to see 
both standard and formatted output, you can save formatted output to report file:

```bash
pint --test --format=checkstyle --report=checkstyle.xml
```

This is especially useful in CI/CD pipelines (for example, [Annotations via the Checks API](https://docs.github.com/en/rest/checks)) 
when you want to have both job logs and job annotations. You can have one job to generate report file and second job 
to read from report file. For example, for GitHub Actions you can implement following jobs:

```yaml
- name: Check PHP code style
continue-on-error: true
run: pint --test --format=checkstyle --report=./pint-report.xml

- name: Show Pint results in PR
run: cs2pr ./pint-report.xml
```

<a name="contributing"></a>
## Contributing

Thank you for considering contributing to Pint! You can read the contribution guide [here](.github/CONTRIBUTING.md).

<a name="code-of-conduct"></a>
## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

<a name="security-vulnerabilities"></a>
## Security Vulnerabilities

Please review [our security policy](https://github.com/laravel/pint/security/policy) on how to report security vulnerabilities.

<a name="license"></a>
## License

Pint is open-sourced software licensed under the [MIT license](LICENSE.md).
