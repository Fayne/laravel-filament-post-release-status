<?php

use MWGuerra\FileManager\Filament\Pages\FileSystem;
use MWGuerra\FileManager\Models\FileSystemItem;

beforeEach(function () {
    $this->testPath = sys_get_temp_dir() . '/filemanager-filesystem-unit-' . uniqid();
    mkdir($this->testPath, 0777, true);

    $this->app['config']->set('filesystems.disks.testing', [
        'driver' => 'local',
        'root' => $this->testPath,
    ]);

    $this->app['config']->set('filemanager.mode', 'storage');
    $this->app['config']->set('filemanager.model', FileSystemItem::class);
    $this->app['config']->set('filemanager.storage_mode.disk', 'testing');
    $this->app['config']->set('filemanager.file_system.navigation.label', 'File System');
    $this->app['config']->set('filemanager.file_system.navigation.icon', 'heroicon-o-server-stack');
    $this->app['config']->set('filemanager.file_system.navigation.sort', 2);
    $this->app['config']->set('filemanager.file_system.navigation.group', 'FileSystem');

    // Create test folder structure
    mkdir($this->testPath . '/documents', 0777, true);
    mkdir($this->testPath . '/images', 0777, true);
    file_put_contents($this->testPath . '/readme.txt', 'readme content');
    file_put_contents($this->testPath . '/documents/contract.pdf', 'pdf content');
});

afterEach(function () {
    if (isset($this->testPath) && is_dir($this->testPath)) {
        deleteDirectory($this->testPath);
    }
});

describe('page configuration', function () {
    it('has correct navigation icon', function () {
        expect(FileSystem::getNavigationIcon())->toBe('heroicon-o-server-stack');
    });

    it('has correct navigation label', function () {
        expect(FileSystem::getNavigationLabel())->toBe('File System');
    });

    it('has correct navigation sort', function () {
        expect(FileSystem::getNavigationSort())->toBe(2);
    });

    it('has correct navigation group', function () {
        expect(FileSystem::getNavigationGroup())->toBe('FileSystem');
    });

    it('has correct slug', function () {
        expect(FileSystem::getSlug())->toBe('file-system');
    });
});

describe('mode', function () {
    it('returns storage mode', function () {
        $page = new FileSystem();

        expect($page->getMode())->toBe('storage');
    });

    it('detects storage mode correctly', function () {
        $page = new FileSystem();

        expect($page->isStorageMode())->toBeTrue();
        expect($page->isDatabaseMode())->toBeFalse();
    });

    it('is read-only', function () {
        $page = new FileSystem();

        expect($page->isReadOnly())->toBeTrue();
    });
});

describe('storage disk property', function () {
    it('returns configured storage disk', function () {
        $this->app['config']->set('filemanager.storage_mode.disk', 'public');

        $page = new FileSystem();

        expect($page->storageDisk)->toBe('public');
    });

    it('returns null when disk not configured', function () {
        $this->app['config']->set('filemanager.storage_mode.disk', null);

        $page = new FileSystem();

        // When explicitly set to null, config returns null
        expect($page->storageDisk)->toBeNull();
    });
});

describe('mount', function () {
    it('mounts with default state', function () {
        $page = new FileSystem();
        $page->mount();

        expect($page->currentPath)->toBeNull();
        expect($page->viewMode)->toBe('grid');
        expect($page->selectedItems)->toBe([]);
        expect($page->expandedFolders)->toBe(['root']);
    });
});

describe('navigation', function () {
    it('navigates to a folder', function () {
        $page = new FileSystem();
        $page->mount();

        $page->navigateTo('documents');

        expect($page->currentPath)->toBe('documents');
        expect($page->selectedItems)->toBe([]);
    });

    it('navigates to root', function () {
        $page = new FileSystem();
        $page->mount();
        $page->currentPath = 'documents';

        $page->navigateTo(null);

        expect($page->currentPath)->toBeNull();
    });
});

describe('folder expansion', function () {
    it('toggles folder expansion', function () {
        $page = new FileSystem();
        $page->mount();

        expect($page->expandedFolders)->toBe(['root']);

        $page->toggleFolder('documents');
        expect($page->expandedFolders)->toContain('documents');

        $page->toggleFolder('documents');
        expect($page->expandedFolders)->not->toContain('documents');
    });

    it('checks if folder is expanded', function () {
        $page = new FileSystem();
        $page->mount();

        expect($page->isFolderExpanded(null))->toBeTrue();
        expect($page->isFolderExpanded('documents'))->toBeFalse();

        $page->toggleFolder('documents');

        expect($page->isFolderExpanded('documents'))->toBeTrue();
    });
});

describe('view mode', function () {
    it('sets view mode', function () {
        $page = new FileSystem();
        $page->mount();

        expect($page->viewMode)->toBe('grid');

        $page->setViewMode('list');

        expect($page->viewMode)->toBe('list');
    });
});

describe('selection', function () {
    it('toggles single item selection', function () {
        $page = new FileSystem();
        $page->mount();

        $page->toggleSelection('readme.txt');
        expect($page->selectedItems)->toBe(['readme.txt']);

        $page->toggleSelection('readme.txt');
        expect($page->selectedItems)->toBe([]);
    });

    it('toggles multi-selection', function () {
        $page = new FileSystem();
        $page->mount();

        $page->toggleSelection('readme.txt', true);
        $page->toggleSelection('documents', true);

        expect($page->selectedItems)->toBe(['readme.txt', 'documents']);
    });

    it('clears selection', function () {
        $page = new FileSystem();
        $page->mount();
        $page->selectedItems = ['readme.txt'];

        $page->clearSelection();

        expect($page->selectedItems)->toBe([]);
    });

    it('checks if item is selected', function () {
        $page = new FileSystem();
        $page->mount();
        $page->toggleSelection('readme.txt');

        expect($page->isSelected('readme.txt'))->toBeTrue();
        expect($page->isSelected('documents'))->toBeFalse();
    });
});

describe('title', function () {
    it('returns correct title', function () {
        $page = new FileSystem();

        expect($page->getTitle())->toBe('File System');
    });
});
