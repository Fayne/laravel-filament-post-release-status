<?php

use Filament\Actions\DeleteAction;
use MWGuerra\FileManager\Filament\Resources\FileSystemItemResource;
use MWGuerra\FileManager\Filament\Resources\FileSystemItemResource\Pages\EditFileSystemItem;
use MWGuerra\FileManager\Models\FileSystemItem;

beforeEach(function () {
    $this->app['config']->set('filemanager.model', FileSystemItem::class);
    $this->app['config']->set('filemanager.file_manager.navigation.group', 'FileManager');
});

describe('page configuration', function () {
    it('has correct resource class', function () {
        $page = new EditFileSystemItem();

        $reflection = new ReflectionClass($page);
        $property = $reflection->getProperty('resource');

        expect($property->getValue($page))->toBe(FileSystemItemResource::class);
    });

    it('extends EditRecord', function () {
        expect(is_subclass_of(EditFileSystemItem::class, \Filament\Resources\Pages\EditRecord::class))->toBeTrue();
    });

    it('can be instantiated', function () {
        $page = new EditFileSystemItem();

        expect($page)->toBeInstanceOf(EditFileSystemItem::class);
    });
});

describe('header actions', function () {
    it('has getHeaderActions method', function () {
        expect(method_exists(EditFileSystemItem::class, 'getHeaderActions'))->toBeTrue();
    });

    it('returns array from getHeaderActions', function () {
        $page = new EditFileSystemItem();

        $reflection = new ReflectionClass($page);
        $method = $reflection->getMethod('getHeaderActions');

        $actions = $method->invoke($page);

        expect($actions)->toBeArray();
        expect($actions)->toHaveCount(1);
    });

    it('includes DeleteAction in header actions', function () {
        $page = new EditFileSystemItem();

        $reflection = new ReflectionClass($page);
        $method = $reflection->getMethod('getHeaderActions');

        $actions = $method->invoke($page);

        expect($actions[0])->toBeInstanceOf(DeleteAction::class);
    });
});

describe('resource methods', function () {
    it('can get resource class', function () {
        expect(EditFileSystemItem::getResource())->toBe(FileSystemItemResource::class);
    });
});

describe('inheritance', function () {
    it('inherits from Filament EditRecord page', function () {
        $parents = class_parents(EditFileSystemItem::class);

        expect($parents)->toContain(\Filament\Resources\Pages\EditRecord::class);
    });

    it('uses Filament page traits', function () {
        $traits = class_uses_recursive(EditFileSystemItem::class);

        // EditRecord uses various traits from Filament
        expect(count($traits))->toBeGreaterThan(0);
    });
});
