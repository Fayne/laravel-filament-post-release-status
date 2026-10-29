<?php

namespace MWGuerra\FileManager\FileTypes;

use MWGuerra\FileManager\Contracts\FileTypeContract;

/**
 * Abstract base class for file types.
 *
 * Provides sensible defaults for all methods. Extend this class
 * to create custom file types with minimal boilerplate.
 *
 * At minimum, you should override:
 * - identifier()
 * - supportedMimeTypes() or supportedExtensions()
 */
abstract class AbstractFileType implements FileTypeContract
{
    /**
     * Get the unique identifier for this file type.
     */
    abstract public function identifier(): string;

    /**
     * Get the human-readable label for this file type.
     *
     * Defaults to a title-cased version of the identifier.
     */
    public function label(): string
    {
        return ucfirst(str_replace(['-', '_'], ' ', $this->identifier()));
    }

    /**
     * Get the icon for this file type.
     *
     * Default: generic document icon.
     */
    public function icon(): string
    {
        return 'heroicon-o-document';
    }

    /**
     * Get the icon color class for this file type.
     *
     * Default: gray color.
     */
    public function iconColor(): string
    {
        return 'text-gray-400';
    }

    /**
     * Get the Filament color name for badges and indicators.
     *
     * Default: gray.
     */
    public function filamentColor(): string
    {
        return 'gray';
    }

    /**
     * Get the MIME types supported by this file type.
     *
     * Default: empty array (override to specify).
     *
     * @return array<string>
     */
    public function supportedMimeTypes(): array
    {
        return [];
    }

    /**
     * Get the file extensions supported by this file type.
     *
     * Default: empty array (override to specify).
     *
     * @return array<string>
     */
    public function supportedExtensions(): array
    {
        return [];
    }

    /**
     * Check if this file type can be previewed in the browser.
     *
     * Default: true if a viewer component is defined.
     */
    public function canPreview(): bool
    {
        return $this->viewerComponent() !== null;
    }

    /**
     * Get the blade component name for rendering the preview viewer.
     *
     * Default: null (will use fallback viewer).
     */
    public function viewerComponent(): ?string
    {
        return null;
    }

    /**
     * Get the priority for this file type.
     *
     * Higher priority types are matched first.
     * Default: 0. Built-in types use 10.
     */
    public function priority(): int
    {
        return 0;
    }

    /**
     * Check if this file type matches a given MIME type.
     *
     * Default implementation checks if the MIME type is in supportedMimeTypes(),
     * or if it starts with a prefix pattern (e.g., 'video/' matches 'video/*').
     */
    public function matchesMimeType(string $mimeType): bool
    {
        $mimeType = strtolower($mimeType);

        foreach ($this->supportedMimeTypes() as $supported) {
            $supported = strtolower($supported);

            // Exact match
            if ($supported === $mimeType) {
                return true;
            }

            // Wildcard match (e.g., 'video/*' matches 'video/mp4')
            if (str_ends_with($supported, '/*')) {
                $prefix = substr($supported, 0, -1); // 'video/'
                if (str_starts_with($mimeType, $prefix)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if this file type matches a given file extension.
     *
     * Default implementation checks if the extension is in supportedExtensions().
     */
    public function matchesExtension(string $extension): bool
    {
        $extension = strtolower(ltrim($extension, '.'));

        return in_array($extension, array_map('strtolower', $this->supportedExtensions()), true);
    }

    /**
     * Get additional metadata for this file type.
     *
     * Default: empty array.
     *
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        return [];
    }
}
