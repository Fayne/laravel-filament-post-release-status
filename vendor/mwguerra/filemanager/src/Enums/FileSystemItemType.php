<?php

namespace MWGuerra\FileManager\Enums;

enum FileSystemItemType: string
{
    case Folder = 'folder';
    case File = 'file';

    public function label(): string
    {
        return match ($this) {
            self::Folder => 'Folder',
            self::File => 'File',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Folder => 'heroicon-o-folder',
            self::File => 'heroicon-o-document',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Folder => 'primary',
            self::File => 'gray',
        };
    }
}
