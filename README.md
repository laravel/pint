<p align="center">
    <img src="/art/pint-example.png" alt="Logo Laravel Pint preview" style="width:70%;">
</p>

<p align="center">
<a href="https://github.com/laravel/pint/actions"><img src="https://github.com/laravel/pint/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/pint"><img src="https://img.shields.io/packagist/dt/laravel/pint" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/pint"><img src="https://img.shields.io/packagist/v/laravel/pint" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/pint"><img src="https://img.shields.io/packagist/l/laravel/pint" alt="License"></a>
</p>

<a name="introduction"></a>
## Introduction

**Laravel Pint** is a minimalist, simple, zero dependencies coding-style fixer for PHP, built on top of [PHP-CS-Fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer), that you may use to ensure a clean and consistent code style-wise.

<a name="installation"></a>
## Installation

You may use Composer to install Pint into your new PHP project:

    composer require laravel/pint --dev

After Pint has been installed, you may run `pint` to fix coding-style issues in your code:

    pint

<a name="configuring"></a>
## Configuring Pint

By default, Pint does not require any configuration, and it will fix coding-style issues in your code following the rules defined in the [PSR 12 Style Guide](https://www.php-fig.org/psr/psr-12).

However, Pint's configuration can be defined in the `pint.json` file, which should be located at the root of your project.

<a name="presets"></a>
### Presets

Presets are a way to define a set of rules that can be used to fix coding-style issues in your code. By default, the `psr12` preset is used, which fixes coding-style issues following the rules defined in the [PSR 12 Style Guide](https://www.php-fig.org/psr/psr-12). 

However, you can opt to use a different preset by passing the `--preset` option:

```bash
pint --preset laravel
```

If you wish, you may also set the preset in your `pint.json` file: 

    {
        "preset": "laravel"
    }

<a name="rules"></a>
### Rules

Rules are style guidelines that Pint will use to fix coding-style issues in your code. As mentioned above, presets already include a set of rules, as typically you don't need to worry about them.

If you wish, you may opt to disable or enable rules in your `pint.json` file:

    {
        "preset": "laravel",
        "rules": {
            "array_indentation" => true,
        }
    }

As mentioned above, Pint is built on top of the [PHP-CS-Fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer) tool, as such, you may use any of its rules to fix coding-style issues in your code: [github.com/FriendsOfPHP/PHP-CS-Fixer/blob/master/doc/rules](https://github.com/FriendsOfPHP/PHP-CS-Fixer/blob/master/doc/rules/index.rst)

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
