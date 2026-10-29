<?php

namespace MWGuerra\FileManager\FileTypes;

/**
 * Fallback file type for unknown/unsupported files.
 *
 * This type is used when no other registered type matches
 * the file's MIME type or extension.
 */
class OtherFileType extends AbstractFileType
{
    public function identifier(): string
    {
        return 'other';
    }

    public function label(): string
    {
        return 'File';
    }

    public function icon(): string
    {
        return 'heroicon-o-document';
    }

    public function iconColor(): string
    {
        return 'text-gray-400';
    }

    public function filamentColor(): string
    {
        return 'gray';
    }

    public function supportedMimeTypes(): array
    {
        // Empty - this is a fallback type that doesn't match specific MIME types
        return [];
    }

    public function supportedExtensions(): array
    {
        // Empty - this is a fallback type that doesn't match specific extensions
        return [];
    }

    public function canPreview(): bool
    {
        return false;
    }

    public function viewerComponent(): ?string
    {
        return 'filemanager::components.viewers.fallback';
    }

    public function priority(): int
    {
        // Lowest priority - only used as fallback
        return -1;
    }

    /**
     * Override to always return false - this type should never match directly.
     */
    public function matchesMimeType(string $mimeType): bool
    {
        return false;
    }

    /**
     * Override to always return false - this type should never match directly.
     */
    public function matchesExtension(string $extension): bool
    {
        return false;
    }
}
