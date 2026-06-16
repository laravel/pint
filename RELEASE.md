# Release Instructions

1. Pull down the latest changes on the `main` branch
2. Run `composer install`
3. Update the version in [`config/app.php`](./config/app.php)
4. Compile the binary with

```zsh
./pint app:build
```

5. Commit all changes
6. Push all commits to GitHub
7. Tag the release and push the tag:

```zsh
git tag v1.x.y
git push origin v1.x.y
```

8. Edit the release title or notes on GitHub if needed (these remain editable
   even though immutable releases lock the assets and tag)
