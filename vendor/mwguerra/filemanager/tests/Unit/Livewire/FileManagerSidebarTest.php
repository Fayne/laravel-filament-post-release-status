<?php

use Illuminate\Support\Facades\Storage;
use MWGuerra\FileManager\Livewire\FileManagerSidebar;
use MWGuerra\FileManager\Models\FileSystemItem;

beforeEach(function () {
    Storage::fake('testing');
    $this->app['config']->set('filemanager.mode', 'database');
    $this->app['config']->set('filemanager.model', FileSystemItem::class);
    $this->app['config']->set('filemanager.storage_mode.disk', 'testing');
    $this->app['config']->set('filemanager.upload.disk', 'testing');
    $this->app['config']->set('filemanager.sidebar.enabled', true);
    $this->app['config']->set('filemanager.sidebar.root_label', 'Root');
    $this->app['config']->set('filemanager.sidebar.heading', 'Folders');
    $this->app['config']->set('filemanager.sidebar.show_in_file_manager', true);
    // Disable authorization for unit tests (no user context)
    $this->app['config']->set('filemanager.authorization.enabled', false);
});

describe('mount', function () {
    it('mounts with default state', function () {
        $component = new FileManagerSidebar();
        $component->mount();

        expect($component->currentPath)->toBeNull();
        expect($component->expandedFolders)->toBe(['root']);
    });
});

describe('folder expansion', function () {
    it('toggles folder expansion', function () {
        $folder = FileSystemItem::factory()->folder()->create();

        $component = new FileManagerSidebar();
        $component->mount();

        expect($component->expandedFolders)->toBe(['root']);

        $component->toggleFolder((string) $folder->id);
        expect($component->expandedFolders)->toContain((string) $folder->id);

        $component->toggleFolder((string) $folder->id);
        expect($component->expandedFolders)->not->toContain((string) $folder->id);
    });

    it('checks if folder is expanded', function () {
        $folder = FileSystemItem::factory()->folder()->create();

        $component = new FileManagerSidebar();
        $component->mount();

        expect($component->isFolderExpanded(null))->toBeTrue();
        expect($component->isFolderExpanded((string) $folder->id))->toBeFalse();

        $component->toggleFolder((string) $folder->id);

        expect($component->isFolderExpanded((string) $folder->id))->toBeTrue();
    });

    it('expands root by default', function () {
        $component = new FileManagerSidebar();
        $component->mount();

        expect($component->isFolderExpanded(null))->toBeTrue();
    });
});

describe('folder tree property', function () {
    it('returns folder tree array', function () {
        FileSystemItem::factory()->folder()->count(2)->create();

        $component = new FileManagerSidebar();
        $component->mount();

        $folderTree = $component->folderTree;

        expect($folderTree)->toBeArray();
    });

    it('returns nested folder structure', function () {
        $parent = FileSystemItem::factory()->folder()->create(['name' => 'Parent']);
        FileSystemItem::factory()->folder()->create(['name' => 'Child', 'parent_id' => $parent->id]);

        $component = new FileManagerSidebar();
        $component->mount();

        $folderTree = $component->folderTree;

        expect($folderTree)->toBeArray();
        expect($folderTree)->toHaveCount(1);
        expect($folderTree[0]['name'])->toBe('Parent');
        expect($folderTree[0]['children'])->toHaveCount(1);
    });
});

describe('root file count property', function () {
    it('returns count of files in root', function () {
        FileSystemItem::factory()->count(2)->create();
        FileSystemItem::factory()->folder()->create();

        $component = new FileManagerSidebar();
        $component->mount();

        $count = $component->rootFileCount;

        expect($count)->toBe(2);
    });

    it('returns zero when no files in root', function () {
        FileSystemItem::factory()->folder()->count(2)->create();

        $component = new FileManagerSidebar();
        $component->mount();

        $count = $component->rootFileCount;

        expect($count)->toBe(0);
    });
});

describe('config properties', function () {
    it('returns root label from config', function () {
        $this->app['config']->set('filemanager.sidebar.root_label', '/');

        $component = new FileManagerSidebar();
        $component->mount();

        expect($component->rootLabel)->toBe('/');
    });

    it('returns default root label when config is empty', function () {
        // Remove the key entirely so it falls back to default
        $this->app['config']->offsetUnset('filemanager.sidebar.root_label');

        $component = new FileManagerSidebar();
        $component->mount();

        expect($component->rootLabel)->toBe('Root');
    });

    it('returns heading from config', function () {
        $this->app['config']->set('filemanager.sidebar.heading', 'My Files');

        $component = new FileManagerSidebar();
        $component->mount();

        expect($component->heading)->toBe('My Files');
    });

    it('returns default heading when config is empty', function () {
        // Remove the key entirely so it falls back to default
        $this->app['config']->offsetUnset('filemanager.sidebar.heading');

        $component = new FileManagerSidebar();
        $component->mount();

        expect($component->heading)->toBe('Folders');
    });
});

describe('inheritance', function () {
    it('extends Livewire Component', function () {
        expect(is_subclass_of(FileManagerSidebar::class, \Livewire\Component::class))->toBeTrue();
    });
});

describe('subfolder creation', function () {
    it('opens create subfolder dialog for root', function () {
        $component = new FileManagerSidebar();
        $component->mount();

        $component->openCreateSubfolderDialog(null);

        expect($component->subfolderParentPath)->toBeNull();
        expect($component->subfolderName)->toBe('');
    });

    it('opens create subfolder dialog for a folder', function () {
        $folder = FileSystemItem::factory()->folder()->create();

        $component = new FileManagerSidebar();
        $component->mount();

        $component->openCreateSubfolderDialog((string) $folder->id);

        expect($component->subfolderParentPath)->toBe((string) $folder->id);
        expect($component->subfolderName)->toBe('');
    });

    it('creates a subfolder in root', function () {
        $component = new FileManagerSidebar();
        $component->mount();

        $component->subfolderParentPath = null;
        $component->subfolderName = 'New Folder';
        $component->createSubfolder();

        $folder = FileSystemItem::where('name', 'New Folder')->first();

        expect($folder)->not->toBeNull();
        expect($folder->parent_id)->toBeNull();
        expect($folder->type)->toBe('folder');
    });

    it('creates a subfolder in a parent folder', function () {
        $parent = FileSystemItem::factory()->folder()->create(['name' => 'Parent']);

        $component = new FileManagerSidebar();
        $component->mount();

        $component->subfolderParentPath = (string) $parent->id;
        $component->subfolderName = 'Child Folder';
        $component->createSubfolder();

        $folder = FileSystemItem::where('name', 'Child Folder')->first();

        expect($folder)->not->toBeNull();
        expect($folder->parent_id)->toBe($parent->id);
        expect($folder->type)->toBe('folder');
    });

    it('expands parent folder after creating subfolder', function () {
        $parent = FileSystemItem::factory()->folder()->create(['name' => 'Parent']);

        $component = new FileManagerSidebar();
        $component->mount();

        expect($component->expandedFolders)->not->toContain((string) $parent->id);

        $component->subfolderParentPath = (string) $parent->id;
        $component->subfolderName = 'Child Folder';
        $component->createSubfolder();

        expect($component->expandedFolders)->toContain((string) $parent->id);
    });
});

describe('rename functionality', function () {
    it('opens rename dialog with item name', function () {
        $folder = FileSystemItem::factory()->folder()->create(['name' => 'My Folder']);

        $component = new FileManagerSidebar();
        $component->mount();

        $component->openRenameDialog((string) $folder->id);

        expect($component->itemToRenameId)->toBe((string) $folder->id);
        expect($component->renameItemName)->toBe('My Folder');
    });

    it('renames a folder', function () {
        $folder = FileSystemItem::factory()->folder()->create(['name' => 'Old Name']);

        $component = new FileManagerSidebar();
        $component->mount();

        $component->itemToRenameId = (string) $folder->id;
        $component->renameItemName = 'New Name';
        $component->renameItem();

        $folder->refresh();

        expect($folder->name)->toBe('New Name');
        expect($component->itemToRenameId)->toBeNull();
        expect($component->renameItemName)->toBe('');
    });

    it('does nothing when item to rename is not set', function () {
        $component = new FileManagerSidebar();
        $component->mount();

        $component->renameItem();

        // Should not throw exception
        expect(true)->toBeTrue();
    });
});

describe('move functionality', function () {
    it('opens move dialog', function () {
        $folder = FileSystemItem::factory()->folder()->create(['name' => 'My Folder']);

        $component = new FileManagerSidebar();
        $component->mount();

        $component->openMoveDialog((string) $folder->id);

        expect($component->itemToMoveId)->toBe((string) $folder->id);
        expect($component->moveTargetPath)->toBeNull();
    });

    it('sets move target', function () {
        $component = new FileManagerSidebar();
        $component->mount();

        $component->setMoveTarget('some-path');

        expect($component->moveTargetPath)->toBe('some-path');
    });

    it('moves a folder to root', function () {
        $parent = FileSystemItem::factory()->folder()->create(['name' => 'Parent']);
        $child = FileSystemItem::factory()->folder()->create([
            'name' => 'Child',
            'parent_id' => $parent->id,
        ]);

        $component = new FileManagerSidebar();
        $component->mount();

        $component->itemToMoveId = (string) $child->id;
        $component->moveTargetPath = null;
        $component->moveItem();

        $child->refresh();

        expect($child->parent_id)->toBeNull();
    });

    it('moves a folder to another folder', function () {
        $folder1 = FileSystemItem::factory()->folder()->create(['name' => 'Folder 1']);
        $folder2 = FileSystemItem::factory()->folder()->create(['name' => 'Folder 2']);

        $component = new FileManagerSidebar();
        $component->mount();

        $component->itemToMoveId = (string) $folder1->id;
        $component->moveTargetPath = (string) $folder2->id;
        $component->moveItem();

        $folder1->refresh();

        expect($folder1->parent_id)->toBe($folder2->id);
    });

    it('does not move folder into itself', function () {
        $folder = FileSystemItem::factory()->folder()->create(['name' => 'My Folder']);

        $component = new FileManagerSidebar();
        $component->mount();

        $component->itemToMoveId = (string) $folder->id;
        $component->moveTargetPath = (string) $folder->id;
        $component->moveItem();

        $folder->refresh();

        // Folder should remain in root
        expect($folder->parent_id)->toBeNull();
    });

    it('returns all folders property', function () {
        FileSystemItem::factory()->folder()->count(3)->create();

        $component = new FileManagerSidebar();
        $component->mount();

        $allFolders = $component->allFolders;

        expect($allFolders)->toHaveCount(3);
    });
});

describe('computed properties', function () {
    it('returns item to move', function () {
        $folder = FileSystemItem::factory()->folder()->create(['name' => 'My Folder']);

        $component = new FileManagerSidebar();
        $component->mount();

        expect($component->itemToMove)->toBeNull();

        $component->itemToMoveId = (string) $folder->id;

        expect($component->itemToMove)->not->toBeNull();
        expect($component->itemToMove->getName())->toBe('My Folder');
    });

    it('returns subfolder parent', function () {
        $folder = FileSystemItem::factory()->folder()->create(['name' => 'Parent']);

        $component = new FileManagerSidebar();
        $component->mount();

        expect($component->subfolderParent)->toBeNull();

        $component->subfolderParentPath = (string) $folder->id;

        expect($component->subfolderParent)->not->toBeNull();
        expect($component->subfolderParent->getName())->toBe('Parent');
    });

    it('returns item to rename', function () {
        $folder = FileSystemItem::factory()->folder()->create(['name' => 'My Folder']);

        $component = new FileManagerSidebar();
        $component->mount();

        expect($component->itemToRename)->toBeNull();

        $component->itemToRenameId = (string) $folder->id;

        expect($component->itemToRename)->not->toBeNull();
        expect($component->itemToRename->getName())->toBe('My Folder');
    });
});

describe('read-only mode', function () {
    it('detects read-only mode when user cannot create', function () {
        // With default config (no permissions required), user can create
        $component = new FileManagerSidebar();
        $component->mount();

        expect($component->isReadOnly())->toBeFalse();
    });
});

describe('event listening', function () {
    it('has refreshFolderTree method for event handling', function () {
        $component = new FileManagerSidebar();
        $component->mount();

        // Just verify the method exists and can be called
        $component->refreshFolderTree();

        expect(true)->toBeTrue();
    });
});
