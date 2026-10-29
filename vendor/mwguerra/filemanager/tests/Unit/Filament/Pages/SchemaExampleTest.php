<?php

use MWGuerra\FileManager\Filament\Pages\SchemaExample;
use MWGuerra\FileManager\Models\FileSystemItem;

beforeEach(function () {
    $this->app['config']->set('filemanager.model', FileSystemItem::class);
    $this->app['config']->set('filemanager.file_manager.navigation.sort', 1);
    $this->app['config']->set('filemanager.file_manager.navigation.group', 'FileManager');
});

describe('page configuration', function () {
    it('has correct navigation icon', function () {
        expect(SchemaExample::getNavigationIcon())->toBe('heroicon-o-squares-plus');
    });

    it('has correct navigation label', function () {
        expect(SchemaExample::getNavigationLabel())->toBe('Schema Example');
    });

    it('has correct navigation sort', function () {
        expect(SchemaExample::getNavigationSort())->toBe(11);
    });

    it('has correct navigation group', function () {
        expect(SchemaExample::getNavigationGroup())->toBe('FileManager');
    });

    it('has correct slug', function () {
        expect(SchemaExample::getSlug())->toBe('schema-example');
    });
});

describe('title', function () {
    it('returns correct title', function () {
        $page = new SchemaExample();

        expect($page->getTitle())->toBe('Schema Example');
    });
});

describe('mount', function () {
    it('can be instantiated', function () {
        $page = new SchemaExample();

        expect($page)->toBeInstanceOf(SchemaExample::class);
    });

    it('has data property', function () {
        $page = new SchemaExample();

        expect($page->data)->toBe([]);
    });
});

describe('actions', function () {
    it('has submitAction method', function () {
        expect(method_exists(SchemaExample::class, 'submitAction'))->toBeTrue();
    });

    it('has getFormData method', function () {
        expect(method_exists(SchemaExample::class, 'getFormData'))->toBeTrue();
    });

    it('has getHeaderActions method', function () {
        expect(method_exists(SchemaExample::class, 'getHeaderActions'))->toBeTrue();
    });

    it('has form method', function () {
        expect(method_exists(SchemaExample::class, 'form'))->toBeTrue();
    });
});

describe('traits', function () {
    it('uses InteractsWithSchemas trait', function () {
        $traits = class_uses_recursive(SchemaExample::class);

        expect($traits)->toContain(\Filament\Schemas\Concerns\InteractsWithSchemas::class);
    });

    it('uses InteractsWithActions trait', function () {
        $traits = class_uses_recursive(SchemaExample::class);

        expect($traits)->toContain(\Filament\Actions\Concerns\InteractsWithActions::class);
    });
});

describe('interfaces', function () {
    it('implements HasSchemas', function () {
        expect(is_a(SchemaExample::class, \Filament\Schemas\Contracts\HasSchemas::class, true))->toBeTrue();
    });

    it('implements HasActions', function () {
        expect(is_a(SchemaExample::class, \Filament\Actions\Contracts\HasActions::class, true))->toBeTrue();
    });
});
