<?php

namespace MWGuerra\FileManager\Enums;

enum FileType: string
{
    case Video = 'video';
    case Image = 'image';
    case Document = 'document';
    case Audio = 'audio';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Video => 'Video',
            self::Image => 'Image',
            self::Document => 'Document',
            self::Audio => 'Audio',
            self::Other => 'Other',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Video => 'heroicon-o-video-camera',
            self::Image => 'heroicon-o-photo',
            self::Document => 'heroicon-o-document-text',
            self::Audio => 'heroicon-o-musical-note',
            self::Other => 'heroicon-o-document',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Video => 'success',
            self::Image => 'info',
            self::Document => 'warning',
            self::Audio => 'danger',
            self::Other => 'gray',
        };
    }

    /**
     * Determine file type from mime type
     */
    public static function fromMimeType(string $mimeType): self
    {
        return match (true) {
            str_starts_with($mimeType, 'video/') => self::Video,
            str_starts_with($mimeType, 'image/') => self::Image,
            str_starts_with($mimeType, 'audio/') => self::Audio,
            in_array($mimeType, [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'text/plain',
            ]) => self::Document,
            default => self::Other,
        };
    }
}
