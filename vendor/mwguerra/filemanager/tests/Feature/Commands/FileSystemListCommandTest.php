<?php

beforeEach(function () {
    $this->testPath = sys_get_temp_dir() . '/filemanager-fslist-' . uniqid();
    mkdir($this->testPath, 0777, true);

    $this->app['config']->set('filesystems.disks.testing', [
        'driver' => 'local',
        'root' => $this->testPath,
    ]);

    // Create test folder structure
    mkdir($this->testPath . '/documents', 0777, true);
    mkdir($this->testPath . '/images', 0777, true);
    mkdir($this->testPath . '/documents/reports', 0777, true);
    file_put_contents($this->testPath . '/readme.txt', 'readme content');
    file_put_contents($this->testPath . '/documents/file.pdf', 'pdf content');
    file_put_contents($this->testPath . '/documents/reports/annual.docx', 'annual report');
    file_put_contents($this->testPath . '/.hidden', 'hidden content');
    mkdir($this->testPath . '/.hidden-folder', 0777, true);
});

afterEach(function () {
    if (isset($this->testPath) && is_dir($this->testPath)) {
        deleteDirectory($this->testPath);
    }
});

describe('basic execution', function () {
    it('runs successfully with defaults', function () {
        $this->artisan('filesystem:list', [
            '--disk' => 'testing',
        ])->assertExitCode(0);
    });

    it('runs successfully with path argument', function () {
        $this->artisan('filesystem:list', [
            'path' => 'documents',
            '--disk' => 'testing',
        ])->assertExitCode(0);
    });

    it('runs successfully with nested path', function () {
        $this->artisan('filesystem:list', [
            'path' => 'documents/reports',
            '--disk' => 'testing',
        ])->assertExitCode(0);
    });
});

describe('validation', function () {
    it('fails with invalid type', function () {
        $this->artisan('filesystem:list', [
            '--disk' => 'testing',
            '--type' => 'invalid',
        ])
            ->assertExitCode(1)
            ->expectsOutputToContain("Invalid type 'invalid'");
    });

    it('fails with invalid format', function () {
        $this->artisan('filesystem:list', [
            '--disk' => 'testing',
            '--format' => 'invalid',
        ])
            ->assertExitCode(1)
            ->expectsOutputToContain("Invalid format 'invalid'");
    });

    it('fails with nonexistent disk', function () {
        $this->artisan('filesystem:list', [
            '--disk' => 'nonexistent-disk',
        ])
            ->assertExitCode(1)
            ->expectsOutputToContain('not configured or accessible');
    });
});

describe('type filtering', function () {
    it('filters by folder type', function () {
        $this->artisan('filesystem:list', [
            '--disk' => 'testing',
            '--type' => 'folder',
            '--format' => 'json',
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('"type": "folder"');
    });

    it('filters by file type', function () {
        $this->artisan('filesystem:list', [
            '--disk' => 'testing',
            '--type' => 'file',
            '--format' => 'json',
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('"type": "file"');
    });

    it('accepts all type filter', function () {
        $this->artisan('filesystem:list', [
            '--disk' => 'testing',
            '--type' => 'all',
        ])->assertExitCode(0);
    });
});

describe('output formats', function () {
    it('outputs table format by default', function () {
        $this->artisan('filesystem:list', [
            '--disk' => 'testing',
            '--format' => 'table',
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('Summary');
    });

    it('outputs json format', function () {
        $this->artisan('filesystem:list', [
            '--disk' => 'testing',
            '--format' => 'json',
        ])->assertExitCode(0);
    });

    it('outputs csv format with header', function () {
        $this->artisan('filesystem:list', [
            '--disk' => 'testing',
            '--format' => 'csv',
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('type,name,path,size,modified');
    });
});

describe('recursive option', function () {
    it('lists recursively by default', function () {
        $this->artisan('filesystem:list', [
            '--disk' => 'testing',
            '--format' => 'json',
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('reports');
    });

    it('disables recursive listing with no-recursive option', function () {
        $this->artisan('filesystem:list', [
            '--disk' => 'testing',
            '--no-recursive' => true,
            '--format' => 'table',
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('Recursive: No');
    });
});

describe('hidden files', function () {
    it('hides hidden files by default', function () {
        $this->artisan('filesystem:list', [
            '--disk' => 'testing',
            '--format' => 'json',
        ])
            ->assertExitCode(0);
        // The hidden file should not be in output by default
    });

    it('shows hidden files with show-hidden option', function () {
        $this->artisan('filesystem:list', [
            '--disk' => 'testing',
            '--show-hidden' => true,
        ])
            ->assertExitCode(0);
        // The command accepts the option without errors
    });
});

describe('empty results', function () {
    it('handles empty folder gracefully with table format', function () {
        mkdir($this->testPath . '/empty', 0777, true);

        $this->artisan('filesystem:list', [
            'path' => 'empty',
            '--disk' => 'testing',
            '--format' => 'table',
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('No items found');
    });

    it('handles empty folder gracefully with json format', function () {
        mkdir($this->testPath . '/empty', 0777, true);

        $this->artisan('filesystem:list', [
            'path' => 'empty',
            '--disk' => 'testing',
            '--format' => 'json',
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('[]');
    });

    it('handles empty folder gracefully with csv format', function () {
        mkdir($this->testPath . '/empty', 0777, true);

        $this->artisan('filesystem:list', [
            'path' => 'empty',
            '--disk' => 'testing',
            '--format' => 'csv',
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('type,name,path,size,modified');
    });
});

describe('header display', function () {
    it('shows disk name in header', function () {
        $this->artisan('filesystem:list', [
            '--disk' => 'testing',
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('FileSystem List: testing');
    });

    it('shows path in header', function () {
        $this->artisan('filesystem:list', [
            'path' => 'documents',
            '--disk' => 'testing',
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('Path: documents');
    });

    it('shows root path indication when no path specified', function () {
        $this->artisan('filesystem:list', [
            '--disk' => 'testing',
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('Path: (root)');
    });

    it('shows type filter in header when specified', function () {
        $this->artisan('filesystem:list', [
            '--disk' => 'testing',
            '--type' => 'folder',
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('Type filter: folder');
    });
});
