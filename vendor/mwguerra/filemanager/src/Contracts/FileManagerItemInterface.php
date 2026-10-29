<?php

namespace MWGuerra\FileManager\Contracts;

/**
 * Interface for file manager items.
 *
 * This is a simplified interface that works for both database-backed
 * items and storage-backed items.
 */
interface FileManagerItemInterface
{
    /**
     * Get the unique identifier for this item.
     *
     * For database mode, this is the ID.
     * For storage mode, this is the full path.
     */
    public function getIdentifier(): string;

    /**
     * Get the item name.
     */
    public function getName(): string;

    /**
     * Get the full path.
     */
    public function getPath(): string;

    /**
     * Get the parent path.
     */
    public function getParentPath(): ?string;

    /**
     * Check if this is a folder.
     */
    public function isFolder(): bool;

    /**
     * Check if this is a file.
     */
    public function isFile(): bool;

    /**
     * Get the file size in bytes (null for folders).
     */
    public function getSize(): ?int;

    /**
     * Get the formatted file size (e.g., "1.5 MB").
     */
    public function getFormattedSize(): string;

    /**
     * Get the MIME type (null for folders).
     */
    public function getMimeType(): ?string;

    /**
     * Get the file extension (null for folders).
     */
    public function getExtension(): ?string;

    /**
     * Get the last modified timestamp.
     */
    public function getLastModified(): ?int;

    /**
     * Get the thumbnail URL (if available).
     */
    public function getThumbnail(): ?string;

    /**
     * Get the duration in seconds (for audio/video, null otherwise).
     */
    public function getDuration(): ?int;

    /**
     * Get the formatted duration (e.g., "1:30").
     */
    public function getFormattedDuration(): string;

    /**
     * Check if this is a video file.
     */
    public function isVideo(): bool;

    /**
     * Check if this is an image file.
     */
    public function isImage(): bool;

    /**
     * Check if this is an audio file.
     */
    public function isAudio(): bool;

    /**
     * Check if this is a document file.
     */
    public function isDocument(): bool;

    /**
     * Get the depth level in the hierarchy.
     */
    public function getDepth(): int;

    /**
     * Convert to array for JSON serialization.
     */
    public function toArray(): array;
}
