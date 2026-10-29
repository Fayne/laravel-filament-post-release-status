<?php

namespace MWGuerra\FileManager\Filament\Resources\FileSystemItemResource\Pages;

use MWGuerra\FileManager\Filament\Resources\FileSystemItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFileSystemItems extends ListRecords
{
    protected static string $resource = FileSystemItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
