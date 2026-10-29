<?php

use MWGuerra\FileManager\Adapters\DatabaseItem;
use MWGuerra\FileManager\Models\FileSystemItem;

describe('constructor and model access', function () {
    it('wraps a FileSystemItem model', function () {
        $model = FileSystemItem::factory()->file()->create(['name' => 'test.pdf']);

        $item = new DatabaseItem($model);

        expect($item->getModel())->toBe($model)
            ->and($item->getModel())->toBeInstanceOf(FileSystemItem::class);
    });
});

describe('identifier methods', function () {
    it('returns model id as identifier', function () {
        $model = FileSystemItem::factory()->file()->create(['name' => 'test.pdf']);

        $item = new DatabaseItem($model);

        expect($item->getIdentifier())->toBe((string) $model->id);
    });
});

describe('name and path methods', function () {
    it('returns model name', function () {
        $model = FileSystemItem::factory()->file()->create(['name' => 'document.pdf']);

        $item = new DatabaseItem($model);

        expect($item->getName())->toBe('document.pdf');
    });

    it('returns full path for root item', function () {
        $model = FileSystemItem::factory()->file()->create(['name' => 'file.txt']);

        $item = new DatabaseItem($model);

        expect($item->getPath())->toBe('/file.txt');
    });

    it('returns full path for nested item', function () {
        $parent = FileSystemItem::factory()->folder()->create(['name' => 'parent']);
        $model = FileSystemItem::factory()->file()->create([
            'name' => 'file.txt',
            'parent_id' => $parent->id,
        ]);

        $item = new DatabaseItem($model);

        expect($item->getPath())->toBe('/parent/file.txt');
    });

    it('returns null parent path for root item', function () {
        $model = FileSystemItem::factory()->file()->create(['name' => 'file.txt']);

        $item = new DatabaseItem($model);

        expect($item->getParentPath())->toBeNull();
    });

    it('returns parent path for nested item', function () {
        $parent = FileSystemItem::factory()->folder()->create(['name' => 'parent']);
        $model = FileSystemItem::factory()->file()->create([
            'name' => 'file.txt',
            'parent_id' => $parent->id,
        ]);

        $item = new DatabaseItem($model);

        expect($item->getParentPath())->toBe('/parent');
    });
});

describe('folder and file detection', function () {
    it('detects folder correctly', function () {
        $model = FileSystemItem::factory()->folder()->create(['name' => 'my-folder']);

        $item = new DatabaseItem($model);

        expect($item->isFolder())->toBeTrue()
            ->and($item->isFile())->toBeFalse();
    });

    it('detects file correctly', function () {
        $model = FileSystemItem::factory()->file()->create(['name' => 'document.pdf']);

        $item = new DatabaseItem($model);

        expect($item->isFile())->toBeTrue()
            ->and($item->isFolder())->toBeFalse();
    });
});

describe('size methods', function () {
    it('returns size from model', function () {
        $model = FileSystemItem::factory()->file()->create([
            'name' => 'file.pdf',
            'size' => 12345,
        ]);

        $item = new DatabaseItem($model);

        expect($item->getSize())->toBe(12345);
    });

    it('returns null size for folder', function () {
        $model = FileSystemItem::factory()->folder()->create(['name' => 'folder']);

        $item = new DatabaseItem($model);

        expect($item->getSize())->toBeNull();
    });

    it('returns formatted size', function () {
        $model = FileSystemItem::factory()->file()->create([
            'name' => 'file.pdf',
            'size' => 2048,
        ]);

        $item = new DatabaseItem($model);

        expect($item->getFormattedSize())->toBe('2 KB');
    });
});

describe('mime type and extension', function () {
    it('returns null for mime type', function () {
        $model = FileSystemItem::factory()->file()->create(['name' => 'file.pdf']);

        $item = new DatabaseItem($model);

        // Database mode doesn't store mime type
        expect($item->getMimeType())->toBeNull();
    });

    it('returns extension for file', function () {
        $model = FileSystemItem::factory()->file()->create(['name' => 'document.pdf']);

        $item = new DatabaseItem($model);

        expect($item->getExtension())->toBe('pdf');
    });

    it('returns null extension for folder', function () {
        $model = FileSystemItem::factory()->folder()->create(['name' => 'folder']);

        $item = new DatabaseItem($model);

        expect($item->getExtension())->toBeNull();
    });

    it('returns null for file without extension', function () {
        $model = FileSystemItem::factory()->file()->create(['name' => 'README']);

        $item = new DatabaseItem($model);

        expect($item->getExtension())->toBeNull();
    });
});

describe('timestamp methods', function () {
    it('returns last modified timestamp', function () {
        $model = FileSystemItem::factory()->file()->create(['name' => 'file.pdf']);

        $item = new DatabaseItem($model);

        expect($item->getLastModified())->not->toBeNull()
            ->and($item->getLastModified())->toBeInt();
    });
});

describe('media properties', function () {
    it('returns thumbnail from model', function () {
        $model = FileSystemItem::factory()->video()->create([
            'name' => 'video.mp4',
            'thumbnail' => 'thumbnails/video.jpg',
        ]);

        $item = new DatabaseItem($model);

        expect($item->getThumbnail())->toBe('thumbnails/video.jpg');
    });

    it('returns null thumbnail when not set', function () {
        $model = FileSystemItem::factory()->file()->create(['name' => 'file.pdf']);

        $item = new DatabaseItem($model);

        expect($item->getThumbnail())->toBeNull();
    });

    it('returns duration from model', function () {
        $model = FileSystemItem::factory()->video()->create([
            'name' => 'video.mp4',
            'duration' => 120,
        ]);

        $item = new DatabaseItem($model);

        expect($item->getDuration())->toBe(120);
    });

    it('returns null duration when not set', function () {
        $model = FileSystemItem::factory()->file()->create(['name' => 'file.pdf']);

        $item = new DatabaseItem($model);

        expect($item->getDuration())->toBeNull();
    });

    it('returns formatted duration', function () {
        $model = FileSystemItem::factory()->video()->create([
            'name' => 'video.mp4',
            'duration' => 125, // 2:05
        ]);

        $item = new DatabaseItem($model);

        expect($item->getFormattedDuration())->toBe('2:05');
    });
});

describe('file type detection', function () {
    it('detects video files', function () {
        $model = FileSystemItem::factory()->video()->create(['name' => 'video.mp4']);

        $item = new DatabaseItem($model);

        expect($item->isVideo())->toBeTrue()
            ->and($item->isImage())->toBeFalse()
            ->and($item->isAudio())->toBeFalse()
            ->and($item->isDocument())->toBeFalse();
    });

    it('detects image files', function () {
        $model = FileSystemItem::factory()->image()->create(['name' => 'image.jpg']);

        $item = new DatabaseItem($model);

        expect($item->isImage())->toBeTrue()
            ->and($item->isVideo())->toBeFalse();
    });

    it('detects audio files', function () {
        $model = FileSystemItem::factory()->audio()->create(['name' => 'audio.mp3']);

        $item = new DatabaseItem($model);

        expect($item->isAudio())->toBeTrue()
            ->and($item->isVideo())->toBeFalse();
    });

    it('detects document files', function () {
        $model = FileSystemItem::factory()->document()->create(['name' => 'doc.pdf']);

        $item = new DatabaseItem($model);

        expect($item->isDocument())->toBeTrue()
            ->and($item->isVideo())->toBeFalse();
    });
});

describe('depth calculation', function () {
    it('returns depth for root item', function () {
        $model = FileSystemItem::factory()->file()->create(['name' => 'file.txt']);

        $item = new DatabaseItem($model);

        // Depth is count of ancestors, root item has 0 ancestors
        expect($item->getDepth())->toBe(0);
    });

    it('returns depth for nested item', function () {
        $parent = FileSystemItem::factory()->folder()->create(['name' => 'parent']);
        $child = FileSystemItem::factory()->folder()->create([
            'name' => 'child',
            'parent_id' => $parent->id,
        ]);
        $model = FileSystemItem::factory()->file()->create([
            'name' => 'file.txt',
            'parent_id' => $child->id,
        ]);

        $item = new DatabaseItem($model);

        // Depth is count of ancestors (parent and child), so 2
        expect($item->getDepth())->toBe(2);
    });
});

describe('toArray method', function () {
    it('returns complete array representation', function () {
        $parent = FileSystemItem::factory()->folder()->create(['name' => 'parent']);
        $model = FileSystemItem::factory()->video()->create([
            'name' => 'video.mp4',
            'parent_id' => $parent->id,
            'size' => 1024000,
            'duration' => 90,
            'thumbnail' => 'thumb.jpg',
            'storage_path' => 'uploads/video.mp4',
        ]);

        $item = new DatabaseItem($model);
        $array = $item->toArray();

        expect($array)->toBeArray()
            ->and($array['id'])->toBe($model->id)
            ->and($array['identifier'])->toBe((string) $model->id)
            ->and($array['name'])->toBe('video.mp4')
            ->and($array['path'])->toBe('/parent/video.mp4')
            ->and($array['parent_path'])->toBe('/parent')
            ->and($array['parent_id'])->toBe($parent->id)
            ->and($array['is_folder'])->toBeFalse()
            ->and($array['is_file'])->toBeTrue()
            ->and($array['size'])->toBe(1024000)
            ->and($array['extension'])->toBe('mp4')
            ->and($array['last_modified'])->not->toBeNull()
            ->and($array['thumbnail'])->toBe('thumb.jpg')
            ->and($array['duration'])->toBe(90)
            ->and($array['is_video'])->toBeTrue()
            ->and($array['is_image'])->toBeFalse()
            ->and($array['is_audio'])->toBeFalse()
            ->and($array['depth'])->toBe(1) // 1 ancestor (parent folder)
            ->and($array['storage_path'])->toBe('uploads/video.mp4');
    });
});

describe('magic getter', function () {
    it('returns model properties via magic getter', function () {
        $model = FileSystemItem::factory()->file()->create([
            'name' => 'file.pdf',
            'size' => 1234,
        ]);

        $item = new DatabaseItem($model);

        expect($item->name)->toBe('file.pdf')
            ->and($item->size)->toBe(1234)
            ->and($item->id)->toBe($model->id);
    });

    it('returns identifier via magic getter', function () {
        $model = FileSystemItem::factory()->file()->create(['name' => 'file.pdf']);

        $item = new DatabaseItem($model);

        expect($item->identifier)->toBe((string) $model->id);
    });

    it('returns path via magic getter', function () {
        $model = FileSystemItem::factory()->file()->create(['name' => 'file.pdf']);

        $item = new DatabaseItem($model);

        expect($item->path)->toBe('/file.pdf');
    });

    it('returns null for unknown property', function () {
        $model = FileSystemItem::factory()->file()->create(['name' => 'file.pdf']);

        $item = new DatabaseItem($model);

        expect($item->unknown_property)->toBeNull();
    });
});

describe('magic call forwarding', function () {
    it('forwards method calls to underlying model', function () {
        $model = FileSystemItem::factory()->file()->create(['name' => 'file.pdf']);

        $item = new DatabaseItem($model);

        // getFullPath is a method on the model
        expect($item->getFullPath())->toBe('/file.pdf');
    });
});
