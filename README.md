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

**Laravel Pint** is a zero-dependency PHP code style fixer for minimalists - built on top of **[PHP-CS-Fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer)**. Pint makes it simple to ensure that your code style stays **clean** and **consistent**.

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

**By default, Pint does not require any configuration** and will fix code style issues in your code by following the rules defined in the [PSR-12 Style Guide](https://www.php-fig.org/psr/psr-12).

However, if you wish to customize the presets, rules, or inspected folders, you may do so by creating a `pint.json` file in your project's root directory:

```json
{
    "preset": "psr12"
}
```

<a name="presets"></a>
### Presets

Presets define a set of rules that can be used to fix code style issues in your code. By default, Pint uses the `psr12` preset, which fixes issues by following the rules defined in the [PSR-12 Style Guide](https://www.php-fig.org/psr/psr-12).

However, you can use a different preset by passing the `--preset` option:

```bash
pint --preset laravel
```

If you wish, you may also set the preset in your project's `pint.json` file:

```json
{
    "preset": "laravel"
}
```

The currently supported presets are: `psr12`, `laravel`, and `symfony`.

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
