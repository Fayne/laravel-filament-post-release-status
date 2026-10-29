<?php

namespace MWGuerra\FileManager\FileTypes;

/**
 * File type for image files.
 *
 * Supports common image formats: JPEG, PNG, GIF, WebP, SVG, etc.
 */
class ImageFileType extends AbstractFileType
{
    public function identifier(): string
    {
        return 'image';
    }

    public function label(): string
    {
        return 'Image';
    }

    public function icon(): string
    {
        return 'heroicon-o-photo';
    }

    public function iconColor(): string
    {
        return 'text-blue-400';
    }

    public function filamentColor(): string
    {
        return 'info';
    }

    public function supportedMimeTypes(): array
    {
        return [
            'image/*', // Wildcard for all image types
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            'image/bmp',
            'image/tiff',
            'image/x-icon',
            'image/heic',
            'image/heif',
            'image/avif',
        ];
    }

    public function supportedExtensions(): array
    {
        return [
            'jpg',
            'jpeg',
            'png',
            'gif',
            'webp',
            'svg',
            'bmp',
            'tiff',
            'tif',
            'ico',
            'heic',
            'heif',
            'avif',
        ];
    }

    public function canPreview(): bool
    {
        return true;
    }

    public function viewerComponent(): ?string
    {
        return 'filemanager::components.viewers.image';
    }

    public function priority(): int
    {
        return 10;
    }

    public function metadata(): array
    {
        return [
            'supports_thumbnail' => true,
            'supports_dimensions' => true,
        ];
    }
}
