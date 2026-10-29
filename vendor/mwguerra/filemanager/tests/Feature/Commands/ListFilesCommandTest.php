<?php

beforeEach(function () {
    $this->testPath = sys_get_temp_dir() . '/filemanager-test-' . uniqid();
    mkdir($this->testPath, 0777, true);

    $this->app['config']->set('filesystems.disks.testing', [
        'driver' => 'local',
        'root' => $this->testPath,
    ]);

    // Create test folder structure
    mkdir($this->testPath . '/folder1', 0777, true);
    mkdir($this->testPath . '/folder2', 0777, true);
    mkdir($this->testPath . '/folder1/subfolder', 0777, true);
    file_put_contents($this->testPath . '/file1.txt', 'content');
    file_put_contents($this->testPath . '/folder1/file2.txt', 'content');
    file_put_contents($this->testPath . '/.hidden', 'hidden content');
});

afterEach(function () {
    if (isset($this->testPath) && is_dir($this->testPath)) {
        deleteDirectory($this->testPath);
    }
});

it('runs successfully in storage mode', function () {
    $this->artisan('filemanager:list', [
        '--mode' => 'storage',
        '--disk' => 'testing',
    ])->assertExitCode(0);
});

it('fails with invalid mode', function () {
    $this->artisan('filemanager:list', [
        '--mode' => 'invalid',
        '--disk' => 'testing',
    ])
        ->assertExitCode(1)
        ->expectsOutputToContain("Invalid mode 'invalid'");
});

it('fails with invalid type', function () {
    $this->artisan('filemanager:list', [
        '--mode' => 'storage',
        '--disk' => 'testing',
        '--type' => 'invalid',
    ])
        ->assertExitCode(1)
        ->expectsOutputToContain("Invalid type 'invalid'");
});

it('fails with invalid format', function () {
    $this->artisan('filemanager:list', [
        '--mode' => 'storage',
        '--disk' => 'testing',
        '--format' => 'invalid',
    ])
        ->assertExitCode(1)
        ->expectsOutputToContain("Invalid format 'invalid'");
});

it('fails with nonexistent disk', function () {
    $this->artisan('filemanager:list', [
        '--mode' => 'storage',
        '--disk' => 'nonexistent',
    ])
        ->assertExitCode(1)
        ->expectsOutputToContain('not configured or accessible');
});

it('outputs table format', function () {
    $this->artisan('filemanager:list', [
        '--mode' => 'storage',
        '--disk' => 'testing',
        '--format' => 'table',
    ])
        ->assertExitCode(0)
        ->expectsOutputToContain('Summary');
});

it('outputs json format structure', function () {
    $this->artisan('filemanager:list', [
        '--mode' => 'storage',
        '--disk' => 'testing',
        '--format' => 'json',
    ])
        ->assertExitCode(0)
        ->expectsOutputToContain('"items"');
});

it('outputs csv format header', function () {
    $this->artisan('filemanager:list', [
        '--mode' => 'storage',
        '--disk' => 'testing',
        '--format' => 'csv',
    ])
        ->assertExitCode(0)
        ->expectsOutputToContain('type,name,id,path,size,modified');
});

it('filters by folder type', function () {
    $this->artisan('filemanager:list', [
        '--mode' => 'storage',
        '--disk' => 'testing',
        '--type' => 'folder',
    ])->assertExitCode(0);
});

it('filters by file type', function () {
    $this->artisan('filemanager:list', [
        '--mode' => 'storage',
        '--disk' => 'testing',
        '--type' => 'file',
    ])->assertExitCode(0);
});

it('supports recursive option', function () {
    $this->artisan('filemanager:list', [
        '--mode' => 'storage',
        '--disk' => 'testing',
        '--recursive' => true,
    ])->assertExitCode(0);
});

it('supports show-hidden option', function () {
    $this->artisan('filemanager:list', [
        '--mode' => 'storage',
        '--disk' => 'testing',
        '--show-hidden' => true,
    ])->assertExitCode(0);
});

it('supports path argument', function () {
    $this->artisan('filemanager:list', [
        'path' => 'folder1',
        '--mode' => 'storage',
        '--disk' => 'testing',
    ])->assertExitCode(0);
});

it('supports target option', function () {
    $this->artisan('filemanager:list', [
        '--mode' => 'storage',
        '--disk' => 'testing',
        '--target' => 'folder1',
    ])->assertExitCode(0);
});
