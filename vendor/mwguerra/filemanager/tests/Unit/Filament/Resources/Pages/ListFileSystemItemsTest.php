<?php

use Filament\Actions\CreateAction;
use MWGuerra\FileManager\Filament\Resources\FileSystemItemResource;
use MWGuerra\FileManager\Filament\Resources\FileSystemItemResource\Pages\ListFileSystemItems;
use MWGuerra\FileManager\Models\FileSystemItem;

beforeEach(function () {
    $this->app['config']->set('filemanager.model', FileSystemItem::class);
    $this->app['config']->set('filemanager.file_manager.navigation.group', 'FileManager');
});

describe('page configuration', function () {
    it('has correct resource class', function () {
        $page = new ListFileSystemItems();

        $reflection = new ReflectionClass($page);
        $property = $reflection->getProperty('resource');

        expect($property->getValue($page))->toBe(FileSystemItemResource::class);
    });

    it('extends ListRecords', function () {
        expect(is_subclass_of(ListFileSystemItems::class, \Filament\Resources\Pages\ListRecords::class))->toBeTrue();
    });

    it('can be instantiated', function () {
        $page = new ListFileSystemItems();

        expect($page)->toBeInstanceOf(ListFileSystemItems::class);
    });
});

describe('header actions', function () {
    it('has getHeaderActions method', function () {
        expect(method_exists(ListFileSystemItems::class, 'getHeaderActions'))->toBeTrue();
    });

    it('returns array from getHeaderActions', function () {
        $page = new ListFileSystemItems();

        $reflection = new ReflectionClass($page);
        $method = $reflection->getMethod('getHeaderActions');

        $actions = $method->invoke($page);

        expect($actions)->toBeArray();
        expect($actions)->toHaveCount(1);
    });

    it('includes CreateAction in header actions', function () {
        $page = new ListFileSystemItems();

        $reflection = new ReflectionClass($page);
        $method = $reflection->getMethod('getHeaderActions');

        $actions = $method->invoke($page);

        expect($actions[0])->toBeInstanceOf(CreateAction::class);
    });
});

describe('resource methods', function () {
    it('can get resource class', function () {
        expect(ListFileSystemItems::getResource())->toBe(FileSystemItemResource::class);
    });
});

describe('inheritance', function () {
    it('inherits from Filament ListRecords page', function () {
        $parents = class_parents(ListFileSystemItems::class);

        expect($parents)->toContain(\Filament\Resources\Pages\ListRecords::class);
    });

    it('uses Filament page traits', function () {
        $traits = class_uses_recursive(ListFileSystemItems::class);

        // ListRecords uses various traits from Filament
        expect(count($traits))->toBeGreaterThan(0);
    });
});
