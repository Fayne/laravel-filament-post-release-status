<?php

use MWGuerra\FileManager\Adapters\AdapterFactory;
use MWGuerra\FileManager\Adapters\DatabaseAdapter;
use MWGuerra\FileManager\Adapters\StorageAdapter;

it('creates storage adapter when mode is storage', function () {
    config()->set('filemanager.mode', 'storage');

    $adapter = AdapterFactory::make();

    expect($adapter)->toBeInstanceOf(StorageAdapter::class);
});

it('creates database adapter when mode is database', function () {
    config()->set('filemanager.mode', 'database');

    $adapter = AdapterFactory::make();

    expect($adapter)->toBeInstanceOf(DatabaseAdapter::class);
});

it('throws exception for unknown mode', function () {
    config()->set('filemanager.mode', 'invalid');

    AdapterFactory::make();
})->throws(InvalidArgumentException::class, 'Unknown file manager mode: invalid');

it('creates database adapter with default config', function () {
    $adapter = AdapterFactory::makeDatabase();

    expect($adapter)->toBeInstanceOf(DatabaseAdapter::class)
        ->and($adapter->getModeName())->toBe('database');
});

it('creates database adapter with custom disk', function () {
    $adapter = AdapterFactory::makeDatabase('custom-disk');

    expect($adapter)->toBeInstanceOf(DatabaseAdapter::class);
});

it('creates database adapter with custom disk and directory', function () {
    $adapter = AdapterFactory::makeDatabase('custom-disk', 'custom-dir');

    expect($adapter)->toBeInstanceOf(DatabaseAdapter::class);
});

it('creates storage adapter with default config', function () {
    $adapter = AdapterFactory::makeStorage();

    expect($adapter)->toBeInstanceOf(StorageAdapter::class)
        ->and($adapter->getModeName())->toBe('storage');
});

it('creates storage adapter with custom disk', function () {
    $adapter = AdapterFactory::makeStorage('custom-disk');

    expect($adapter)->toBeInstanceOf(StorageAdapter::class)
        ->and($adapter->getDisk())->toBe('custom-disk');
});

it('creates storage adapter with custom disk and root', function () {
    $adapter = AdapterFactory::makeStorage('custom-disk', 'custom-root');

    expect($adapter)->toBeInstanceOf(StorageAdapter::class)
        ->and($adapter->getDisk())->toBe('custom-disk')
        ->and($adapter->getRoot())->toBe('custom-root');
});

it('returns current mode name', function () {
    config()->set('filemanager.mode', 'storage');

    expect(AdapterFactory::getMode())->toBe('storage');

    config()->set('filemanager.mode', 'database');

    expect(AdapterFactory::getMode())->toBe('database');
});

it('returns database as default mode when config key is missing', function () {
    // Get the entire filemanager config and remove the mode key
    $config = config('filemanager');
    unset($config['mode']);
    config()->set('filemanager', $config);

    expect(AdapterFactory::getMode())->toBe('database');
});

it('checks if in database mode', function () {
    config()->set('filemanager.mode', 'database');
    expect(AdapterFactory::isDatabaseMode())->toBeTrue();
    expect(AdapterFactory::isStorageMode())->toBeFalse();

    config()->set('filemanager.mode', 'storage');
    expect(AdapterFactory::isDatabaseMode())->toBeFalse();
});

it('checks if in storage mode', function () {
    config()->set('filemanager.mode', 'storage');
    expect(AdapterFactory::isStorageMode())->toBeTrue();
    expect(AdapterFactory::isDatabaseMode())->toBeFalse();

    config()->set('filemanager.mode', 'database');
    expect(AdapterFactory::isStorageMode())->toBeFalse();
});
