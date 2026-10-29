<?php

use Filament\Schemas\Schema;
use Filament\Tables\Table;
use MWGuerra\FileManager\Filament\Resources\FileSystemItemResource;
use MWGuerra\FileManager\Filament\Resources\FileSystemItemResource\Pages\CreateFileSystemItem;
use MWGuerra\FileManager\Filament\Resources\FileSystemItemResource\Pages\EditFileSystemItem;
use MWGuerra\FileManager\Filament\Resources\FileSystemItemResource\Pages\ListFileSystemItems;
use MWGuerra\FileManager\Models\FileSystemItem;

beforeEach(function () {
    $this->app['config']->set('filemanager.model', FileSystemItem::class);
    $this->app['config']->set('filemanager.file_manager.navigation.group', 'FileManager');
});

describe('resource configuration', function () {
    it('returns correct model class', function () {
        expect(FileSystemItemResource::getModel())->toBe(FileSystemItem::class);
    });

    it('returns correct navigation icon', function () {
        expect(FileSystemItemResource::getNavigationIcon())->toBe('heroicon-o-document-duplicate');
    });

    it('returns correct navigation sort', function () {
        expect(FileSystemItemResource::getNavigationSort())->toBe(2);
    });

    it('returns correct record title attribute', function () {
        expect(FileSystemItemResource::getRecordTitleAttribute())->toBe('name');
    });

    it('returns correct navigation group', function () {
        expect(FileSystemItemResource::getNavigationGroup())->toBe('FileManager');
    });

    it('returns navigation group from config', function () {
        $this->app['config']->set('filemanager.file_manager.navigation.group', 'Custom Group');

        expect(FileSystemItemResource::getNavigationGroup())->toBe('Custom Group');
    });
});

describe('pages configuration', function () {
    it('returns correct pages array', function () {
        $pages = FileSystemItemResource::getPages();

        expect($pages)->toHaveKey('index');
        expect($pages)->toHaveKey('create');
        expect($pages)->toHaveKey('edit');
    });

    it('has correct page classes', function () {
        $pages = FileSystemItemResource::getPages();

        expect($pages['index']->getPage())->toBe(ListFileSystemItems::class);
        expect($pages['create']->getPage())->toBe(CreateFileSystemItem::class);
        expect($pages['edit']->getPage())->toBe(EditFileSystemItem::class);
    });
});

describe('relations', function () {
    it('returns empty relations array', function () {
        expect(FileSystemItemResource::getRelations())->toBe([]);
    });
});

describe('form and table methods', function () {
    it('has static form method', function () {
        expect(method_exists(FileSystemItemResource::class, 'form'))->toBeTrue();
    });

    it('has static table method', function () {
        expect(method_exists(FileSystemItemResource::class, 'table'))->toBeTrue();
    });
});
