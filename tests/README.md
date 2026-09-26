# Basic checks

These unit tests run without Nextcloud, a database or network services. They cover event date selection, public URL validation and slug collisions, and footer HTML sanitisation.

With PHP 8.2 or newer, the DOM and mbstring extensions, and PHPUnit 11 available, run from the repository root:

```sh
find appinfo lib templates tests -type f -name '*.php' -print0 | xargs -0 -n 1 php -l
phpunit --configuration phpunit.xml.dist
```

GitHub Actions runs both commands on pull requests and pushes to `main`, using PHP 8.2 (the app's declared minimum). The check name for branch protection is **PHP checks**.

These checks do not exercise Nextcloud integration, account switching or browser rendering.
