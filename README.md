Debian package build
====================

[![Latest Stable Version](https://img.shields.io/packagist/v/vysokeskoly/deb-build.svg)](https://packagist.org/packages/vysokeskoly/deb-build)
[![License](https://img.shields.io/packagist/l/vysokeskoly/deb-build.svg)](https://packagist.org/packages/vysokeskoly/deb-build)
[![Checks](https://github.com/vysokeskoly/deb-build/actions/workflows/checks.yaml/badge.svg)](https://github.com/vysokeskoly/deb-build/actions/workflows/checks.yaml)
[![Build](https://github.com/vysokeskoly/deb-build/actions/workflows/php-checks.yaml/badge.svg)](https://github.com/vysokeskoly/deb-build/actions/workflows/php-checks.yaml)
[![Coverage Status](https://coveralls.io/repos/github/vysokeskoly/deb-build/badge.svg)](https://coveralls.io/github/vysokeskoly/deb-build)

Build deb package of PHP application using [Robo](http://robo.li/) task runner.


# How to install and use

First install this package as composer dependency:

```sh
composer require vysokeskoly/deb-build:dev-master
```

- *NOTE* it must NOT be installed as `--dev` dependency, as it is needed for `postinst` which is triggered on during installation on target server.

Then you can copy example `RoboFile.php`:

```sh
cd {YOUR_PROJECT}
cp vendor/vysokeskoly/deb-build/example/RoboFile.php ./RoboFile.php
```

Now just edit `RoboFile.php` and resolve all `TODOs` and check/edit other configuration.

## Autoloading

- You can require `Tasks` and `Traits` in your own `RoboFile.php`.
- Or you can use the predefined autoloader:
```php
require __DIR__ . '/vendor/vysokeskoly/deb-build/src/autoload.php';
```
- It is not recommended (though it may be possible in some cases) to use `vendor/autoload.php` of your application,
 because it may (and most probably will) conflict with `robo.phar` inner dependencies (_like `Symfony`_).

# Build a deb package

## Build a deb package

```sh
sudo apt-get install ruby-dev gcc make
sudo gem install fpm

export BUILD_NUMBER=666
bin/robo build:deb
```
