<?php

namespace MWGuerra\FileManager\FileTypes;

/**
 * File type for video files.
 *
 * Supports common video formats: MP4, WebM, MOV, AVI, MKV, etc.
 */
class VideoFileType extends AbstractFileType
{
    public function identifier(): string
    {
        return 'video';
    }

    public function label(): string
    {
        return 'Video';
    }

    public function icon(): string
    {
        return 'heroicon-o-video-camera';
    }

    public function iconColor(): string
    {
        return 'text-red-400';
    }

    public function filamentColor(): string
    {
        return 'success';
    }

    public function supportedMimeTypes(): array
    {
        return [
            'video/*', // Wildcard for all video types
            'video/mp4',
            'video/webm',
            'video/ogg',
            'video/quicktime',
            'video/x-msvideo',
            'video/x-matroska',
            'video/x-flv',
            'video/3gpp',
            'video/3gpp2',
        ];
    }

    public function supportedExtensions(): array
    {
        return [
            'mp4',
            'webm',
            'ogg',
            'ogv',
            'mov',
            'avi',
            'mkv',
            'flv',
            '3gp',
            'm4v',
            'wmv',
        ];
    }

    public function canPreview(): bool
    {
        return true;
    }

    public function viewerComponent(): ?string
    {
        return 'filemanager::components.viewers.video';
    }

    public function priority(): int
    {
        return 10;
    }

    public function metadata(): array
    {
        return [
            'supports_streaming' => true,
            'supports_duration' => true,
            'supports_thumbnail' => true,
        ];
    }
}
