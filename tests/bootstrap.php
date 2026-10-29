<?php

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\PackageManifest;

$vendor = dirname(__DIR__).'/vendor';
putenv('COMPOSER_VENDOR_DIR='.$vendor);
$_ENV['COMPOSER_VENDOR_DIR'] = $vendor;
require $vendor.'/autoload.php';
$skeleton = $vendor.'/orchestra/testbench-core/laravel';
(new PackageManifest(new Filesystem, $skeleton, $skeleton.'/bootstrap/cache/packages.php'))->build();
spl_autoload_register(function ($class) {
    $prefix = 'Fayne\\PostReleaseStatus\\';
    if (str_starts_with($class, $prefix)) {
        require __DIR__.'/../src/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
    }
});
require_once __DIR__.'/TestCase.php';
