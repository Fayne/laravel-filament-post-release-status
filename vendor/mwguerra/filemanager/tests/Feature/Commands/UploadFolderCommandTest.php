<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use MWGuerra\FileManager\Models\FileSystemItem;

beforeEach(function () {
    // Create local source folder
    $this->sourcePath = sys_get_temp_dir() . '/filemanager-upload-source-' . uniqid();
    mkdir($this->sourcePath, 0777, true);

    // Create storage destination
    $this->destPath = sys_get_temp_dir() . '/filemanager-upload-dest-' . uniqid();
    mkdir($this->destPath, 0777, true);

    $this->app['config']->set('filesystems.disks.testing', [
        'driver' => 'local',
        'root' => $this->destPath,
    ]);

    $this->app['config']->set('filemanager.model', FileSystemItem::class);

    // Create source folder structure
    mkdir($this->sourcePath . '/documents', 0777, true);
    mkdir($this->sourcePath . '/images', 0777, true);
    mkdir($this->sourcePath . '/documents/reports', 0777, true);
    file_put_contents($this->sourcePath . '/readme.txt', 'readme content');
    file_put_contents($this->sourcePath . '/documents/contract.pdf', 'pdf content');
    file_put_contents($this->sourcePath . '/images/logo.png', 'png content');
    file_put_contents($this->sourcePath . '/.hidden', 'hidden content');
});

afterEach(function () {
    if (isset($this->sourcePath) && is_dir($this->sourcePath)) {
        deleteDirectory($this->sourcePath);
    }
    if (isset($this->destPath) && is_dir($this->destPath)) {
        deleteDirectory($this->destPath);
    }
});

describe('validation', function () {
    it('fails when source path does not exist', function () {
        $this->artisan('filemanager:upload', [
            'path' => '/nonexistent/path',
            '--disk' => 'testing',
            '--force' => true,
        ])
            ->assertExitCode(1)
            ->expectsOutputToContain('Path does not exist');
    });

    it('fails when source path is not a directory', function () {
        $filePath = $this->sourcePath . '/readme.txt';

        $this->artisan('filemanager:upload', [
            'path' => $filePath,
            '--disk' => 'testing',
            '--force' => true,
        ])
            ->assertExitCode(1)
            ->expectsOutputToContain('Path is not a directory');
    });

    it('fails with nonexistent disk', function () {
        $this->artisan('filemanager:upload', [
            'path' => $this->sourcePath,
            '--disk' => 'nonexistent-disk',
            '--force' => true,
        ])
            ->assertExitCode(1)
            ->expectsOutputToContain('not configured or accessible');
    });

    it('fails when model class is not configured and database is enabled', function () {
        $this->app['config']->set('filemanager.model', null);

        $this->artisan('filemanager:upload', [
            'path' => $this->sourcePath,
            '--disk' => 'testing',
            '--force' => true,
        ])
            ->assertExitCode(1)
            ->expectsOutputToContain('Model class not found');
    });

    it('fails when model class does not exist and database is enabled', function () {
        $this->app['config']->set('filemanager.model', 'NonExistent\\Model\\Class');

        $this->artisan('filemanager:upload', [
            'path' => $this->sourcePath,
            '--disk' => 'testing',
            '--force' => true,
        ])
            ->assertExitCode(1)
            ->expectsOutputToContain('Model class not found');
    });
});

describe('confirmation', function () {
    it('cancels operation when user declines confirmation', function () {
        $this->artisan('filemanager:upload', [
            'path' => $this->sourcePath,
            '--disk' => 'testing',
            '--no-database' => true,
        ])
            ->expectsConfirmation('Do you want to proceed?', 'no')
            ->expectsOutputToContain('Operation cancelled')
            ->assertExitCode(0);
    });

    it('skips confirmation with force option', function () {
        $this->artisan('filemanager:upload', [
            'path' => $this->sourcePath,
            '--disk' => 'testing',
            '--no-database' => true,
            '--force' => true,
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('Upload complete');
    });
});

describe('header display', function () {
    it('displays header information', function () {
        $this->artisan('filemanager:upload', [
            'path' => $this->sourcePath,
            '--disk' => 'testing',
            '--no-database' => true,
            '--force' => true,
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('FileManager Upload Command')
            ->expectsOutputToContain('Source:')
            ->expectsOutputToContain('Disk: testing');
    });

    it('shows target path information', function () {
        $this->artisan('filemanager:upload', [
            'path' => $this->sourcePath,
            '--disk' => 'testing',
            '--target' => 'uploads',
            '--no-database' => true,
            '--force' => true,
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('Target: uploads');
    });

    it('shows root target indication when no target specified', function () {
        $this->artisan('filemanager:upload', [
            'path' => $this->sourcePath,
            '--disk' => 'testing',
            '--no-database' => true,
            '--force' => true,
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('Target: (root)');
    });

    it('shows database records status', function () {
        $this->artisan('filemanager:upload', [
            'path' => $this->sourcePath,
            '--disk' => 'testing',
            '--no-database' => true,
            '--force' => true,
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('Database records: No');
    });

    it('shows file count information', function () {
        $this->artisan('filemanager:upload', [
            'path' => $this->sourcePath,
            '--disk' => 'testing',
            '--no-database' => true,
            '--force' => true,
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('Total size:');
    });
});

describe('upload process without database', function () {
    it('uploads files to storage', function () {
        $this->artisan('filemanager:upload', [
            'path' => $this->sourcePath,
            '--disk' => 'testing',
            '--no-database' => true,
            '--force' => true,
        ])->assertExitCode(0);

        // Verify files were uploaded
        expect(Storage::disk('testing')->exists('readme.txt'))->toBeTrue();
        expect(Storage::disk('testing')->exists('documents/contract.pdf'))->toBeTrue();
        expect(Storage::disk('testing')->exists('images/logo.png'))->toBeTrue();
    });

    it('creates folders in storage', function () {
        $this->artisan('filemanager:upload', [
            'path' => $this->sourcePath,
            '--disk' => 'testing',
            '--no-database' => true,
            '--force' => true,
        ])->assertExitCode(0);

        // Verify directories were created
        expect(Storage::disk('testing')->exists('documents'))->toBeTrue();
        expect(Storage::disk('testing')->exists('images'))->toBeTrue();
        expect(Storage::disk('testing')->exists('documents/reports'))->toBeTrue();
    });

    it('uploads to target path', function () {
        $this->artisan('filemanager:upload', [
            'path' => $this->sourcePath,
            '--disk' => 'testing',
            '--target' => 'uploads/2024',
            '--no-database' => true,
            '--force' => true,
        ])->assertExitCode(0);

        // Verify files were uploaded to target path
        expect(Storage::disk('testing')->exists('uploads/2024/readme.txt'))->toBeTrue();
        expect(Storage::disk('testing')->exists('uploads/2024/documents'))->toBeTrue();
    });

    it('skips existing files', function () {
        // Pre-create a file
        Storage::disk('testing')->put('readme.txt', 'original content');

        $this->artisan('filemanager:upload', [
            'path' => $this->sourcePath,
            '--disk' => 'testing',
            '--no-database' => true,
            '--force' => true,
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('Skipping (exists): readme.txt');

        // Verify original content is preserved
        expect(Storage::disk('testing')->get('readme.txt'))->toBe('original content');
    });

    it('skips hidden files', function () {
        $this->artisan('filemanager:upload', [
            'path' => $this->sourcePath,
            '--disk' => 'testing',
            '--no-database' => true,
            '--force' => true,
        ])->assertExitCode(0);

        // Verify hidden file was not uploaded
        expect(Storage::disk('testing')->exists('.hidden'))->toBeFalse();
    });

    it('shows summary on completion', function () {
        $this->artisan('filemanager:upload', [
            'path' => $this->sourcePath,
            '--disk' => 'testing',
            '--no-database' => true,
            '--force' => true,
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('Upload complete')
            ->expectsOutputToContain('Folders created')
            ->expectsOutputToContain('Files uploaded')
            ->expectsOutputToContain('Files skipped')
            ->expectsOutputToContain('Data transferred');
    });
});

describe('upload process with database', function () {
    it('creates database records', function () {
        // Skip if no database
        if (!Schema::hasTable('file_system_items')) {
            $this->markTestSkipped('Database table not available');
        }

        FileSystemItem::query()->delete();

        $this->artisan('filemanager:upload', [
            'path' => $this->sourcePath,
            '--disk' => 'testing',
            '--force' => true,
        ])->assertExitCode(0);

        // Verify records were created
        expect(FileSystemItem::count())->toBeGreaterThan(0);
    });

    it('creates folder records in database', function () {
        // Skip if no database
        if (!Schema::hasTable('file_system_items')) {
            $this->markTestSkipped('Database table not available');
        }

        FileSystemItem::query()->delete();

        $this->artisan('filemanager:upload', [
            'path' => $this->sourcePath,
            '--disk' => 'testing',
            '--force' => true,
        ])->assertExitCode(0);

        // Verify folder records
        expect(FileSystemItem::where('name', 'documents')->where('type', 'folder')->exists())->toBeTrue();
        expect(FileSystemItem::where('name', 'images')->where('type', 'folder')->exists())->toBeTrue();
    });

    it('creates file records in database', function () {
        // Skip if no database
        if (!Schema::hasTable('file_system_items')) {
            $this->markTestSkipped('Database table not available');
        }

        FileSystemItem::query()->delete();

        $this->artisan('filemanager:upload', [
            'path' => $this->sourcePath,
            '--disk' => 'testing',
            '--force' => true,
        ])->assertExitCode(0);

        // Verify file records
        expect(FileSystemItem::where('name', 'readme.txt')->where('type', 'file')->exists())->toBeTrue();
    });

    it('shows database records status as yes', function () {
        // Skip if no database
        if (!Schema::hasTable('file_system_items')) {
            $this->markTestSkipped('Database table not available');
        }

        $this->artisan('filemanager:upload', [
            'path' => $this->sourcePath,
            '--disk' => 'testing',
            '--force' => true,
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('Database records: Yes');
    });

    it('creates target folder in database when specified', function () {
        // Skip if no database
        if (!Schema::hasTable('file_system_items')) {
            $this->markTestSkipped('Database table not available');
        }

        FileSystemItem::query()->delete();

        $this->artisan('filemanager:upload', [
            'path' => $this->sourcePath,
            '--disk' => 'testing',
            '--target' => 'uploads/2024',
            '--force' => true,
        ])->assertExitCode(0);

        // Verify target folder records
        expect(FileSystemItem::where('name', 'uploads')->where('type', 'folder')->exists())->toBeTrue();
        expect(FileSystemItem::where('name', '2024')->where('type', 'folder')->exists())->toBeTrue();
    });
});

describe('progress output', function () {
    it('shows folder creation progress', function () {
        $this->artisan('filemanager:upload', [
            'path' => $this->sourcePath,
            '--disk' => 'testing',
            '--no-database' => true,
            '--force' => true,
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('Creating folder:');
    });

    it('shows file upload progress', function () {
        $this->artisan('filemanager:upload', [
            'path' => $this->sourcePath,
            '--disk' => 'testing',
            '--no-database' => true,
            '--force' => true,
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('Uploading file:');
    });
});
