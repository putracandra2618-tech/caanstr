---
paths:
  - 'C:/laragon/bin/php/php-8.3.33-Win32-vs16-x64/php.ini'
---

# Php

## pdo_sqlite extension needed for the test suite
phpunit.xml uses sqlite :memory:, which requires the pdo_sqlite + sqlite3 PHP extensions loaded in php.ini. They were commented out; tests failed with 'could not find driver'. Keep extension=pdo_sqlite and extension=sqlite3 enabled or `php artisan test` cannot run.
