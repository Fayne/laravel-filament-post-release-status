<?php

use Illuminate\Support\Facades\Storage;
use MWGuerra\FileManager\Livewire\EmbeddedFileManager;
use MWGuerra\FileManager\Models\FileSystemItem;

beforeEach(function () {
    Storage::fake('testing');
    $this->app['config']->set('filemanager.mode', 'database');
    $this->app['config']->set('filemanager.model', FileSystemItem::class);
    $this->app['config']->set('filemanager.storage_mode.disk', 'testing');
    $this->app['config']->set('filemanager.upload.disk', 'testing');
    $this->app['config']->set('filemanager.upload.max_file_size', 10240);
});

describe('mount', function () {
    it('mounts with default configuration', function () {
        $component = new EmbeddedFileManager();
        $component->mount();

        expect($component->height)->toBe('500px');
        expect($component->showHeader)->toBeTrue();
        expect($component->showSidebar)->toBeTrue();
        expect($component->defaultViewMode)->toBe('grid');
        expect($component->viewMode)->toBe('grid');
        expect($component->disk)->toBeNull();
        expect($component->target)->toBeNull();
        expect($component->currentPath)->toBeNull();
    });

    it('mounts with custom configuration', function () {
        $component = new EmbeddedFileManager();
        $component->mount(
            height: '800px',
            showHeader: false,
            showSidebar: false,
            defaultViewMode: 'list',
            disk: 'testing',
            target: 'uploads',
            initialFolder: 'documents'
        );

        expect($component->height)->toBe('800px');
        expect($component->showHeader)->toBeFalse();
        expect($component->showSidebar)->toBeFalse();
        expect($component->defaultViewMode)->toBe('list');
        expect($component->viewMode)->toBe('list');
        expect($component->disk)->toBe('testing');
        expect($component->target)->toBe('uploads');
        expect($component->currentPath)->toBe('documents');
    });

    it('expands initial folder in tree', function () {
        $component = new EmbeddedFileManager();
        $component->mount(initialFolder: 'documents');

        expect($component->expandedFolders)->toContain('root');
        expect($component->expandedFolders)->toContain('documents');
    });
});

describe('mode', function () {
    it('returns database mode', function () {
        $component = new EmbeddedFileManager();
        $component->mount();

        expect($component->getMode())->toBe('database');
    });

    it('is not read-only', function () {
        $component = new EmbeddedFileManager();
        $component->mount();

        expect($component->isReadOnly())->toBeFalse();
    });
});

describe('navigation', function () {
    it('navigates to a folder', function () {
        $folder = FileSystemItem::factory()->folder()->create(['name' => 'documents']);

        $component = new EmbeddedFileManager();
        $component->mount();
        $component->navigateTo((string) $folder->id);

        expect($component->currentPath)->toBe((string) $folder->id);
        expect($component->selectedItems)->toBe([]);
    });

    it('clears selection when navigating', function () {
        $folder = FileSystemItem::factory()->folder()->create();
        $file = FileSystemItem::factory()->create();

        $component = new EmbeddedFileManager();
        $component->mount();
        $component->selectedItems = [(string) $file->id];
        $component->navigateTo((string) $folder->id);

        expect($component->selectedItems)->toBe([]);
    });
});

describe('folder expansion', function () {
    it('toggles folder expansion', function () {
        $folder = FileSystemItem::factory()->folder()->create();

        $component = new EmbeddedFileManager();
        $component->mount();

        expect($component->expandedFolders)->toBe(['root']);

        $component->toggleFolder((string) $folder->id);
        expect($component->expandedFolders)->toContain((string) $folder->id);

        $component->toggleFolder((string) $folder->id);
        expect($component->expandedFolders)->not->toContain((string) $folder->id);
    });

    it('checks if folder is expanded', function () {
        $folder = FileSystemItem::factory()->folder()->create();

        $component = new EmbeddedFileManager();
        $component->mount();

        expect($component->isFolderExpanded(null))->toBeTrue();
        expect($component->isFolderExpanded((string) $folder->id))->toBeFalse();

        $component->toggleFolder((string) $folder->id);

        expect($component->isFolderExpanded((string) $folder->id))->toBeTrue();
    });
});

describe('view mode', function () {
    it('sets view mode', function () {
        $component = new EmbeddedFileManager();
        $component->mount();

        expect($component->viewMode)->toBe('grid');

        $component->setViewMode('list');

        expect($component->viewMode)->toBe('list');
    });
});

describe('selection', function () {
    it('toggles single item selection', function () {
        $file = FileSystemItem::factory()->create();

        $component = new EmbeddedFileManager();
        $component->mount();

        $component->toggleSelection((string) $file->id);
        expect($component->selectedItems)->toBe([(string) $file->id]);

        $component->toggleSelection((string) $file->id);
        expect($component->selectedItems)->toBe([]);
    });

    it('toggles multi-selection', function () {
        $file1 = FileSystemItem::factory()->create();
        $file2 = FileSystemItem::factory()->create();

        $component = new EmbeddedFileManager();
        $component->mount();

        $component->toggleSelection((string) $file1->id, true);
        $component->toggleSelection((string) $file2->id, true);

        expect($component->selectedItems)->toBe([(string) $file1->id, (string) $file2->id]);
    });

    it('clears selection', function () {
        $file = FileSystemItem::factory()->create();

        $component = new EmbeddedFileManager();
        $component->mount();
        $component->selectedItems = [(string) $file->id];

        $component->clearSelection();

        expect($component->selectedItems)->toBe([]);
    });

    it('checks if item is selected', function () {
        $file = FileSystemItem::factory()->create();

        $component = new EmbeddedFileManager();
        $component->mount();
        $component->toggleSelection((string) $file->id);

        expect($component->isSelected((string) $file->id))->toBeTrue();
    });
});

describe('changes tracking', function () {
    it('gets changes count', function () {
        $component = new EmbeddedFileManager();
        $component->mount();

        // Directly access changes array since we can't render
        expect($component->changesCount)->toBe(0);
    });

    it('clears changes', function () {
        $component = new EmbeddedFileManager();
        $component->mount();
        $component->changes = [['type' => 'test']];

        $component->clearChanges();

        expect($component->getChanges())->toHaveCount(0);
    });
});

describe('upload', function () {
    it('clears uploaded files', function () {
        $component = new EmbeddedFileManager();
        $component->mount();
        $component->uploadedFiles = ['file1', 'file2'];

        $component->clearUploadedFiles();

        expect($component->uploadedFiles)->toBe([]);
    });
});

describe('move', function () {
    it('sets move target', function () {
        $folder = FileSystemItem::factory()->folder()->create();

        $component = new EmbeddedFileManager();
        $component->mount();

        $component->setMoveTarget((string) $folder->id);

        expect($component->moveTargetPath)->toBe((string) $folder->id);
    });

    it('opens move dialog for single item', function () {
        $file = FileSystemItem::factory()->create();

        $component = new EmbeddedFileManager();
        $component->mount();

        $component->openMoveDialog((string) $file->id);

        expect($component->itemToMoveId)->toBe((string) $file->id);
        expect($component->itemsToMove)->toBe([]);
        expect($component->moveTargetPath)->toBeNull();
    });

    it('opens move dialog for selected items', function () {
        $file1 = FileSystemItem::factory()->create();
        $file2 = FileSystemItem::factory()->create();

        $component = new EmbeddedFileManager();
        $component->mount();
        $component->selectedItems = [(string) $file1->id, (string) $file2->id];

        $component->openMoveDialogForSelected();

        expect($component->itemsToMove)->toBe([(string) $file1->id, (string) $file2->id]);
        expect($component->itemToMoveId)->toBeNull();
    });

    it('does not open move dialog when no items selected', function () {
        $component = new EmbeddedFileManager();
        $component->mount();
        $component->selectedItems = [];

        $component->openMoveDialogForSelected();

        expect($component->itemsToMove)->toBe([]);
    });
});

describe('items property', function () {
    it('returns items collection', function () {
        FileSystemItem::factory()->count(3)->create();

        $component = new EmbeddedFileManager();
        $component->mount();

        $items = $component->items;

        expect($items)->toBeInstanceOf(\Illuminate\Support\Collection::class);
        expect($items->count())->toBe(3);
    });

    it('returns items for folder', function () {
        $folder = FileSystemItem::factory()->folder()->create();
        FileSystemItem::factory()->count(2)->create(['parent_id' => $folder->id]);

        $component = new EmbeddedFileManager();
        $component->mount();
        $component->navigateTo((string) $folder->id);

        $items = $component->items;

        expect($items->count())->toBe(2);
    });
});

describe('folder tree property', function () {
    it('returns folder tree array', function () {
        FileSystemItem::factory()->folder()->count(2)->create();

        $component = new EmbeddedFileManager();
        $component->mount();

        $folderTree = $component->folderTree;

        expect($folderTree)->toBeArray();
    });
});

describe('breadcrumbs property', function () {
    it('returns breadcrumbs for root', function () {
        $component = new EmbeddedFileManager();
        $component->mount();

        $breadcrumbs = $component->breadcrumbs;

        expect($breadcrumbs)->toBeArray();
    });

    it('returns breadcrumbs for nested folder', function () {
        $parent = FileSystemItem::factory()->folder()->create(['name' => 'Parent']);
        $child = FileSystemItem::factory()->folder()->create(['name' => 'Child', 'parent_id' => $parent->id]);

        $component = new EmbeddedFileManager();
        $component->mount();
        $component->navigateTo((string) $child->id);

        $breadcrumbs = $component->breadcrumbs;

        expect($breadcrumbs)->toBeArray();
        expect(count($breadcrumbs))->toBeGreaterThan(0);
    });
});

describe('select all', function () {
    it('selects all items in current view', function () {
        FileSystemItem::factory()->count(3)->create();

        $component = new EmbeddedFileManager();
        $component->mount();

        $component->selectAll();

        expect(count($component->selectedItems))->toBe(3);
    });
});

describe('all selected', function () {
    it('returns true when all items are selected', function () {
        FileSystemItem::factory()->count(2)->create();

        $component = new EmbeddedFileManager();
        $component->mount();
        $component->selectAll();

        expect($component->allSelected())->toBeTrue();
    });

    it('returns false when not all items are selected', function () {
        $file1 = FileSystemItem::factory()->create();
        FileSystemItem::factory()->create();

        $component = new EmbeddedFileManager();
        $component->mount();
        $component->toggleSelection((string) $file1->id);

        expect($component->allSelected())->toBeFalse();
    });

    it('returns false when no items exist', function () {
        $component = new EmbeddedFileManager();
        $component->mount();

        expect($component->allSelected())->toBeFalse();
    });
});

describe('handle item click', function () {
    it('navigates to folder when clicking folder', function () {
        $folder = FileSystemItem::factory()->folder()->create();

        $component = new EmbeddedFileManager();
        $component->mount();

        $component->handleItemClick((string) $folder->id);

        expect($component->currentPath)->toBe((string) $folder->id);
        expect($component->expandedFolders)->toContain((string) $folder->id);
    });

    it('opens preview when clicking file', function () {
        $file = FileSystemItem::factory()->create();

        $component = new EmbeddedFileManager();
        $component->mount();

        $component->handleItemClick((string) $file->id);

        expect($component->previewItemId)->toBe((string) $file->id);
    });
});

describe('preview', function () {
    it('opens preview for file', function () {
        $file = FileSystemItem::factory()->create();

        $component = new EmbeddedFileManager();
        $component->mount();

        $component->openPreview((string) $file->id);

        expect($component->previewItemId)->toBe((string) $file->id);
    });

    it('does not open preview for folder', function () {
        $folder = FileSystemItem::factory()->folder()->create();

        $component = new EmbeddedFileManager();
        $component->mount();

        $component->openPreview((string) $folder->id);

        expect($component->previewItemId)->toBeNull();
    });

    it('closes preview', function () {
        $file = FileSystemItem::factory()->create();

        $component = new EmbeddedFileManager();
        $component->mount();
        $component->previewItemId = (string) $file->id;

        $component->closePreview();

        expect($component->previewItemId)->toBeNull();
    });

    it('returns preview item property', function () {
        $file = FileSystemItem::factory()->create(['name' => 'document.pdf']);

        $component = new EmbeddedFileManager();
        $component->mount();
        $component->previewItemId = (string) $file->id;

        $previewItem = $component->previewItem;

        expect($previewItem)->not->toBeNull();
        expect($previewItem->getName())->toBe('document.pdf');
    });

    it('returns null preview item when no item selected', function () {
        $component = new EmbeddedFileManager();
        $component->mount();

        expect($component->previewItem)->toBeNull();
    });
});

describe('preview url', function () {
    it('returns null when no preview item', function () {
        $component = new EmbeddedFileManager();
        $component->mount();

        expect($component->getPreviewUrl())->toBeNull();
    });
});

describe('text content', function () {
    it('returns null when no preview item', function () {
        $component = new EmbeddedFileManager();
        $component->mount();

        expect($component->getTextContent())->toBeNull();
    });
});

describe('all folders property', function () {
    it('returns collection of folders', function () {
        FileSystemItem::factory()->folder()->count(3)->create();

        $component = new EmbeddedFileManager();
        $component->mount();

        $allFolders = $component->allFolders;

        expect($allFolders)->toBeInstanceOf(\Illuminate\Support\Collection::class);
        expect($allFolders->count())->toBe(3);
    });
});

describe('root file count property', function () {
    it('returns count of files in root', function () {
        FileSystemItem::factory()->count(2)->create();
        FileSystemItem::factory()->folder()->create();

        $component = new EmbeddedFileManager();
        $component->mount();

        $count = $component->rootFileCount;

        expect($count)->toBe(2);
    });
});

describe('rename dialog', function () {
    it('opens rename dialog', function () {
        $file = FileSystemItem::factory()->create(['name' => 'document.pdf']);

        $component = new EmbeddedFileManager();
        $component->mount();

        $component->openRenameDialog((string) $file->id);

        expect($component->itemToRenameId)->toBe((string) $file->id);
        expect($component->renameItemName)->toBe('document.pdf');
    });
});

describe('subfolder dialog', function () {
    it('opens subfolder dialog', function () {
        $folder = FileSystemItem::factory()->folder()->create();

        $component = new EmbeddedFileManager();
        $component->mount();

        $component->openCreateSubfolderDialog((string) $folder->id);

        expect($component->subfolderParentPath)->toBe((string) $folder->id);
        expect($component->subfolderName)->toBe('');
    });
});

describe('computed item properties', function () {
    it('returns item to move property', function () {
        $file = FileSystemItem::factory()->create(['name' => 'document.pdf']);

        $component = new EmbeddedFileManager();
        $component->mount();
        $component->itemToMoveId = (string) $file->id;

        $item = $component->itemToMove;

        expect($item)->not->toBeNull();
        expect($item->getName())->toBe('document.pdf');
    });

    it('returns null item to move when not set', function () {
        $component = new EmbeddedFileManager();
        $component->mount();

        expect($component->itemToMove)->toBeNull();
    });

    it('returns subfolder parent property', function () {
        $folder = FileSystemItem::factory()->folder()->create(['name' => 'Parent Folder']);

        $component = new EmbeddedFileManager();
        $component->mount();
        $component->subfolderParentPath = (string) $folder->id;

        $parent = $component->subfolderParent;

        expect($parent)->not->toBeNull();
        expect($parent->getName())->toBe('Parent Folder');
    });

    it('returns item to rename property', function () {
        $file = FileSystemItem::factory()->create(['name' => 'document.pdf']);

        $component = new EmbeddedFileManager();
        $component->mount();
        $component->itemToRenameId = (string) $file->id;

        $item = $component->itemToRename;

        expect($item)->not->toBeNull();
        expect($item->getName())->toBe('document.pdf');
    });
});

describe('file type', function () {
    it('returns file type for item', function () {
        $file = FileSystemItem::factory()->create(['name' => 'document.pdf']);

        $component = new EmbeddedFileManager();
        $component->mount();

        $items = $component->items;
        $item = $items->first();

        $fileType = $component->getFileTypeForItem($item);

        expect($fileType)->toBeInstanceOf(\MWGuerra\FileManager\Contracts\FileTypeContract::class);
    });
});

describe('preview file type property', function () {
    it('returns file type for preview item', function () {
        $file = FileSystemItem::factory()->create(['name' => 'readme.txt']);

        $component = new EmbeddedFileManager();
        $component->mount();
        $component->previewItemId = (string) $file->id;

        $fileType = $component->previewFileType;

        expect($fileType)->toBeInstanceOf(\MWGuerra\FileManager\Contracts\FileTypeContract::class);
        expect($fileType->identifier())->toBe('text');
    });

    it('returns null when no preview item', function () {
        $component = new EmbeddedFileManager();
        $component->mount();

        expect($component->previewFileType)->toBeNull();
    });
});

describe('changes', function () {
    it('returns changes array', function () {
        $component = new EmbeddedFileManager();
        $component->mount();

        expect($component->getChanges())->toBeArray();
        expect($component->getChanges())->toHaveCount(0);
    });

    it('tracks changes count property', function () {
        $component = new EmbeddedFileManager();
        $component->mount();
        $component->changes = [
            ['type' => 'created', 'item_type' => 'folder'],
            ['type' => 'renamed', 'item_type' => 'file'],
        ];

        expect($component->changesCount)->toBe(2);
    });
});

describe('refresh', function () {
    it('can refresh component', function () {
        $component = new EmbeddedFileManager();
        $component->mount();

        // Should not throw
        $component->refresh();

        expect(true)->toBeTrue();
    });
});

describe('inheritance', function () {
    it('extends Livewire Component', function () {
        expect(is_subclass_of(EmbeddedFileManager::class, \Livewire\Component::class))->toBeTrue();
    });

    it('uses WithFileUploads trait', function () {
        $traits = class_uses_recursive(EmbeddedFileManager::class);

        expect($traits)->toContain(\Livewire\WithFileUploads::class);
    });
});
