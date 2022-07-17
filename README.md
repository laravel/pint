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

## Official Documentation

Documentation for Pint can be found on the [Laravel website](https://laravel.com/docs/pint).

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
