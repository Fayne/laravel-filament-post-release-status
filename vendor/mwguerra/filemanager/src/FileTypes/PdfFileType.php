<?php

namespace MWGuerra\FileManager\FileTypes;

/**
 * File type for PDF documents.
 *
 * PDF files get their own type because they can be previewed
 * in the browser using an iframe or PDF.js.
 */
class PdfFileType extends AbstractFileType
{
    public function identifier(): string
    {
        return 'pdf';
    }

    public function label(): string
    {
        return 'PDF Document';
    }

    public function icon(): string
    {
        return 'heroicon-o-document-text';
    }

    public function iconColor(): string
    {
        return 'text-red-500';
    }

    public function filamentColor(): string
    {
        return 'danger';
    }

    public function supportedMimeTypes(): array
    {
        return [
            'application/pdf',
        ];
    }

    public function supportedExtensions(): array
    {
        return [
            'pdf',
        ];
    }

    public function canPreview(): bool
    {
        return true;
    }

    public function viewerComponent(): ?string
    {
        return 'filemanager::components.viewers.pdf';
    }

    public function priority(): int
    {
        // Higher priority than generic document type
        return 15;
    }
}
