# Appwrite ClamAV PHP Client

[![Discord](https://img.shields.io/discord/564160730845151244?label=discord&style=flat-square)](https://appwrite.io/discord?r=Github)
![Total Downloads](https://img.shields.io/packagist/dt/appwrite/php-clamav.svg?style=flat-square)
[![Build Status](https://img.shields.io/travis/com/appwrite/php-clamav?style=flat-square)](https://travis-ci.com/appwrite/php-clamav)
[![Twitter Account](https://img.shields.io/twitter/follow/appwrite?color=00acee&label=twitter&style=flat-square)](https://twitter.com/appwrite)

PHP Client to connect to ClamAV daemon over TCP or using a local socket from command line and scan your storage files for viruses.

## Getting Started

Install using composer:
```bash
composer require appwrite/php-clamav
```

```php
<?php

require_once 'vendor/autoload.php';

use Appwrite\ClamAV\Network;

$clam = new Network('localhost', 3310); // Or use new Pipe() for unix socket

$clam->ping(); // Check ClamAV is up and running

$clam->version(); // Check ClamAV version

$clam->fileScan('path/to/file.dmg'); // Returns true if a file is clean or false if a file is infected

$clam->reload(); // Reload ClamAV database

$clam->shutdown(); // Shutdown ClamAV
```

## System Requirements

This package requires PHP 7.1 or later. We recommend using the latest PHP version whenever possible.

## Find Us

* [GitHub](https://github.com/appwrite)
* [Discord](https://appwrite.io/discord)
* [Twitter](https://twitter.com/appwrite)

## Authors

**Eldad Fux**

+ [https://twitter.com/eldadfux](https://twitter.com/eldadfux)
+ [https://github.com/eldadfux](https://github.com/eldadfux)

## Copyright and license

The MIT License (MIT) [https://www.opensource.org/licenses/mit-license.php](http://www.opensource.org/licenses/mit-license.php)
