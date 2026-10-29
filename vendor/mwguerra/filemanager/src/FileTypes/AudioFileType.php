<?php

namespace MWGuerra\FileManager\FileTypes;

/**
 * File type for audio files.
 *
 * Supports common audio formats: MP3, WAV, OGG, FLAC, AAC, etc.
 */
class AudioFileType extends AbstractFileType
{
    public function identifier(): string
    {
        return 'audio';
    }

    public function label(): string
    {
        return 'Audio';
    }

    public function icon(): string
    {
        return 'heroicon-o-musical-note';
    }

    public function iconColor(): string
    {
        return 'text-purple-400';
    }

    public function filamentColor(): string
    {
        return 'danger';
    }

    public function supportedMimeTypes(): array
    {
        return [
            'audio/*', // Wildcard for all audio types
            'audio/mpeg',
            'audio/mp3',
            'audio/wav',
            'audio/x-wav',
            'audio/ogg',
            'audio/webm',
            'audio/flac',
            'audio/aac',
            'audio/x-aac',
            'audio/mp4',
            'audio/x-m4a',
            'audio/midi',
            'audio/x-midi',
        ];
    }

    public function supportedExtensions(): array
    {
        return [
            'mp3',
            'wav',
            'ogg',
            'oga',
            'webm',
            'flac',
            'aac',
            'm4a',
            'wma',
            'midi',
            'mid',
            'opus',
        ];
    }

    public function canPreview(): bool
    {
        return true;
    }

    public function viewerComponent(): ?string
    {
        return 'filemanager::components.viewers.audio';
    }

    public function priority(): int
    {
        return 10;
    }

    public function metadata(): array
    {
        return [
            'supports_duration' => true,
        ];
    }
}
