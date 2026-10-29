<?php

use Illuminate\Support\Facades\Schema;
use MWGuerra\FileManager\Models\FileSystemItem;

beforeEach(function () {
    $this->testPath = sys_get_temp_dir() . '/filemanager-rebuild-' . uniqid();
    mkdir($this->testPath, 0777, true);

    $this->app['config']->set('filesystems.disks.testing', [
        'driver' => 'local',
        'root' => $this->testPath,
    ]);

    $this->app['config']->set('filemanager.model', FileSystemItem::class);

    // Create test folder structure in storage
    mkdir($this->testPath . '/documents', 0777, true);
    mkdir($this->testPath . '/images', 0777, true);
    mkdir($this->testPath . '/documents/reports', 0777, true);
    file_put_contents($this->testPath . '/readme.txt', 'readme content');
    file_put_contents($this->testPath . '/documents/contract.pdf', 'pdf content');
    file_put_contents($this->testPath . '/images/logo.png', 'png content');
    file_put_contents($this->testPath . '/.hidden', 'hidden content');
});

afterEach(function () {
    if (isset($this->testPath) && is_dir($this->testPath)) {
        deleteDirectory($this->testPath);
    }
});

describe('validation', function () {
    it('fails with nonexistent disk', function () {
        $this->artisan('filemanager:rebuild', [
            '--disk' => 'nonexistent-disk',
            '--force' => true,
        ])
            ->assertExitCode(1)
            ->expectsOutputToContain('not configured or accessible');
    });

    it('fails when model class is not configured', function () {
        $this->app['config']->set('filemanager.model', null);

        $this->artisan('filemanager:rebuild', [
            '--disk' => 'testing',
            '--force' => true,
        ])
            ->assertExitCode(1)
            ->expectsOutputToContain('Model class not found');
    });

    it('fails when model class does not exist', function () {
        $this->app['config']->set('filemanager.model', 'NonExistent\\Model\\Class');

        $this->artisan('filemanager:rebuild', [
            '--disk' => 'testing',
            '--force' => true,
        ])
            ->assertExitCode(1)
            ->expectsOutputToContain('Model class not found');
    });
});

describe('confirmation', function () {
    it('cancels operation when user declines confirmation', function () {
        // Skip if no database
        if (!Schema::hasTable('file_system_items')) {
            $this->markTestSkipped('Database table not available');
        }

        $this->artisan('filemanager:rebuild', [
            '--disk' => 'testing',
        ])
            ->expectsConfirmation('Do you want to proceed?', 'no')
            ->expectsOutputToContain('Operation cancelled')
            ->assertExitCode(0);
    });

    it('skips confirmation with force option', function () {
        // Skip if no database
        if (!Schema::hasTable('file_system_items')) {
            $this->markTestSkipped('Database table not available');
        }

        $this->artisan('filemanager:rebuild', [
            '--disk' => 'testing',
            '--force' => true,
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('Rebuild complete');
    });
});

describe('rebuild process', function () {
    it('displays header information', function () {
        // Skip if no database
        if (!Schema::hasTable('file_system_items')) {
            $this->markTestSkipped('Database table not available');
        }

        $this->artisan('filemanager:rebuild', [
            '--disk' => 'testing',
            '--force' => true,
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('FileManager Rebuild Command')
            ->expectsOutputToContain('Disk: testing');
    });

    it('shows root path information', function () {
        // Skip if no database
        if (!Schema::hasTable('file_system_items')) {
            $this->markTestSkipped('Database table not available');
        }

        $this->artisan('filemanager:rebuild', [
            '--disk' => 'testing',
            '--force' => true,
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('Root:');
    });

    it('scans and creates folders', function () {
        // Skip if no database
        if (!Schema::hasTable('file_system_items')) {
            $this->markTestSkipped('Database table not available');
        }

        $this->artisan('filemanager:rebuild', [
            '--disk' => 'testing',
            '--force' => true,
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('Creating folder: documents')
            ->expectsOutputToContain('Creating folder: images');
    });

    it('scans and creates files', function () {
        // Skip if no database
        if (!Schema::hasTable('file_system_items')) {
            $this->markTestSkipped('Database table not available');
        }

        $this->artisan('filemanager:rebuild', [
            '--disk' => 'testing',
            '--force' => true,
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('Creating file: readme.txt');
    });

    it('shows summary table on completion', function () {
        // Skip if no database
        if (!Schema::hasTable('file_system_items')) {
            $this->markTestSkipped('Database table not available');
        }

        $this->artisan('filemanager:rebuild', [
            '--disk' => 'testing',
            '--force' => true,
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('Folders')
            ->expectsOutputToContain('Files')
            ->expectsOutputToContain('Total');
    });

    it('creates database records', function () {
        // Skip if no database
        if (!Schema::hasTable('file_system_items')) {
            $this->markTestSkipped('Database table not available');
        }

        // Clear existing records
        FileSystemItem::query()->delete();

        $this->artisan('filemanager:rebuild', [
            '--disk' => 'testing',
            '--force' => true,
        ])->assertExitCode(0);

        // Verify records were created
        expect(FileSystemItem::count())->toBeGreaterThan(0);
    });

    it('clears existing records before rebuild', function () {
        // Skip if no database
        if (!Schema::hasTable('file_system_items')) {
            $this->markTestSkipped('Database table not available');
        }

        // Create some existing records
        FileSystemItem::create([
            'name' => 'old-folder',
            'type' => 'folder',
            'parent_id' => null,
        ]);

        $existingCount = FileSystemItem::count();
        expect($existingCount)->toBeGreaterThan(0);

        $this->artisan('filemanager:rebuild', [
            '--disk' => 'testing',
            '--force' => true,
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain("Deleted {$existingCount} record(s)");

        // Old record should be gone
        expect(FileSystemItem::where('name', 'old-folder')->exists())->toBeFalse();
    });

    it('skips hidden files by default', function () {
        // Skip if no database
        if (!Schema::hasTable('file_system_items')) {
            $this->markTestSkipped('Database table not available');
        }

        FileSystemItem::query()->delete();

        $this->artisan('filemanager:rebuild', [
            '--disk' => 'testing',
            '--force' => true,
        ])->assertExitCode(0);

        // Hidden file should not be in database
        expect(FileSystemItem::where('name', '.hidden')->exists())->toBeFalse();
    });
});

describe('root option', function () {
    it('accepts custom root path', function () {
        // Skip if no database
        if (!Schema::hasTable('file_system_items')) {
            $this->markTestSkipped('Database table not available');
        }

        $this->artisan('filemanager:rebuild', [
            '--disk' => 'testing',
            '--root' => 'documents',
            '--force' => true,
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('Root: documents');
    });
});

describe('warning for existing records', function () {
    it('warns when there are existing records', function () {
        // Skip if no database
        if (!Schema::hasTable('file_system_items')) {
            $this->markTestSkipped('Database table not available');
        }

        // Create an existing record
        FileSystemItem::query()->delete();
        FileSystemItem::create([
            'name' => 'existing-folder',
            'type' => 'folder',
            'parent_id' => null,
        ]);

        $this->artisan('filemanager:rebuild', [
            '--disk' => 'testing',
            '--force' => true,
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('This will delete 1 existing record(s)');
    });
});
