<?php

namespace MWGuerra\FileManager\Contracts;

/**
 * Contract for file type definitions.
 *
 * Implement this interface to create custom file types that can be
 * registered with the FileTypeRegistry.
 *
 * Example usage:
 * ```php
 * class ThreeDModelFileType extends AbstractFileType
 * {
 *     public function identifier(): string
 *     {
 *         return '3d-model';
 *     }
 *
 *     public function supportedMimeTypes(): array
 *     {
 *         return ['model/gltf-binary', 'model/gltf+json'];
 *     }
 *
 *     public function supportedExtensions(): array
 *     {
 *         return ['glb', 'gltf', 'obj', 'fbx'];
 *     }
 *
 *     public function viewerComponent(): ?string
 *     {
 *         return 'filemanager::viewers.3d-model';
 *     }
 * }
 * ```
 */
interface FileTypeContract
{
    /**
     * Get the unique identifier for this file type.
     *
     * This is used internally for registration and lookup.
     * Examples: 'video', 'image', 'pdf', '3d-model'
     */
    public function identifier(): string;

    /**
     * Get the human-readable label for this file type.
     *
     * Examples: 'Video', 'Image', 'PDF Document', '3D Model'
     */
    public function label(): string;

    /**
     * Get the icon for this file type.
     *
     * Should be a Heroicon identifier.
     * Examples: 'heroicon-o-video-camera', 'heroicon-o-photo'
     */
    public function icon(): string;

    /**
     * Get the icon color class for this file type.
     *
     * Can be a Tailwind color class or Filament color name.
     * Examples: 'text-blue-500', 'text-purple-400'
     */
    public function iconColor(): string;

    /**
     * Get the Filament color name for badges and indicators.
     *
     * Examples: 'success', 'info', 'warning', 'danger', 'gray'
     */
    public function filamentColor(): string;

    /**
     * Get the MIME types supported by this file type.
     *
     * Examples: ['video/mp4', 'video/webm', 'video/quicktime']
     *
     * @return array<string>
     */
    public function supportedMimeTypes(): array;

    /**
     * Get the file extensions supported by this file type.
     *
     * Extensions should be lowercase without the leading dot.
     * Examples: ['mp4', 'webm', 'mov', 'avi']
     *
     * @return array<string>
     */
    public function supportedExtensions(): array;

    /**
     * Check if this file type can be previewed in the browser.
     */
    public function canPreview(): bool;

    /**
     * Get the blade component name for rendering the preview viewer.
     *
     * Return null if no custom viewer is needed (will use fallback).
     * Examples: 'filemanager::viewers.video', 'my-package::viewers.custom'
     */
    public function viewerComponent(): ?string;

    /**
     * Get the priority for this file type (higher = matched first).
     *
     * Used when multiple types could match the same MIME type.
     * Default should be 0. Built-in types use 10.
     */
    public function priority(): int;

    /**
     * Check if this file type matches a given MIME type.
     */
    public function matchesMimeType(string $mimeType): bool;

    /**
     * Check if this file type matches a given file extension.
     */
    public function matchesExtension(string $extension): bool;

    /**
     * Get additional metadata for this file type.
     *
     * Can be used for custom properties specific to certain file types.
     *
     * @return array<string, mixed>
     */
    public function metadata(): array;
}
