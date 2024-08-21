# simplesamlphp-module-authyubikey

![Build Status](https://github.com/simplesamlphp/simplesamlphp-module-authyubikey/actions/workflows/php.yml/badge.svg)
[![Coverage Status](https://codecov.io/gh/simplesamlphp/simplesamlphp-module-authyubikey/branch/master/graph/badge.svg)](https://codecov.io/gh/simplesamlphp/simplesamlphp-module-authyubikey)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/simplesamlphp/simplesamlphp-module-authyubikey/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/simplesamlphp/simplesamlphp-module-authyubikey/?branch=master)
[![Type Coverage](https://shepherd.dev/github/simplesamlphp/simplesamlphp-module-authyubikey/coverage.svg)](https://shepherd.dev/github/simplesamlphp/simplesamlphp-module-authyubikey)
[![Psalm Level](https://shepherd.dev/github/simplesamlphp/simplesamlphp-module-authyubikey/level.svg)](https://shepherd.dev/github/simplesamlphp/simplesamlphp-module-authyubikey)

## Install

Install with composer

```bash
    vendor/bin/composer require simplesamlphp/simplesamlphp-module-authyubikey
```

## Configuration

Next thing you need to do is to enable the module: in `config.php`,
search for the `module.enable` key and set `authyubikey` to true:

```php
    'module.enable' => [
        'authyubikey' => true,
        …
    ],
```
