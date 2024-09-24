# Release Instructions

1. Pull down the latest changes on the `main` branch
2. Run `composer install`
3. Update the version in [`config/app.php`](./config/app.php)
4. Compile the binary with

```zsh
./pint app:build
```

4. Commit all changes
5. Push all commits to GitHub
6. [Create a new GitHub release](https://github.com/laravel/pint/releases/new) with the release notes
