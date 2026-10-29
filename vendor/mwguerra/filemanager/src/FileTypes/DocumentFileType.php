<?php

namespace MWGuerra\FileManager\FileTypes;

/**
 * File type for office documents.
 *
 * Supports Microsoft Office and OpenDocument formats:
 * Word, Excel, PowerPoint, and their ODF equivalents.
 *
 * Note: These files cannot be previewed in the browser directly.
 * Consider integrating with Office Online or Google Docs Viewer
 * for preview functionality.
 */
class DocumentFileType extends AbstractFileType
{
    public function identifier(): string
    {
        return 'document';
    }

    public function label(): string
    {
        return 'Document';
    }

    public function icon(): string
    {
        return 'heroicon-o-document-text';
    }

    public function iconColor(): string
    {
        return 'text-green-500';
    }

    public function filamentColor(): string
    {
        return 'warning';
    }

    public function supportedMimeTypes(): array
    {
        return [
            // Microsoft Word
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            // Microsoft Excel
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            // Microsoft PowerPoint
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            // OpenDocument
            'application/vnd.oasis.opendocument.text',
            'application/vnd.oasis.opendocument.spreadsheet',
            'application/vnd.oasis.opendocument.presentation',
            // Rich Text
            'application/rtf',
        ];
    }

    public function supportedExtensions(): array
    {
        return [
            // Word
            'doc',
            'docx',
            'odt',
            'rtf',
            // Excel
            'xls',
            'xlsx',
            'ods',
            // PowerPoint
            'ppt',
            'pptx',
            'odp',
        ];
    }

    public function canPreview(): bool
    {
        // Office documents cannot be previewed directly in browser
        // Override this if integrating with a document viewer service
        return false;
    }

    public function viewerComponent(): ?string
    {
        // No built-in viewer, uses fallback
        return null;
    }

    public function priority(): int
    {
        return 10;
    }

    public function metadata(): array
    {
        return [
            'office_type' => true,
            'requires_external_viewer' => true,
        ];
    }
}
