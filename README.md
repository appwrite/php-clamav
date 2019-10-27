# Appwrite ClamAV PHP Client

[![Build Status](https://travis-ci.org/appwrite/php-clamav.svg?branch=master)](https://travis-ci.org/appwrite/php-clamav)
![Total Downloads](https://img.shields.io/packagist/dt/appwrite/php-clamav.svg)
[![Discord](https://img.shields.io/discord/564160730845151244)](https://discord.gg/GSeTUeA)

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

$clam->fileScan('path/to/file.dmg'); // Return true of false for file scan

$clam->reload(); // Reload ClamAV database

$clam->shutdown(); // Shutdown ClamAV
```

## System Requirements

This package requires PHP 7.1 or later. We recommend using the latest PHP version whenever possible.

## Authors

**Eldad Fux**

+ [https://twitter.com/eldadfux](https://twitter.com/eldadfux)
+ [https://github.com/eldadfux](https://github.com/eldadfux)

## Copyright and license

The MIT License (MIT) [http://www.opensource.org/licenses/mit-license.php](http://www.opensource.org/licenses/mit-license.php)
