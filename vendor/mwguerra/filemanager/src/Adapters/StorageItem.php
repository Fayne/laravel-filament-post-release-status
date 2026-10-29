<?php

namespace MWGuerra\FileManager\Adapters;

use MWGuerra\FileManager\Contracts\FileManagerItemInterface;
use MWGuerra\FileManager\FileTypeRegistry;

/**
 * A Data Transfer Object representing a file or folder from storage.
 *
 * This class does not use Eloquent - it's a simple PHP object that holds
 * file/folder information retrieved from Laravel's Storage facade.
 */
class StorageItem implements FileManagerItemInterface
{
    protected string $path;
    protected string $name;
    protected bool $isDirectory;
    protected ?int $size;
    protected ?string $mimeType;
    protected ?int $lastModified;
    protected string $disk;

    public function __construct(
        string $path,
        string $name,
        bool $isDirectory,
        ?int $size = null,
        ?string $mimeType = null,
        ?int $lastModified = null,
        string $disk = 'public'
    ) {
        $this->path = $path;
        $this->name = $name;
        $this->isDirectory = $isDirectory;
        $this->size = $size;
        $this->mimeType = $mimeType;
        $this->lastModified = $lastModified;
        $this->disk = $disk;
    }

    /**
     * Create a StorageItem from a file path.
     */
    public static function fromPath(string $path, string $disk = 'public', bool $isDirectory = false): static
    {
        $storage = \Illuminate\Support\Facades\Storage::disk($disk);
        $name = basename($path) ?: '/';

        if ($isDirectory) {
            return new static(
                path: $path,
                name: $name,
                isDirectory: true,
                size: null,
                mimeType: null,
                lastModified: null,
                disk: $disk
            );
        }

        $size = null;
        $mimeType = null;
        $lastModified = null;

        try {
            if ($storage->exists($path)) {
                $size = $storage->size($path);
                $mimeType = $storage->mimeType($path);
                $lastModified = $storage->lastModified($path);
            }
        } catch (\Exception $e) {
            // Some drivers may not support all operations
        }

        return new static(
            path: $path,
            name: $name,
            isDirectory: false,
            size: $size,
            mimeType: $mimeType,
            lastModified: $lastModified,
            disk: $disk
        );
    }

    public function getIdentifier(): string
    {
        return $this->path;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPath(): string
    {
        return '/' . ltrim($this->path, '/');
    }

    public function getParentPath(): ?string
    {
        if ($this->path === '' || $this->path === '/') {
            return null;
        }

        $parent = dirname($this->path);

        return $parent === '.' ? null : $parent;
    }

    public function isFolder(): bool
    {
        return $this->isDirectory;
    }

    public function isFile(): bool
    {
        return !$this->isDirectory;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getFormattedSize(): string
    {
        if ($this->size === null) {
            return '';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = $this->size;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function getExtension(): ?string
    {
        if ($this->isDirectory) {
            return null;
        }

        $ext = pathinfo($this->name, PATHINFO_EXTENSION);

        return $ext ?: null;
    }

    public function getLastModified(): ?int
    {
        return $this->lastModified;
    }

    public function getThumbnail(): ?string
    {
        // Storage mode doesn't support thumbnails by default
        // Could be extended to check for companion .thumb files
        return null;
    }

    public function getDuration(): ?int
    {
        // Duration requires reading file metadata
        // Could be extended with getID3 or similar
        return null;
    }

    public function getFormattedDuration(): string
    {
        $duration = $this->getDuration();

        if ($duration === null) {
            return '';
        }

        $hours = floor($duration / 3600);
        $minutes = floor(($duration % 3600) / 60);
        $seconds = $duration % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    public function isVideo(): bool
    {
        if ($this->mimeType && str_starts_with($this->mimeType, 'video/')) {
            return true;
        }

        $ext = strtolower($this->getExtension() ?? '');

        return in_array($ext, ['mp4', 'webm', 'mov', 'avi', 'mkv', 'flv', 'wmv', 'm4v', 'ogv']);
    }

    public function isImage(): bool
    {
        if ($this->mimeType && str_starts_with($this->mimeType, 'image/')) {
            return true;
        }

        $ext = strtolower($this->getExtension() ?? '');

        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico', 'tiff', 'tif']);
    }

    public function isAudio(): bool
    {
        if ($this->mimeType && str_starts_with($this->mimeType, 'audio/')) {
            return true;
        }

        $ext = strtolower($this->getExtension() ?? '');

        return in_array($ext, ['mp3', 'wav', 'ogg', 'flac', 'aac', 'm4a', 'wma', 'opus']);
    }

    public function isDocument(): bool
    {
        $ext = strtolower($this->getExtension() ?? '');

        return in_array($ext, [
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
            'odt', 'ods', 'odp', 'txt', 'rtf', 'csv',
            'md', 'json', 'xml', 'yml', 'yaml', 'html', 'css', 'js',
        ]);
    }

    public function getDepth(): int
    {
        if ($this->path === '' || $this->path === '/') {
            return 0;
        }

        return count(array_filter(explode('/', $this->path)));
    }

    /**
     * Get the storage disk name.
     */
    public function getDisk(): string
    {
        return $this->disk;
    }

    public function toArray(): array
    {
        return [
            'identifier' => $this->getIdentifier(),
            'name' => $this->getName(),
            'path' => $this->getPath(),
            'parent_path' => $this->getParentPath(),
            'is_folder' => $this->isFolder(),
            'is_file' => $this->isFile(),
            'size' => $this->getSize(),
            'formatted_size' => $this->getFormattedSize(),
            'mime_type' => $this->getMimeType(),
            'extension' => $this->getExtension(),
            'last_modified' => $this->getLastModified(),
            'thumbnail' => $this->getThumbnail(),
            'duration' => $this->getDuration(),
            'formatted_duration' => $this->getFormattedDuration(),
            'is_video' => $this->isVideo(),
            'is_image' => $this->isImage(),
            'is_audio' => $this->isAudio(),
            'is_document' => $this->isDocument(),
            'depth' => $this->getDepth(),
        ];
    }

    /**
     * Magic getter for property access.
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'id', 'identifier' => $this->getIdentifier(),
            'name' => $this->getName(),
            'path' => $this->getPath(),
            'size' => $this->getSize(),
            'duration' => $this->getDuration(),
            'thumbnail' => $this->getThumbnail(),
            'storage_path' => $this->path,
            default => null,
        };
    }
}
