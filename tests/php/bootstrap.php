<?php

declare(strict_types=1);

define('PHPUNIT_RUN', 1);

// When running inside the container, load NC's autoloader
$ncBase = '/var/www/html/lib/base.php';
if (file_exists($ncBase)) {
    require_once $ncBase;
}

// Our app's own autoloader (composer install --dev)
$autoload = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}
