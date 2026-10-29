<?php

use Illuminate\Support\Facades\Storage;
use MWGuerra\FileManager\Filament\Pages\FileManager;
use MWGuerra\FileManager\Models\FileSystemItem;

beforeEach(function () {
    Storage::fake('testing');
    $this->app['config']->set('filemanager.mode', 'database');
    $this->app['config']->set('filemanager.model', FileSystemItem::class);
    $this->app['config']->set('filemanager.storage_mode.disk', 'testing');
    $this->app['config']->set('filemanager.upload.disk', 'testing');
    $this->app['config']->set('filemanager.upload.max_file_size', 10240);
    $this->app['config']->set('filemanager.file_manager.navigation.label', 'File Manager');
    $this->app['config']->set('filemanager.file_manager.navigation.icon', 'heroicon-o-folder');
    $this->app['config']->set('filemanager.file_manager.navigation.sort', 1);
    $this->app['config']->set('filemanager.file_manager.navigation.group', 'FileManager');
});

describe('page configuration', function () {
    it('has correct navigation icon', function () {
        expect(FileManager::getNavigationIcon())->toBe('heroicon-o-folder');
    });

    it('has correct navigation label', function () {
        expect(FileManager::getNavigationLabel())->toBe('File Manager');
    });

    it('has correct navigation sort', function () {
        expect(FileManager::getNavigationSort())->toBe(1);
    });

    it('has correct navigation group', function () {
        expect(FileManager::getNavigationGroup())->toBe('FileManager');
    });
});

describe('mount', function () {
    it('mounts with default state', function () {
        $page = new FileManager();
        $page->mount();

        expect($page->currentPath)->toBeNull();
        expect($page->viewMode)->toBe('grid');
        expect($page->selectedItems)->toBe([]);
        expect($page->expandedFolders)->toBe(['root']);
    });
});

describe('mode', function () {
    it('returns configured mode', function () {
        $page = new FileManager();

        expect($page->getMode())->toBe('database');
    });

    it('detects database mode', function () {
        $page = new FileManager();

        expect($page->isDatabaseMode())->toBeTrue();
        expect($page->isStorageMode())->toBeFalse();
    });

    it('is not read-only', function () {
        $page = new FileManager();

        expect($page->isReadOnly())->toBeFalse();
    });
});

describe('navigation', function () {
    it('navigates to a folder', function () {
        $folder = FileSystemItem::factory()->folder()->create();

        $page = new FileManager();
        $page->mount();
        $page->navigateTo((string) $folder->id);

        expect($page->currentPath)->toBe((string) $folder->id);
        expect($page->selectedItems)->toBe([]);
    });

    it('navigates to folder by id', function () {
        $folder = FileSystemItem::factory()->folder()->create();

        $page = new FileManager();
        $page->mount();
        $page->navigateToId($folder->id);

        expect($page->currentPath)->toBe((string) $folder->id);
    });

    it('navigates to root by id', function () {
        $page = new FileManager();
        $page->mount();
        $page->currentPath = '1';

        $page->navigateToId(null);

        expect($page->currentPath)->toBeNull();
    });
});

describe('folder expansion', function () {
    it('toggles folder expansion', function () {
        $folder = FileSystemItem::factory()->folder()->create();

        $page = new FileManager();
        $page->mount();

        expect($page->expandedFolders)->toBe(['root']);

        $page->toggleFolder((string) $folder->id);
        expect($page->expandedFolders)->toContain((string) $folder->id);

        $page->toggleFolder((string) $folder->id);
        expect($page->expandedFolders)->not->toContain((string) $folder->id);
    });

    it('checks if folder is expanded', function () {
        $folder = FileSystemItem::factory()->folder()->create();

        $page = new FileManager();
        $page->mount();

        expect($page->isFolderExpanded(null))->toBeTrue();
        expect($page->isFolderExpanded((string) $folder->id))->toBeFalse();

        $page->toggleFolder((string) $folder->id);

        expect($page->isFolderExpanded((string) $folder->id))->toBeTrue();
    });
});

describe('view mode', function () {
    it('sets view mode', function () {
        $page = new FileManager();
        $page->mount();

        expect($page->viewMode)->toBe('grid');

        $page->setViewMode('list');

        expect($page->viewMode)->toBe('list');
    });
});

describe('selection', function () {
    it('toggles single item selection', function () {
        $file = FileSystemItem::factory()->create();

        $page = new FileManager();
        $page->mount();

        $page->toggleSelection((string) $file->id);
        expect($page->selectedItems)->toBe([(string) $file->id]);

        $page->toggleSelection((string) $file->id);
        expect($page->selectedItems)->toBe([]);
    });

    it('toggles multi-selection', function () {
        $file1 = FileSystemItem::factory()->create();
        $file2 = FileSystemItem::factory()->create();

        $page = new FileManager();
        $page->mount();

        $page->toggleSelection((string) $file1->id, true);
        $page->toggleSelection((string) $file2->id, true);

        expect($page->selectedItems)->toBe([(string) $file1->id, (string) $file2->id]);
    });

    it('clears selection', function () {
        $file = FileSystemItem::factory()->create();

        $page = new FileManager();
        $page->mount();
        $page->selectedItems = [(string) $file->id];

        $page->clearSelection();

        expect($page->selectedItems)->toBe([]);
    });

    it('checks if item is selected', function () {
        $file = FileSystemItem::factory()->create();

        $page = new FileManager();
        $page->mount();
        $page->toggleSelection((string) $file->id);

        expect($page->isSelected((string) $file->id))->toBeTrue();
    });
});

describe('upload', function () {
    it('clears uploaded files', function () {
        $page = new FileManager();
        $page->mount();
        $page->uploadedFiles = ['file1', 'file2'];

        $page->clearUploadedFiles();

        expect($page->uploadedFiles)->toBe([]);
    });
});

describe('move', function () {
    it('sets move target', function () {
        $folder = FileSystemItem::factory()->folder()->create();

        $page = new FileManager();
        $page->mount();

        $page->setMoveTarget((string) $folder->id);

        expect($page->moveTargetPath)->toBe((string) $folder->id);
    });
});

describe('storage disk property', function () {
    it('returns storage disk in storage mode', function () {
        $this->app['config']->set('filemanager.mode', 'storage');
        $this->app['config']->set('filemanager.storage_mode.disk', 'public');

        $page = new FileManager();

        expect($page->storageDisk)->toBe('public');
    });

    it('returns null storage disk in database mode', function () {
        $this->app['config']->set('filemanager.mode', 'database');

        $page = new FileManager();

        expect($page->storageDisk)->toBeNull();
    });
});

describe('title', function () {
    it('returns title from config', function () {
        $page = new FileManager();

        expect($page->getTitle())->toBe('File Manager');
    });

    it('returns custom title from config', function () {
        $this->app['config']->set('filemanager.file_manager.navigation.label', 'My Files');

        $page = new FileManager();

        expect($page->getTitle())->toBe('My Files');
    });
});

describe('preview', function () {
    it('opens preview for file', function () {
        $file = FileSystemItem::factory()->create(['type' => 'file']);

        $page = new FileManager();
        $page->mount();
        $page->openPreview((string) $file->id);

        expect($page->previewItemId)->toBe((string) $file->id);
    });

    it('does not open preview for folder', function () {
        $folder = FileSystemItem::factory()->folder()->create();

        $page = new FileManager();
        $page->mount();
        $page->openPreview((string) $folder->id);

        expect($page->previewItemId)->toBeNull();
    });

    it('closes preview', function () {
        $file = FileSystemItem::factory()->create(['type' => 'file']);

        $page = new FileManager();
        $page->mount();
        $page->previewItemId = (string) $file->id;
        $page->closePreview();

        expect($page->previewItemId)->toBeNull();
    });

    it('returns null preview url when no item previewed', function () {
        $page = new FileManager();
        $page->mount();

        expect($page->getPreviewUrl())->toBeNull();
    });
});

describe('open move dialog', function () {
    it('opens move dialog for single item', function () {
        $file = FileSystemItem::factory()->create();

        $page = new FileManager();
        $page->mount();
        $page->openMoveDialog((string) $file->id);

        expect($page->itemToMoveId)->toBe((string) $file->id);
        expect($page->itemsToMove)->toBe([]);
        expect($page->moveTargetPath)->toBeNull();
    });

    it('opens move dialog for selected items', function () {
        $file1 = FileSystemItem::factory()->create();
        $file2 = FileSystemItem::factory()->create();

        $page = new FileManager();
        $page->mount();
        $page->selectedItems = [(string) $file1->id, (string) $file2->id];
        $page->openMoveDialogForSelected();

        expect($page->itemsToMove)->toBe([(string) $file1->id, (string) $file2->id]);
        expect($page->itemToMoveId)->toBeNull();
    });

    it('does not open move dialog when no items selected', function () {
        $page = new FileManager();
        $page->mount();
        $page->openMoveDialogForSelected();

        expect($page->itemsToMove)->toBe([]);
    });
});

describe('rename dialog', function () {
    it('opens rename dialog for item', function () {
        $file = FileSystemItem::factory()->create(['name' => 'test-file.txt']);

        $page = new FileManager();
        $page->mount();
        $page->openRenameDialog((string) $file->id);

        expect($page->itemToRenameId)->toBe((string) $file->id);
        expect($page->renameItemName)->toBe('test-file.txt');
    });
});

describe('subfolder dialog', function () {
    it('opens create subfolder dialog', function () {
        $folder = FileSystemItem::factory()->folder()->create();

        $page = new FileManager();
        $page->mount();
        $page->openCreateSubfolderDialog((string) $folder->id);

        expect($page->subfolderParentPath)->toBe((string) $folder->id);
        expect($page->subfolderName)->toBe('');
    });
});

describe('selection actions', function () {
    it('selects all items', function () {
        FileSystemItem::factory()->count(3)->create();

        $page = new FileManager();
        $page->mount();
        $page->selectAll();

        expect(count($page->selectedItems))->toBe(3);
    });

    it('checks all selected when all items selected', function () {
        $items = FileSystemItem::factory()->count(3)->create();

        $page = new FileManager();
        $page->mount();
        $page->selectedItems = $items->pluck('id')->map(fn ($id) => (string) $id)->toArray();

        expect($page->allSelected())->toBeTrue();
    });

    it('returns false for all selected when no items', function () {
        $page = new FileManager();
        $page->mount();

        expect($page->allSelected())->toBeFalse();
    });

    it('returns false when not all items selected', function () {
        $items = FileSystemItem::factory()->count(3)->create();

        $page = new FileManager();
        $page->mount();
        $page->selectedItems = [(string) $items->first()->id];

        expect($page->allSelected())->toBeFalse();
    });
});

describe('computed properties', function () {
    it('returns items collection', function () {
        FileSystemItem::factory()->count(2)->create();

        $page = new FileManager();
        $page->mount();

        expect($page->items)->toBeInstanceOf(\Illuminate\Support\Collection::class);
        expect($page->items)->toHaveCount(2);
    });

    it('returns folder tree', function () {
        FileSystemItem::factory()->folder()->create();

        $page = new FileManager();
        $page->mount();

        expect($page->folderTree)->toBeArray();
    });

    it('returns breadcrumbs', function () {
        $page = new FileManager();
        $page->mount();

        expect($page->breadcrumbs)->toBeArray();
    });

    it('returns all folders', function () {
        FileSystemItem::factory()->folder()->count(2)->create();

        $page = new FileManager();
        $page->mount();

        expect($page->allFolders)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    });

    it('returns root file count', function () {
        FileSystemItem::factory()->count(2)->create(['type' => 'file']);
        FileSystemItem::factory()->folder()->create();

        $page = new FileManager();
        $page->mount();

        expect($page->rootFileCount)->toBe(2);
    });

    it('returns preview item when set', function () {
        $file = FileSystemItem::factory()->create(['type' => 'file']);

        $page = new FileManager();
        $page->mount();
        $page->previewItemId = (string) $file->id;

        expect($page->previewItem)->not->toBeNull();
        expect($page->previewItem->getIdentifier())->toBe((string) $file->id);
    });

    it('returns null preview item when not set', function () {
        $page = new FileManager();
        $page->mount();

        expect($page->previewItem)->toBeNull();
    });

    it('returns item to move when set', function () {
        $file = FileSystemItem::factory()->create();

        $page = new FileManager();
        $page->mount();
        $page->itemToMoveId = (string) $file->id;

        expect($page->itemToMove)->not->toBeNull();
    });

    it('returns null item to move when not set', function () {
        $page = new FileManager();
        $page->mount();

        expect($page->itemToMove)->toBeNull();
    });

    it('returns subfolder parent when set', function () {
        $folder = FileSystemItem::factory()->folder()->create();

        $page = new FileManager();
        $page->mount();
        $page->subfolderParentPath = (string) $folder->id;

        expect($page->subfolderParent)->not->toBeNull();
    });

    it('returns null subfolder parent when not set', function () {
        $page = new FileManager();
        $page->mount();

        expect($page->subfolderParent)->toBeNull();
    });

    it('returns item to rename when set', function () {
        $file = FileSystemItem::factory()->create();

        $page = new FileManager();
        $page->mount();
        $page->itemToRenameId = (string) $file->id;

        expect($page->itemToRename)->not->toBeNull();
    });

    it('returns null item to rename when not set', function () {
        $page = new FileManager();
        $page->mount();

        expect($page->itemToRename)->toBeNull();
    });
});

describe('text content preview', function () {
    it('returns null when no preview item', function () {
        $page = new FileManager();
        $page->mount();

        expect($page->getTextContent())->toBeNull();
    });
});

describe('can access', function () {
    it('checks access using authorization service', function () {
        // This calls the authorization service
        $canAccess = FileManager::canAccess();

        // Without a user, authorization denies access by default
        expect($canAccess)->toBeFalse();
    });

    it('has static canAccess method', function () {
        expect(method_exists(FileManager::class, 'canAccess'))->toBeTrue();
    });
});

describe('handle item click', function () {
    it('navigates to folder on click', function () {
        $folder = FileSystemItem::factory()->folder()->create();

        $page = new FileManager();
        $page->mount();
        $page->handleItemClick((string) $folder->id);

        expect($page->currentPath)->toBe((string) $folder->id);
        expect($page->expandedFolders)->toContain((string) $folder->id);
    });

    it('opens preview for file on click', function () {
        $file = FileSystemItem::factory()->create(['type' => 'file']);

        $page = new FileManager();
        $page->mount();
        $page->handleItemClick((string) $file->id);

        expect($page->previewItemId)->toBe((string) $file->id);
    });
});
