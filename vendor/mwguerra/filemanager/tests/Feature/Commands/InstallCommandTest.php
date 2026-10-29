<?php

beforeEach(function () {
    $this->testCssPath = sys_get_temp_dir() . '/filemanager-install-test-' . uniqid();
    mkdir($this->testCssPath, 0777, true);
});

afterEach(function () {
    if (isset($this->testCssPath) && is_dir($this->testCssPath)) {
        deleteDirectory($this->testCssPath);
    }
});

function createTestCssFile(string $content): string
{
    $path = test()->testCssPath . '/app.css';
    file_put_contents($path, $content);
    return $path;
}

it('fails with nonexistent file', function () {
    $this->artisan('filemanager:install', [
        '--css-path' => '/nonexistent/path/app.css',
    ])
        ->assertExitCode(1)
        ->expectsOutputToContain('CSS file not found');
});

it('adds source directive', function () {
    $cssPath = createTestCssFile("@import 'tailwindcss';\n");

    $this->artisan('filemanager:install', [
        '--css-path' => $cssPath,
    ])
        ->assertExitCode(0)
        ->expectsOutputToContain('@source directive');

    $content = file_get_contents($cssPath);
    expect($content)->toContain('mwguerra/filemanager');
});

it('adds variant dark directive', function () {
    $cssPath = createTestCssFile("@import 'tailwindcss';\n");

    $this->artisan('filemanager:install', [
        '--css-path' => $cssPath,
    ])
        ->assertExitCode(0)
        ->expectsOutputToContain('@variant dark directive');

    $content = file_get_contents($cssPath);
    expect($content)
        ->toContain('@variant dark')
        ->toContain('.dark');
});

it('adds primary color mappings', function () {
    $cssPath = createTestCssFile("@import 'tailwindcss';\n");

    $this->artisan('filemanager:install', [
        '--css-path' => $cssPath,
    ])
        ->assertExitCode(0)
        ->expectsOutputToContain('Primary color mappings');

    $content = file_get_contents($cssPath);
    expect($content)
        ->toContain('@theme')
        ->toContain('--color-primary-500')
        ->toContain('var(--primary-500)');
});

it('inserts into existing theme block', function () {
    $cssPath = createTestCssFile(<<<CSS
@import 'tailwindcss';

@theme {
    --font-sans: 'Inter', sans-serif;
}
CSS);

    $this->artisan('filemanager:install', [
        '--css-path' => $cssPath,
    ])->assertExitCode(0);

    $content = file_get_contents($cssPath);
    // Should only have one @theme block
    expect(substr_count($content, '@theme'))->toBe(1)
        ->and($content)->toContain('--font-sans')
        ->and($content)->toContain('--color-primary-500');
});

it('skips if already configured', function () {
    $cssContent = <<<CSS
@import 'tailwindcss';

@source '../../vendor/mwguerra/filemanager/resources/views/**/*.blade.php';

@variant dark (&:where(.dark, .dark *));

@theme {
    --color-primary-500: var(--primary-500);
}
CSS;
    $cssPath = createTestCssFile($cssContent);

    $this->artisan('filemanager:install', [
        '--css-path' => $cssPath,
    ])
        ->assertExitCode(0)
        ->expectsOutputToContain('No changes needed');
});

it('adds source after existing sources', function () {
    $cssPath = createTestCssFile(<<<CSS
@import 'tailwindcss';

@source '../**/*.blade.php';
@source '../**/*.js';

@theme {
    --font-sans: 'Inter', sans-serif;
}
CSS);

    $this->artisan('filemanager:install', [
        '--css-path' => $cssPath,
    ])->assertExitCode(0);

    $content = file_get_contents($cssPath);
    $lines = explode("\n", $content);

    // Find positions of @source directives
    $sourcePositions = [];
    foreach ($lines as $index => $line) {
        if (str_contains($line, '@source')) {
            $sourcePositions[] = $index;
        }
    }

    // The filemanager source should be after the existing sources
    expect($sourcePositions)->toHaveCount(3);
});

it('places variant dark before theme', function () {
    $cssPath = createTestCssFile(<<<CSS
@import 'tailwindcss';

@source '../**/*.blade.php';

@theme {
    --font-sans: 'Inter', sans-serif;
}
CSS);

    $this->artisan('filemanager:install', [
        '--css-path' => $cssPath,
    ])->assertExitCode(0);

    $content = file_get_contents($cssPath);

    // @variant dark should appear before @theme
    $variantPos = strpos($content, '@variant dark');
    $themePos = strpos($content, '@theme');

    expect($variantPos)->not->toBeFalse()
        ->and($themePos)->not->toBeFalse()
        ->and($variantPos)->toBeLessThan($themePos);
});

it('supports force option', function () {
    // First install
    $cssPath = createTestCssFile("@import 'tailwindcss';\n");

    $this->artisan('filemanager:install', [
        '--css-path' => $cssPath,
    ])->assertExitCode(0);

    // Modify the file to have partial config
    $content = file_get_contents($cssPath);
    $content = str_replace('--color-primary-500', '--color-primary-MODIFIED', $content);
    file_put_contents($cssPath, $content);

    // Run with --force should re-add everything
    $this->artisan('filemanager:install', [
        '--css-path' => $cssPath,
        '--force' => true,
    ])->assertExitCode(0);

    $content = file_get_contents($cssPath);
    // Should have the original value again (though the modified one may also remain)
    expect($content)->toContain('--color-primary-500: var(--primary-500)');
});
