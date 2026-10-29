<?php

namespace MWGuerra\FileManager;

use MWGuerra\FileManager\Contracts\FileTypeContract;
use MWGuerra\FileManager\FileTypes\OtherFileType;

/**
 * Registry for managing file types.
 *
 * This service provides a central place to register and lookup file types.
 * Built-in types are registered automatically, and custom types can be
 * added via configuration or programmatically.
 *
 * Usage:
 * ```php
 * // Get the registry from the container
 * $registry = app(FileTypeRegistry::class);
 *
 * // Register a custom type
 * $registry->register(new MyCustomFileType());
 *
 * // Find type by MIME type
 * $type = $registry->fromMimeType('video/mp4');
 *
 * // Find type by extension
 * $type = $registry->fromExtension('pdf');
 *
 * // Get a specific type by identifier
 * $type = $registry->get('video');
 * ```
 */
class FileTypeRegistry
{
    /**
     * Registered file types indexed by identifier.
     *
     * @var array<string, FileTypeContract>
     */
    protected array $types = [];

    /**
     * The fallback file type for unknown files.
     */
    protected ?FileTypeContract $fallback = null;

    /**
     * Register a file type.
     *
     * If a type with the same identifier already exists, it will be replaced.
     */
    public function register(FileTypeContract $type): static
    {
        $this->types[$type->identifier()] = $type;

        return $this;
    }

    /**
     * Register multiple file types at once.
     *
     * @param  array<FileTypeContract>  $types
     */
    public function registerMany(array $types): static
    {
        foreach ($types as $type) {
            $this->register($type);
        }

        return $this;
    }

    /**
     * Unregister a file type by identifier.
     */
    public function unregister(string $identifier): static
    {
        unset($this->types[$identifier]);

        return $this;
    }

    /**
     * Check if a type is registered.
     */
    public function has(string $identifier): bool
    {
        return isset($this->types[$identifier]);
    }

    /**
     * Get a file type by its identifier.
     */
    public function get(string $identifier): ?FileTypeContract
    {
        return $this->types[$identifier] ?? null;
    }

    /**
     * Get all registered file types.
     *
     * @return array<string, FileTypeContract>
     */
    public function all(): array
    {
        return $this->types;
    }

    /**
     * Get all registered file types sorted by priority (highest first).
     *
     * @return array<FileTypeContract>
     */
    public function allSortedByPriority(): array
    {
        $types = array_values($this->types);

        usort($types, fn (FileTypeContract $a, FileTypeContract $b) => $b->priority() <=> $a->priority());

        return $types;
    }

    /**
     * Find a file type that matches the given MIME type.
     *
     * Returns the fallback type if no match is found.
     */
    public function fromMimeType(string $mimeType): FileTypeContract
    {
        foreach ($this->allSortedByPriority() as $type) {
            if ($type->matchesMimeType($mimeType)) {
                return $type;
            }
        }

        return $this->getFallback();
    }

    /**
     * Find a file type that matches the given file extension.
     *
     * Returns the fallback type if no match is found.
     */
    public function fromExtension(string $extension): FileTypeContract
    {
        $extension = strtolower(ltrim($extension, '.'));

        foreach ($this->allSortedByPriority() as $type) {
            if ($type->matchesExtension($extension)) {
                return $type;
            }
        }

        return $this->getFallback();
    }

    /**
     * Find a file type from a filename.
     *
     * Extracts the extension and uses fromExtension().
     */
    public function fromFilename(string $filename): FileTypeContract
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        return $this->fromExtension($extension);
    }

    /**
     * Set the fallback file type for unknown files.
     */
    public function setFallback(FileTypeContract $type): static
    {
        $this->fallback = $type;

        return $this;
    }

    /**
     * Get the fallback file type.
     *
     * Creates a default OtherFileType if none is set.
     */
    public function getFallback(): FileTypeContract
    {
        if ($this->fallback === null) {
            $this->fallback = new OtherFileType();
        }

        return $this->fallback;
    }

    /**
     * Get all MIME types supported by registered file types.
     *
     * @return array<string>
     */
    public function getAllSupportedMimeTypes(): array
    {
        $mimeTypes = [];

        foreach ($this->types as $type) {
            $mimeTypes = array_merge($mimeTypes, $type->supportedMimeTypes());
        }

        return array_unique($mimeTypes);
    }

    /**
     * Get all extensions supported by registered file types.
     *
     * @return array<string>
     */
    public function getAllSupportedExtensions(): array
    {
        $extensions = [];

        foreach ($this->types as $type) {
            $extensions = array_merge($extensions, $type->supportedExtensions());
        }

        return array_unique($extensions);
    }

    /**
     * Get file types that can be previewed.
     *
     * @return array<FileTypeContract>
     */
    public function getPreviewableTypes(): array
    {
        return array_filter(
            $this->types,
            fn (FileTypeContract $type) => $type->canPreview()
        );
    }

    /**
     * Convert an identifier to a file type, returning the fallback if not found.
     */
    public function resolve(string $identifier): FileTypeContract
    {
        return $this->get($identifier) ?? $this->getFallback();
    }
}
