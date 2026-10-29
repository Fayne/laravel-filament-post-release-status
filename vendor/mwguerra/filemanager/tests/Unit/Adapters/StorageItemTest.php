<?php

use Illuminate\Support\Facades\Storage;
use MWGuerra\FileManager\Adapters\StorageItem;

beforeEach(function () {
    Storage::fake('testing');
});

describe('constructor and basic properties', function () {
    it('creates item with all properties', function () {
        $item = new StorageItem(
            path: 'folder/file.txt',
            name: 'file.txt',
            isDirectory: false,
            size: 1024,
            mimeType: 'text/plain',
            lastModified: 1234567890,
            disk: 'testing'
        );

        expect($item->getName())->toBe('file.txt')
            ->and($item->getPath())->toBe('/folder/file.txt')
            ->and($item->isFolder())->toBeFalse()
            ->and($item->isFile())->toBeTrue()
            ->and($item->getSize())->toBe(1024)
            ->and($item->getMimeType())->toBe('text/plain')
            ->and($item->getLastModified())->toBe(1234567890)
            ->and($item->getDisk())->toBe('testing');
    });

    it('creates folder item', function () {
        $item = new StorageItem(
            path: 'my-folder',
            name: 'my-folder',
            isDirectory: true,
            disk: 'testing'
        );

        expect($item->isFolder())->toBeTrue()
            ->and($item->isFile())->toBeFalse()
            ->and($item->getSize())->toBeNull()
            ->and($item->getMimeType())->toBeNull();
    });
});

describe('fromPath factory method', function () {
    it('creates item from file path', function () {
        Storage::disk('testing')->put('test-file.txt', 'Hello World');

        $item = StorageItem::fromPath('test-file.txt', 'testing', false);

        expect($item->getName())->toBe('test-file.txt')
            ->and($item->isFile())->toBeTrue()
            ->and($item->getSize())->toBe(11);
    });

    it('creates item from directory path', function () {
        Storage::disk('testing')->makeDirectory('my-folder');

        $item = StorageItem::fromPath('my-folder', 'testing', true);

        expect($item->getName())->toBe('my-folder')
            ->and($item->isFolder())->toBeTrue()
            ->and($item->getSize())->toBeNull();
    });

    it('handles root path', function () {
        $item = StorageItem::fromPath('', 'testing', true);

        expect($item->getName())->toBe('/');
    });
});

describe('path methods', function () {
    it('returns identifier as path', function () {
        $item = new StorageItem('folder/file.txt', 'file.txt', false);

        expect($item->getIdentifier())->toBe('folder/file.txt');
    });

    it('returns path with leading slash', function () {
        $item = new StorageItem('folder/file.txt', 'file.txt', false);

        expect($item->getPath())->toBe('/folder/file.txt');
    });

    it('returns parent path for nested file', function () {
        $item = new StorageItem('folder/subfolder/file.txt', 'file.txt', false);

        expect($item->getParentPath())->toBe('folder/subfolder');
    });

    it('returns null parent path for root item', function () {
        $item = new StorageItem('', 'root', true);

        expect($item->getParentPath())->toBeNull();
    });

    it('returns null parent path for top-level item', function () {
        $item = new StorageItem('file.txt', 'file.txt', false);

        expect($item->getParentPath())->toBeNull();
    });
});

describe('extension detection', function () {
    it('returns extension for file', function () {
        $item = new StorageItem('file.pdf', 'file.pdf', false);

        expect($item->getExtension())->toBe('pdf');
    });

    it('returns null extension for folder', function () {
        $item = new StorageItem('folder', 'folder', true);

        expect($item->getExtension())->toBeNull();
    });

    it('returns null for file without extension', function () {
        $item = new StorageItem('README', 'README', false);

        expect($item->getExtension())->toBeNull();
    });
});

describe('formatted size', function () {
    it('returns empty string for null size', function () {
        $item = new StorageItem('folder', 'folder', true);

        expect($item->getFormattedSize())->toBe('');
    });

    it('formats bytes', function () {
        $item = new StorageItem('file.txt', 'file.txt', false, size: 500);

        expect($item->getFormattedSize())->toBe('500 B');
    });

    it('formats kilobytes', function () {
        $item = new StorageItem('file.txt', 'file.txt', false, size: 2048);

        expect($item->getFormattedSize())->toBe('2 KB');
    });

    it('formats megabytes', function () {
        $item = new StorageItem('file.txt', 'file.txt', false, size: 5242880);

        expect($item->getFormattedSize())->toBe('5 MB');
    });

    it('formats gigabytes', function () {
        $item = new StorageItem('file.txt', 'file.txt', false, size: 2147483648);

        expect($item->getFormattedSize())->toBe('2 GB');
    });
});

describe('file type detection', function () {
    it('detects video by mime type', function () {
        $item = new StorageItem('video.mp4', 'video.mp4', false, mimeType: 'video/mp4');

        expect($item->isVideo())->toBeTrue()
            ->and($item->isImage())->toBeFalse()
            ->and($item->isAudio())->toBeFalse();
    });

    it('detects video by extension', function () {
        $item = new StorageItem('video.mp4', 'video.mp4', false);

        expect($item->isVideo())->toBeTrue();
    });

    it('detects image by mime type', function () {
        $item = new StorageItem('image.jpg', 'image.jpg', false, mimeType: 'image/jpeg');

        expect($item->isImage())->toBeTrue()
            ->and($item->isVideo())->toBeFalse();
    });

    it('detects image by extension', function () {
        $item = new StorageItem('image.png', 'image.png', false);

        expect($item->isImage())->toBeTrue();
    });

    it('detects audio by mime type', function () {
        $item = new StorageItem('audio.mp3', 'audio.mp3', false, mimeType: 'audio/mpeg');

        expect($item->isAudio())->toBeTrue();
    });

    it('detects audio by extension', function () {
        $item = new StorageItem('audio.wav', 'audio.wav', false);

        expect($item->isAudio())->toBeTrue();
    });

    it('detects document by extension', function () {
        $item = new StorageItem('doc.pdf', 'doc.pdf', false);

        expect($item->isDocument())->toBeTrue();
    });

    it('detects various document types', function () {
        $extensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'md', 'json'];

        foreach ($extensions as $ext) {
            $item = new StorageItem("file.{$ext}", "file.{$ext}", false);
            expect($item->isDocument())->toBeTrue("Expected {$ext} to be detected as document");
        }
    });

    it('detects various video types', function () {
        $extensions = ['mp4', 'webm', 'mov', 'avi', 'mkv'];

        foreach ($extensions as $ext) {
            $item = new StorageItem("file.{$ext}", "file.{$ext}", false);
            expect($item->isVideo())->toBeTrue("Expected {$ext} to be detected as video");
        }
    });

    it('detects various image types', function () {
        $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

        foreach ($extensions as $ext) {
            $item = new StorageItem("file.{$ext}", "file.{$ext}", false);
            expect($item->isImage())->toBeTrue("Expected {$ext} to be detected as image");
        }
    });
});

describe('depth calculation', function () {
    it('returns 0 for root', function () {
        $item = new StorageItem('', 'root', true);

        expect($item->getDepth())->toBe(0);
    });

    it('returns 1 for top-level item', function () {
        $item = new StorageItem('folder', 'folder', true);

        expect($item->getDepth())->toBe(1);
    });

    it('returns correct depth for nested item', function () {
        $item = new StorageItem('a/b/c/d/file.txt', 'file.txt', false);

        expect($item->getDepth())->toBe(5);
    });
});

describe('duration methods', function () {
    it('returns null duration by default', function () {
        $item = new StorageItem('video.mp4', 'video.mp4', false);

        expect($item->getDuration())->toBeNull();
    });

    it('returns empty formatted duration for null', function () {
        $item = new StorageItem('video.mp4', 'video.mp4', false);

        expect($item->getFormattedDuration())->toBe('');
    });

    it('returns null thumbnail by default', function () {
        $item = new StorageItem('video.mp4', 'video.mp4', false);

        expect($item->getThumbnail())->toBeNull();
    });
});

describe('toArray method', function () {
    it('returns complete array representation', function () {
        $item = new StorageItem(
            path: 'folder/file.pdf',
            name: 'file.pdf',
            isDirectory: false,
            size: 1024,
            mimeType: 'application/pdf',
            lastModified: 1234567890,
            disk: 'testing'
        );

        $array = $item->toArray();

        expect($array)->toBeArray()
            ->and($array['identifier'])->toBe('folder/file.pdf')
            ->and($array['name'])->toBe('file.pdf')
            ->and($array['path'])->toBe('/folder/file.pdf')
            ->and($array['parent_path'])->toBe('folder')
            ->and($array['is_folder'])->toBeFalse()
            ->and($array['is_file'])->toBeTrue()
            ->and($array['size'])->toBe(1024)
            ->and($array['formatted_size'])->toBe('1 KB')
            ->and($array['mime_type'])->toBe('application/pdf')
            ->and($array['extension'])->toBe('pdf')
            ->and($array['last_modified'])->toBe(1234567890)
            ->and($array['is_document'])->toBeTrue()
            ->and($array['depth'])->toBe(2);
    });
});

describe('magic getter', function () {
    it('returns identifier via magic getter', function () {
        $item = new StorageItem('path/to/file.txt', 'file.txt', false);

        expect($item->id)->toBe('path/to/file.txt')
            ->and($item->identifier)->toBe('path/to/file.txt');
    });

    it('returns name via magic getter', function () {
        $item = new StorageItem('file.txt', 'file.txt', false);

        expect($item->name)->toBe('file.txt');
    });

    it('returns path via magic getter', function () {
        $item = new StorageItem('folder/file.txt', 'file.txt', false);

        expect($item->path)->toBe('/folder/file.txt');
    });

    it('returns size via magic getter', function () {
        $item = new StorageItem('file.txt', 'file.txt', false, size: 1024);

        expect($item->size)->toBe(1024);
    });

    it('returns storage_path via magic getter', function () {
        $item = new StorageItem('folder/file.txt', 'file.txt', false);

        expect($item->storage_path)->toBe('folder/file.txt');
    });

    it('returns null for unknown property', function () {
        $item = new StorageItem('file.txt', 'file.txt', false);

        expect($item->unknown_property)->toBeNull();
    });
});
