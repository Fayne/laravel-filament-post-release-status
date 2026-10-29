<?php

namespace MWGuerra\FileManager\FileTypes;

/**
 * File type for archive/compressed files.
 *
 * Supports common archive formats: ZIP, RAR, 7z, TAR, GZIP, etc.
 */
class ArchiveFileType extends AbstractFileType
{
    public function identifier(): string
    {
        return 'archive';
    }

    public function label(): string
    {
        return 'Archive';
    }

    public function icon(): string
    {
        return 'heroicon-o-archive-box';
    }

    public function iconColor(): string
    {
        return 'text-yellow-500';
    }

    public function filamentColor(): string
    {
        return 'warning';
    }

    public function supportedMimeTypes(): array
    {
        return [
            'application/zip',
            'application/x-zip-compressed',
            'application/x-rar-compressed',
            'application/vnd.rar',
            'application/x-7z-compressed',
            'application/x-tar',
            'application/gzip',
            'application/x-gzip',
            'application/x-bzip2',
            'application/x-xz',
            'application/x-lzip',
        ];
    }

    public function supportedExtensions(): array
    {
        return [
            'zip',
            'rar',
            '7z',
            'tar',
            'gz',
            'tgz',
            'bz2',
            'xz',
            'lz',
            'tar.gz',
            'tar.bz2',
            'tar.xz',
        ];
    }

    public function canPreview(): bool
    {
        // Archives cannot be previewed directly
        return false;
    }

    public function viewerComponent(): ?string
    {
        return null;
    }

    public function priority(): int
    {
        return 10;
    }

    public function metadata(): array
    {
        return [
            'can_extract' => true,
        ];
    }
}
