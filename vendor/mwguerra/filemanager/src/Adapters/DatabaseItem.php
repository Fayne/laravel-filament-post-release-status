<?php

namespace MWGuerra\FileManager\Adapters;

use MWGuerra\FileManager\Contracts\FileManagerItemInterface;
use MWGuerra\FileManager\Contracts\FileSystemItemInterface;

/**
 * Wrapper that adapts a FileSystemItemInterface (Eloquent model) to FileManagerItemInterface.
 *
 * This allows the FileManager to work with database models through the same
 * interface as storage items.
 */
class DatabaseItem implements FileManagerItemInterface
{
    protected FileSystemItemInterface $model;

    public function __construct(FileSystemItemInterface $model)
    {
        $this->model = $model;
    }

    /**
     * Get the underlying Eloquent model.
     */
    public function getModel(): FileSystemItemInterface
    {
        return $this->model;
    }

    public function getIdentifier(): string
    {
        return (string) $this->model->id;
    }

    public function getName(): string
    {
        return $this->model->name;
    }

    public function getPath(): string
    {
        return $this->model->getFullPath();
    }

    public function getParentPath(): ?string
    {
        if (!$this->model->parent_id) {
            return null;
        }

        $parent = $this->model->parent;

        return $parent ? $parent->getFullPath() : null;
    }

    public function isFolder(): bool
    {
        return $this->model->isFolder();
    }

    public function isFile(): bool
    {
        return $this->model->isFile();
    }

    public function getSize(): ?int
    {
        return $this->model->size;
    }

    public function getFormattedSize(): string
    {
        return $this->model->getFormattedSize();
    }

    public function getMimeType(): ?string
    {
        // Database mode doesn't typically store mime type directly
        // It uses file_type enum instead
        return null;
    }

    public function getExtension(): ?string
    {
        if ($this->isFolder()) {
            return null;
        }

        return pathinfo($this->model->name, PATHINFO_EXTENSION) ?: null;
    }

    public function getLastModified(): ?int
    {
        if ($this->model->updated_at) {
            return $this->model->updated_at->timestamp;
        }

        return null;
    }

    public function getThumbnail(): ?string
    {
        return $this->model->thumbnail;
    }

    public function getDuration(): ?int
    {
        return $this->model->duration;
    }

    public function getFormattedDuration(): string
    {
        return $this->model->getFormattedDuration();
    }

    public function isVideo(): bool
    {
        return $this->model->isVideo();
    }

    public function isImage(): bool
    {
        return $this->model->isImage();
    }

    public function isAudio(): bool
    {
        return $this->model->isAudio();
    }

    public function isDocument(): bool
    {
        return $this->model->isDocument();
    }

    public function getDepth(): int
    {
        return $this->model->getDepth();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->model->id,
            'identifier' => $this->getIdentifier(),
            'name' => $this->getName(),
            'path' => $this->getPath(),
            'parent_path' => $this->getParentPath(),
            'parent_id' => $this->model->parent_id,
            'is_folder' => $this->isFolder(),
            'is_file' => $this->isFile(),
            'size' => $this->getSize(),
            'formatted_size' => $this->getFormattedSize(),
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
            'storage_path' => $this->model->storage_path,
        ];
    }

    /**
     * Magic getter to allow property access to underlying model.
     */
    public function __get(string $name): mixed
    {
        // First check if the model has the property
        if (isset($this->model->$name)) {
            return $this->model->$name;
        }

        // Map some common names
        return match ($name) {
            'identifier' => $this->getIdentifier(),
            'path' => $this->getPath(),
            default => null,
        };
    }

    /**
     * Forward method calls to the underlying model.
     */
    public function __call(string $name, array $arguments): mixed
    {
        return $this->model->$name(...$arguments);
    }
}
