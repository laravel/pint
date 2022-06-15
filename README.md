## Team Preface

> This repository contains a proof-of-concept on what could become **pint**. Code, tests, and output, will become different if we actually decide to move forward. 

Hi Team. Welcome to Pint, an opinionated code formatter for PHP. It's a minimalist and zero-dependency alternative to PHP CS Fixer, that just works once you require it in your `composer.json` file. Here are the instructions to try it out locally:

```bash
cd <your-work-directory>
git clone git@github.com:laravel/pint.git
alias pint=$(pwd)/laravel-pint/builds/pint
```

Finally, you may format the PHP code in any of your projects:
```
cd <your-favorite-project>
pint
pint --fix
pint --preset=laravel
pint --preset=laravel --fix
```

---

<p align="center">
    <img src="/art/pint-example.png" alt="Logo Laravel Pint CLI preview" style="width:70%;">
</p>

<p align="center">
<a href="https://github.com/laravel/pint/actions"><img src="https://github.com/laravel/pint/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/pint"><img src="https://img.shields.io/packagist/dt/laravel/pint" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/pint"><img src="https://img.shields.io/packagist/v/laravel/pint" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/pint"><img src="https://img.shields.io/packagist/l/laravel/pint" alt="License"></a>
</p>

## Introduction

**Laravel Pint** is an opinionated code formatter for PHP...

## Official Documentation

Documentation for Pint CLI can be found on ...

## Contributing

Thank you for considering contributing to Pint CLI! You can read the contribution guide [here](.github/CONTRIBUTING.md).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

Please review [our security policy](https://github.com/laravel/pint/security/policy) on how to report security vulnerabilities.

## License

Pint CLI is open-sourced software licensed under the [MIT license](LICENSE.md).
