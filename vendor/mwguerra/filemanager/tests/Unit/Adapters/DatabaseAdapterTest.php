<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use MWGuerra\FileManager\Adapters\DatabaseAdapter;
use MWGuerra\FileManager\Adapters\DatabaseItem;
use MWGuerra\FileManager\Models\FileSystemItem;

beforeEach(function () {
    Storage::fake('testing');
    $this->adapter = new DatabaseAdapter(FileSystemItem::class, 'testing', 'uploads');
});

describe('getItems', function () {
    it('returns items from root folder', function () {
        FileSystemItem::factory()->folder()->create(['name' => 'folder1']);
        FileSystemItem::factory()->folder()->create(['name' => 'folder2']);
        FileSystemItem::factory()->file()->create(['name' => 'file.pdf']);

        $items = $this->adapter->getItems();

        expect($items)->toHaveCount(3);
    });

    it('returns items from specific folder', function () {
        $folder = FileSystemItem::factory()->folder()->create(['name' => 'parent']);
        FileSystemItem::factory()->file()->create(['name' => 'child.pdf', 'parent_id' => $folder->id]);
        FileSystemItem::factory()->file()->create(['name' => 'root.pdf']);

        $items = $this->adapter->getItems((string) $folder->id);

        expect($items)->toHaveCount(1)
            ->and($items->first()->getName())->toBe('child.pdf');
    });

    it('returns empty collection for empty folder', function () {
        $folder = FileSystemItem::factory()->folder()->create(['name' => 'empty']);

        $items = $this->adapter->getItems((string) $folder->id);

        expect($items)->toHaveCount(0);
    });
});

describe('getFolders', function () {
    it('returns only folders from root', function () {
        FileSystemItem::factory()->folder()->create(['name' => 'folder1']);
        FileSystemItem::factory()->folder()->create(['name' => 'folder2']);
        FileSystemItem::factory()->file()->create(['name' => 'file.pdf']);

        $folders = $this->adapter->getFolders();

        expect($folders)->toHaveCount(2)
            ->and($folders->every(fn ($item) => $item->isFolder()))->toBeTrue();
    });

    it('returns folders from specific parent', function () {
        $parent = FileSystemItem::factory()->folder()->create(['name' => 'parent']);
        FileSystemItem::factory()->folder()->create(['name' => 'child1', 'parent_id' => $parent->id]);
        FileSystemItem::factory()->folder()->create(['name' => 'child2', 'parent_id' => $parent->id]);
        FileSystemItem::factory()->folder()->create(['name' => 'other']);

        $folders = $this->adapter->getFolders((string) $parent->id);

        expect($folders)->toHaveCount(2);
    });
});

describe('getItem', function () {
    it('returns item by id', function () {
        $file = FileSystemItem::factory()->file()->create(['name' => 'document.pdf']);

        $item = $this->adapter->getItem((string) $file->id);

        expect($item)->not->toBeNull()
            ->and($item)->toBeInstanceOf(DatabaseItem::class)
            ->and($item->getName())->toBe('document.pdf');
    });

    it('returns null for nonexistent id', function () {
        $item = $this->adapter->getItem('999999');

        expect($item)->toBeNull();
    });

    it('returns null for non-numeric identifier', function () {
        $item = $this->adapter->getItem('invalid');

        expect($item)->toBeNull();
    });
});

describe('createFolder', function () {
    it('creates folder in root', function () {
        $result = $this->adapter->createFolder('new-folder');

        expect($result)->toBeInstanceOf(DatabaseItem::class)
            ->and($result->getName())->toBe('new-folder')
            ->and($result->isFolder())->toBeTrue();

        $this->assertDatabaseHas('file_system_items', [
            'name' => 'new-folder',
            'type' => 'folder',
            'parent_id' => null,
        ]);
    });

    it('creates folder in parent folder', function () {
        $parent = FileSystemItem::factory()->folder()->create(['name' => 'parent']);

        $result = $this->adapter->createFolder('child', (string) $parent->id);

        expect($result)->toBeInstanceOf(DatabaseItem::class)
            ->and($result->getName())->toBe('child');

        $this->assertDatabaseHas('file_system_items', [
            'name' => 'child',
            'type' => 'folder',
            'parent_id' => $parent->id,
        ]);
    });

    it('returns error for duplicate folder name', function () {
        FileSystemItem::factory()->folder()->create(['name' => 'existing']);

        $result = $this->adapter->createFolder('existing');

        expect($result)->toBe('A folder with this name already exists');
    });
});

describe('uploadFile', function () {
    it('uploads file to root', function () {
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $result = $this->adapter->uploadFile($file);

        expect($result)->toBeInstanceOf(DatabaseItem::class)
            ->and($result->getName())->toBe('document.pdf')
            ->and($result->isFile())->toBeTrue();

        $this->assertDatabaseHas('file_system_items', [
            'name' => 'document.pdf',
            'type' => 'file',
            'parent_id' => null,
        ]);
    });

    it('uploads file to specific folder', function () {
        $folder = FileSystemItem::factory()->folder()->create(['name' => 'uploads']);
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $result = $this->adapter->uploadFile($file, (string) $folder->id);

        expect($result)->toBeInstanceOf(DatabaseItem::class);

        $this->assertDatabaseHas('file_system_items', [
            'name' => 'document.pdf',
            'parent_id' => $folder->id,
        ]);
    });

    it('renames file on duplicate', function () {
        FileSystemItem::factory()->file()->create(['name' => 'existing.pdf']);
        $file = UploadedFile::fake()->create('existing.pdf', 100, 'application/pdf');

        $result = $this->adapter->uploadFile($file);

        expect($result)->toBeInstanceOf(DatabaseItem::class)
            ->and($result->getName())->not->toBe('existing.pdf')
            ->and($result->getName())->toContain('existing_');
    });
});

describe('rename', function () {
    it('renames item successfully', function () {
        $file = FileSystemItem::factory()->file()->create(['name' => 'old-name.pdf']);

        $result = $this->adapter->rename((string) $file->id, 'new-name.pdf');

        expect($result)->toBeTrue();

        $this->assertDatabaseHas('file_system_items', [
            'id' => $file->id,
            'name' => 'new-name.pdf',
        ]);
    });

    it('returns error for nonexistent item', function () {
        $result = $this->adapter->rename('999999', 'new-name.pdf');

        expect($result)->toBe('Item not found');
    });

    it('returns error for duplicate name', function () {
        FileSystemItem::factory()->file()->create(['name' => 'existing.pdf']);
        $file = FileSystemItem::factory()->file()->create(['name' => 'other.pdf']);

        $result = $this->adapter->rename((string) $file->id, 'existing.pdf');

        expect($result)->toBe('An item with this name already exists in this folder');
    });
});

describe('move', function () {
    it('moves item to another folder', function () {
        $target = FileSystemItem::factory()->folder()->create(['name' => 'target']);
        $file = FileSystemItem::factory()->file()->create(['name' => 'file.pdf']);

        $result = $this->adapter->move((string) $file->id, (string) $target->id);

        expect($result)->toBeTrue();

        $this->assertDatabaseHas('file_system_items', [
            'id' => $file->id,
            'parent_id' => $target->id,
        ]);
    });

    it('moves item to root', function () {
        $folder = FileSystemItem::factory()->folder()->create(['name' => 'folder']);
        $file = FileSystemItem::factory()->file()->create(['name' => 'file.pdf', 'parent_id' => $folder->id]);

        $result = $this->adapter->move((string) $file->id, null);

        expect($result)->toBeTrue();

        $this->assertDatabaseHas('file_system_items', [
            'id' => $file->id,
            'parent_id' => null,
        ]);
    });

    it('returns error for nonexistent item', function () {
        $result = $this->adapter->move('999999', null);

        expect($result)->toBe('Item not found');
    });

    it('returns error when moving to same location', function () {
        $file = FileSystemItem::factory()->file()->create(['name' => 'file.pdf']);

        $result = $this->adapter->move((string) $file->id, null);

        expect($result)->toBe('Item is already in this folder');
    });

    it('returns error for duplicate name in target', function () {
        $target = FileSystemItem::factory()->folder()->create(['name' => 'target']);
        FileSystemItem::factory()->file()->create(['name' => 'file.pdf', 'parent_id' => $target->id]);
        $file = FileSystemItem::factory()->file()->create(['name' => 'file.pdf']);

        $result = $this->adapter->move((string) $file->id, (string) $target->id);

        expect($result)->toBe('An item with this name already exists in the destination folder');
    });

    it('prevents moving folder into itself', function () {
        $folder = FileSystemItem::factory()->folder()->create(['name' => 'parent']);
        $child = FileSystemItem::factory()->folder()->create(['name' => 'child', 'parent_id' => $folder->id]);

        $result = $this->adapter->move((string) $folder->id, (string) $child->id);

        expect($result)->toBe('Cannot move a folder into itself or its descendants');
    });
});

describe('delete', function () {
    it('deletes file successfully', function () {
        Storage::disk('testing')->put('uploads/file.pdf', 'content');
        $file = FileSystemItem::factory()->file()->create([
            'name' => 'file.pdf',
            'storage_path' => 'uploads/file.pdf',
        ]);

        $result = $this->adapter->delete((string) $file->id);

        expect($result)->toBeTrue();

        $this->assertDatabaseMissing('file_system_items', ['id' => $file->id]);
    });

    it('deletes folder successfully', function () {
        $folder = FileSystemItem::factory()->folder()->create(['name' => 'folder']);

        $result = $this->adapter->delete((string) $folder->id);

        expect($result)->toBeTrue();

        $this->assertDatabaseMissing('file_system_items', ['id' => $folder->id]);
    });

    it('returns error for nonexistent item', function () {
        $result = $this->adapter->delete('999999');

        expect($result)->toBe('Item not found');
    });
});

describe('deleteMany', function () {
    it('deletes multiple items', function () {
        $file1 = FileSystemItem::factory()->file()->create(['name' => 'file1.pdf']);
        $file2 = FileSystemItem::factory()->file()->create(['name' => 'file2.pdf']);
        $file3 = FileSystemItem::factory()->file()->create(['name' => 'file3.pdf']);

        $count = $this->adapter->deleteMany([(string) $file1->id, (string) $file2->id]);

        expect($count)->toBe(2);

        $this->assertDatabaseMissing('file_system_items', ['id' => $file1->id]);
        $this->assertDatabaseMissing('file_system_items', ['id' => $file2->id]);
        $this->assertDatabaseHas('file_system_items', ['id' => $file3->id]);
    });
});

describe('exists', function () {
    it('returns true for existing item', function () {
        $file = FileSystemItem::factory()->file()->create(['name' => 'file.pdf']);

        expect($this->adapter->exists((string) $file->id))->toBeTrue();
    });

    it('returns false for nonexistent item', function () {
        expect($this->adapter->exists('999999'))->toBeFalse();
    });
});

describe('getUrl', function () {
    it('returns url for file with storage path', function () {
        Storage::disk('testing')->put('uploads/file.pdf', 'content');
        $file = FileSystemItem::factory()->file()->create([
            'name' => 'file.pdf',
            'storage_path' => 'uploads/file.pdf',
        ]);

        $url = $this->adapter->getUrl((string) $file->id);

        expect($url)->toBeString()
            ->and($url)->toContain('file.pdf');
    });

    it('returns null for nonexistent item', function () {
        $url = $this->adapter->getUrl('999999');

        expect($url)->toBeNull();
    });

    it('returns null for item without storage path', function () {
        $folder = FileSystemItem::factory()->folder()->create(['name' => 'folder']);

        $url = $this->adapter->getUrl((string) $folder->id);

        expect($url)->toBeNull();
    });
});

describe('getContents', function () {
    it('returns file contents', function () {
        Storage::disk('testing')->put('uploads/file.txt', 'Hello World');
        $file = FileSystemItem::factory()->file()->create([
            'name' => 'file.txt',
            'storage_path' => 'uploads/file.txt',
        ]);

        $contents = $this->adapter->getContents((string) $file->id);

        expect($contents)->toBe('Hello World');
    });

    it('returns null for nonexistent item', function () {
        $contents = $this->adapter->getContents('999999');

        expect($contents)->toBeNull();
    });
});

describe('getStream', function () {
    it('returns stream for file', function () {
        Storage::disk('testing')->put('uploads/file.txt', 'content');
        $file = FileSystemItem::factory()->file()->create([
            'name' => 'file.txt',
            'storage_path' => 'uploads/file.txt',
        ]);

        $stream = $this->adapter->getStream((string) $file->id);

        expect($stream)->toBeResource();
        fclose($stream);
    });

    it('returns null for nonexistent item', function () {
        $stream = $this->adapter->getStream('999999');

        expect($stream)->toBeNull();
    });
});

describe('getSize', function () {
    it('returns stored size', function () {
        $file = FileSystemItem::factory()->file()->create([
            'name' => 'file.pdf',
            'size' => 12345,
        ]);

        $size = $this->adapter->getSize((string) $file->id);

        expect($size)->toBe(12345);
    });

    it('returns null for nonexistent item', function () {
        $size = $this->adapter->getSize('999999');

        expect($size)->toBeNull();
    });
});

describe('breadcrumbs', function () {
    it('returns root breadcrumb for root path', function () {
        $breadcrumbs = $this->adapter->getBreadcrumbs(null);

        expect($breadcrumbs)->toHaveCount(1)
            ->and($breadcrumbs[0]['name'])->toBe('Root');
    });

    it('returns breadcrumbs for nested folder', function () {
        $parent = FileSystemItem::factory()->folder()->create(['name' => 'parent']);
        $child = FileSystemItem::factory()->folder()->create(['name' => 'child', 'parent_id' => $parent->id]);

        $breadcrumbs = $this->adapter->getBreadcrumbs((string) $child->id);

        expect($breadcrumbs)->toHaveCount(3)
            ->and($breadcrumbs[0]['name'])->toBe('Root')
            ->and($breadcrumbs[1]['name'])->toBe('parent')
            ->and($breadcrumbs[2]['name'])->toBe('child');
    });
});

describe('folder tree', function () {
    it('returns folder tree structure', function () {
        $folder1 = FileSystemItem::factory()->folder()->create(['name' => 'folder1']);
        $folder2 = FileSystemItem::factory()->folder()->create(['name' => 'folder2']);
        FileSystemItem::factory()->folder()->create(['name' => 'child', 'parent_id' => $folder1->id]);

        $tree = $this->adapter->getFolderTree();

        expect($tree)->toHaveCount(2);
    });
});

describe('mode and model', function () {
    it('returns database as mode name', function () {
        expect($this->adapter->getModeName())->toBe('database');
    });

    it('returns model class', function () {
        expect($this->adapter->getModelClass())->toBe(FileSystemItem::class);
    });
});
