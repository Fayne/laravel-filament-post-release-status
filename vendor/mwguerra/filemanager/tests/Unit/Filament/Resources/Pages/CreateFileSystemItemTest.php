<?php

use MWGuerra\FileManager\Filament\Resources\FileSystemItemResource;
use MWGuerra\FileManager\Filament\Resources\FileSystemItemResource\Pages\CreateFileSystemItem;
use MWGuerra\FileManager\Models\FileSystemItem;

beforeEach(function () {
    $this->app['config']->set('filemanager.model', FileSystemItem::class);
    $this->app['config']->set('filemanager.file_manager.navigation.group', 'FileManager');
});

describe('page configuration', function () {
    it('has correct resource class', function () {
        $page = new CreateFileSystemItem();

        $reflection = new ReflectionClass($page);
        $property = $reflection->getProperty('resource');

        expect($property->getValue($page))->toBe(FileSystemItemResource::class);
    });

    it('extends CreateRecord', function () {
        expect(is_subclass_of(CreateFileSystemItem::class, \Filament\Resources\Pages\CreateRecord::class))->toBeTrue();
    });

    it('can be instantiated', function () {
        $page = new CreateFileSystemItem();

        expect($page)->toBeInstanceOf(CreateFileSystemItem::class);
    });
});

describe('resource methods', function () {
    it('can get resource class', function () {
        expect(CreateFileSystemItem::getResource())->toBe(FileSystemItemResource::class);
    });
});

describe('inheritance', function () {
    it('inherits from Filament CreateRecord page', function () {
        $parents = class_parents(CreateFileSystemItem::class);

        expect($parents)->toContain(\Filament\Resources\Pages\CreateRecord::class);
    });

    it('uses Filament page traits', function () {
        $traits = class_uses_recursive(CreateFileSystemItem::class);

        // CreateRecord uses various traits from Filament
        expect(count($traits))->toBeGreaterThan(0);
    });
});
