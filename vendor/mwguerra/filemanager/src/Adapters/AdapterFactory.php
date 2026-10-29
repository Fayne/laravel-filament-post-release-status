<?php

namespace MWGuerra\FileManager\Adapters;

use InvalidArgumentException;
use MWGuerra\FileManager\Contracts\FileManagerAdapterInterface;

/**
 * Factory for creating file manager adapters.
 *
 * Resolves the appropriate adapter based on configuration.
 */
class AdapterFactory
{
    /**
     * Create an adapter based on current configuration.
     */
    public static function make(): FileManagerAdapterInterface
    {
        $mode = config('filemanager.mode', 'database');

        return match ($mode) {
            'database' => static::makeDatabase(),
            'storage' => static::makeStorage(),
            default => throw new InvalidArgumentException("Unknown file manager mode: {$mode}"),
        };
    }

    /**
     * Create a database adapter.
     *
     * @param string|null $disk Override the disk (defaults to config)
     * @param string|null $directory Override the upload directory (defaults to config)
     */
    public static function makeDatabase(?string $disk = null, ?string $directory = null): DatabaseAdapter
    {
        return new DatabaseAdapter(
            modelClass: config('filemanager.model'),
            disk: $disk ?? config('filemanager.upload.disk', 'public'),
            directory: $directory ?? config('filemanager.upload.directory', 'uploads')
        );
    }

    /**
     * Create a storage adapter.
     *
     * @param string|null $disk Override the disk (defaults to config)
     * @param string|null $root Override the root directory (defaults to config)
     */
    public static function makeStorage(?string $disk = null, ?string $root = null): StorageAdapter
    {
        return new StorageAdapter(
            disk: $disk ?? config('filemanager.storage_mode.disk', 'public'),
            root: $root ?? config('filemanager.storage_mode.root', ''),
            showHidden: config('filemanager.storage_mode.show_hidden', false)
        );
    }

    /**
     * Get the current mode name.
     */
    public static function getMode(): string
    {
        return config('filemanager.mode', 'database');
    }

    /**
     * Check if in database mode.
     */
    public static function isDatabaseMode(): bool
    {
        return static::getMode() === 'database';
    }

    /**
     * Check if in storage mode.
     */
    public static function isStorageMode(): bool
    {
        return static::getMode() === 'storage';
    }
}
