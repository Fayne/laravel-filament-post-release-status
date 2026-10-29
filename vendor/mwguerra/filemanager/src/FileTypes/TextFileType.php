<?php

namespace MWGuerra\FileManager\FileTypes;

/**
 * File type for text and code files.
 *
 * Supports plain text, markdown, JSON, XML, and various
 * programming language source files.
 */
class TextFileType extends AbstractFileType
{
    public function identifier(): string
    {
        return 'text';
    }

    public function label(): string
    {
        return 'Text File';
    }

    public function icon(): string
    {
        return 'heroicon-o-document-text';
    }

    public function iconColor(): string
    {
        return 'text-gray-500';
    }

    public function filamentColor(): string
    {
        return 'gray';
    }

    public function supportedMimeTypes(): array
    {
        return [
            'text/plain',
            'text/html',
            'text/css',
            'text/javascript',
            'text/markdown',
            'text/xml',
            'text/csv',
            'text/x-php',
            'text/x-python',
            'text/x-java-source',
            'text/x-c',
            'text/x-c++',
            'text/x-ruby',
            'text/x-yaml',
            'text/x-log',
            'application/json',
            'application/xml',
            'application/javascript',
            'application/x-yaml',
            'application/x-sh',
        ];
    }

    public function supportedExtensions(): array
    {
        return [
            // Plain text
            'txt',
            'log',
            'ini',
            'conf',
            'cfg',
            // Markup/data
            'md',
            'markdown',
            'json',
            'xml',
            'csv',
            'yml',
            'yaml',
            'toml',
            // Web
            'html',
            'htm',
            'css',
            'js',
            'ts',
            'jsx',
            'tsx',
            'vue',
            'svelte',
            // Programming
            'php',
            'py',
            'rb',
            'java',
            'kt',
            'c',
            'cpp',
            'h',
            'hpp',
            'cs',
            'go',
            'rs',
            'swift',
            // Shell/scripts
            'sh',
            'bash',
            'zsh',
            'fish',
            'bat',
            'ps1',
            // Database
            'sql',
            // Config
            'env',
            'gitignore',
            'dockerignore',
            'editorconfig',
        ];
    }

    public function canPreview(): bool
    {
        return true;
    }

    public function viewerComponent(): ?string
    {
        return 'filemanager::components.viewers.text';
    }

    public function priority(): int
    {
        return 10;
    }

    public function metadata(): array
    {
        return [
            'supports_syntax_highlighting' => true,
            'max_preview_size' => 1024 * 1024, // 1MB
        ];
    }
}
