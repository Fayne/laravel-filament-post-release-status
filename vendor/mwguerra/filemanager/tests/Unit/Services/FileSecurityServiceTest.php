<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use MWGuerra\FileManager\Services\FileSecurityService;

beforeEach(function () {
    $this->service = new FileSecurityService();

    // Set default security config
    config()->set('filemanager.security.blocked_extensions', [
        'php', 'phar', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phps',
        'exe', 'bat', 'cmd', 'sh', 'bash', 'com', 'msi', 'dll', 'scr',
        'js', 'vbs', 'wsf', 'wsh', 'ps1', 'psm1',
        'htaccess', 'htpasswd',
    ]);
    config()->set('filemanager.security.validate_mime', true);
    config()->set('filemanager.security.sanitize_filenames', true);
    config()->set('filemanager.security.max_filename_length', 255);
    config()->set('filemanager.security.rename_uploads', false);
    config()->set('filemanager.security.blocked_filename_patterns', []);
    config()->set('filemanager.upload.allowed_mimes', []);
});

describe('isBlockedExtension', function () {
    it('returns true for blocked extensions', function () {
        expect($this->service->isBlockedExtension('php'))->toBeTrue();
        expect($this->service->isBlockedExtension('exe'))->toBeTrue();
        expect($this->service->isBlockedExtension('bat'))->toBeTrue();
        expect($this->service->isBlockedExtension('sh'))->toBeTrue();
    });

    it('returns true case-insensitively', function () {
        expect($this->service->isBlockedExtension('PHP'))->toBeTrue();
        expect($this->service->isBlockedExtension('Exe'))->toBeTrue();
        expect($this->service->isBlockedExtension('BAT'))->toBeTrue();
    });

    it('returns false for allowed extensions', function () {
        expect($this->service->isBlockedExtension('pdf'))->toBeFalse();
        expect($this->service->isBlockedExtension('jpg'))->toBeFalse();
        expect($this->service->isBlockedExtension('txt'))->toBeFalse();
        expect($this->service->isBlockedExtension('mp4'))->toBeFalse();
    });
});

describe('hasDoubleExtension', function () {
    it('returns false for single extension', function () {
        expect($this->service->hasDoubleExtension('file.pdf'))->toBeFalse();
        expect($this->service->hasDoubleExtension('document.txt'))->toBeFalse();
    });

    it('returns false for safe double extensions', function () {
        expect($this->service->hasDoubleExtension('file.backup.pdf'))->toBeFalse();
        expect($this->service->hasDoubleExtension('archive.tar.gz'))->toBeFalse();
    });

    it('returns true for dangerous double extensions', function () {
        expect($this->service->hasDoubleExtension('file.php.jpg'))->toBeTrue();
        expect($this->service->hasDoubleExtension('script.exe.pdf'))->toBeTrue();
        expect($this->service->hasDoubleExtension('shell.sh.txt'))->toBeTrue();
    });

    it('returns false for files without extension', function () {
        expect($this->service->hasDoubleExtension('README'))->toBeFalse();
        expect($this->service->hasDoubleExtension('Makefile'))->toBeFalse();
    });
});

describe('validateMimeType', function () {
    it('validates image MIME types', function () {
        expect($this->service->validateMimeType('jpg', 'image/jpeg'))->toBeTrue();
        expect($this->service->validateMimeType('jpeg', 'image/jpeg'))->toBeTrue();
        expect($this->service->validateMimeType('png', 'image/png'))->toBeTrue();
        expect($this->service->validateMimeType('gif', 'image/gif'))->toBeTrue();
        expect($this->service->validateMimeType('webp', 'image/webp'))->toBeTrue();
    });

    it('validates video MIME types', function () {
        expect($this->service->validateMimeType('mp4', 'video/mp4'))->toBeTrue();
        expect($this->service->validateMimeType('webm', 'video/webm'))->toBeTrue();
        expect($this->service->validateMimeType('mov', 'video/quicktime'))->toBeTrue();
    });

    it('validates audio MIME types', function () {
        expect($this->service->validateMimeType('mp3', 'audio/mpeg'))->toBeTrue();
        expect($this->service->validateMimeType('wav', 'audio/wav'))->toBeTrue();
        expect($this->service->validateMimeType('wav', 'audio/x-wav'))->toBeTrue();
    });

    it('validates document MIME types', function () {
        expect($this->service->validateMimeType('pdf', 'application/pdf'))->toBeTrue();
        expect($this->service->validateMimeType('txt', 'text/plain'))->toBeTrue();
        expect($this->service->validateMimeType('json', 'application/json'))->toBeTrue();
    });

    it('validates archive MIME types', function () {
        expect($this->service->validateMimeType('zip', 'application/zip'))->toBeTrue();
        expect($this->service->validateMimeType('gz', 'application/gzip'))->toBeTrue();
    });

    it('returns false for mismatched MIME types', function () {
        expect($this->service->validateMimeType('jpg', 'application/pdf'))->toBeFalse();
        expect($this->service->validateMimeType('pdf', 'image/jpeg'))->toBeFalse();
        expect($this->service->validateMimeType('mp4', 'audio/mpeg'))->toBeFalse();
    });

    it('returns false for null MIME type', function () {
        expect($this->service->validateMimeType('jpg', null))->toBeFalse();
    });

    it('handles unknown extensions based on allowed mimes', function () {
        config()->set('filemanager.upload.allowed_mimes', ['application/octet-stream']);
        expect($this->service->validateMimeType('xyz', 'application/octet-stream'))->toBeTrue();

        config()->set('filemanager.upload.allowed_mimes', []);
        expect($this->service->validateMimeType('xyz', 'application/octet-stream'))->toBeFalse();
    });
});

describe('sanitizeFilename', function () {
    it('keeps valid filenames unchanged', function () {
        expect($this->service->sanitizeFilename('document.pdf'))->toBe('document.pdf');
        expect($this->service->sanitizeFilename('my-file_name.txt'))->toBe('my-file_name.txt');
    });

    it('removes special characters', function () {
        $result = $this->service->sanitizeFilename('file<>:"|?*.pdf');
        expect($result)->not->toContain('<')
            ->and($result)->not->toContain('>')
            ->and($result)->not->toContain(':')
            ->and($result)->not->toContain('"')
            ->and($result)->not->toContain('|')
            ->and($result)->not->toContain('?')
            ->and($result)->not->toContain('*');
    });

    it('converts spaces to underscores', function () {
        expect($this->service->sanitizeFilename('my file name.pdf'))->toBe('my_file_name.pdf');
    });

    it('collapses multiple underscores', function () {
        expect($this->service->sanitizeFilename('file___name.pdf'))->toBe('file_name.pdf');
    });

    it('generates default name for empty result', function () {
        $result = $this->service->sanitizeFilename('<>:"|?*.pdf');
        expect($result)->toMatch('/^file_\d+\.pdf$/');
    });

    it('preserves unicode characters', function () {
        expect($this->service->sanitizeFilename('arquivo.pdf'))->toBe('arquivo.pdf');
        expect($this->service->sanitizeFilename('dokument.pdf'))->toBe('dokument.pdf');
    });

    it('adds random prefix when configured', function () {
        config()->set('filemanager.security.rename_uploads', true);

        $result = $this->service->sanitizeFilename('document.pdf');

        expect($result)->toMatch('/^[a-f0-9]{8}_document\.pdf$/');
    });

    it('respects disabled sanitization config', function () {
        config()->set('filemanager.security.sanitize_filenames', false);

        expect($this->service->sanitizeFilename('file name.pdf'))->toBe('file name.pdf');
    });

    it('handles files without extension', function () {
        expect($this->service->sanitizeFilename('README'))->toBe('README');
    });
});

describe('truncateFilename', function () {
    it('returns filename unchanged if within limit', function () {
        expect($this->service->truncateFilename('short.pdf', 255))->toBe('short.pdf');
    });

    it('truncates long filenames preserving extension', function () {
        $longName = str_repeat('a', 300) . '.pdf';
        $result = $this->service->truncateFilename($longName, 50);

        expect(strlen($result))->toBeLessThanOrEqual(50);
        expect($result)->toEndWith('.pdf');
    });

    it('handles very short max length', function () {
        $result = $this->service->truncateFilename('document.pdf', 5);

        expect(strlen($result))->toBeLessThanOrEqual(5);
    });

    it('handles files without extension', function () {
        $result = $this->service->truncateFilename('verylongfilename', 10);

        expect(strlen($result))->toBe(10);
    });
});

describe('needsSanitization', function () {
    it('returns true for configured extensions', function () {
        config()->set('filemanager.security.sanitize_extensions', ['svg', 'html', 'htm']);

        expect($this->service->needsSanitization('svg'))->toBeTrue();
        expect($this->service->needsSanitization('html'))->toBeTrue();
        expect($this->service->needsSanitization('htm'))->toBeTrue();
    });

    it('returns true case-insensitively', function () {
        config()->set('filemanager.security.sanitize_extensions', ['svg']);

        expect($this->service->needsSanitization('SVG'))->toBeTrue();
        expect($this->service->needsSanitization('Svg'))->toBeTrue();
    });

    it('returns false for non-configured extensions', function () {
        config()->set('filemanager.security.sanitize_extensions', ['svg']);

        expect($this->service->needsSanitization('pdf'))->toBeFalse();
        expect($this->service->needsSanitization('jpg'))->toBeFalse();
    });
});

describe('sanitizeSvg', function () {
    it('removes script tags', function () {
        $svg = '<svg><script>alert("xss")</script><rect/></svg>';
        $result = $this->service->sanitizeSvg($svg);

        expect($result)->not->toContain('<script');
        expect($result)->not->toContain('alert');
    });

    it('removes onclick handlers', function () {
        $svg = '<svg><rect onclick="alert(1)"/></svg>';
        $result = $this->service->sanitizeSvg($svg);

        expect($result)->not->toContain('onclick');
    });

    it('removes onload handlers', function () {
        $svg = '<svg onload="alert(1)"><rect/></svg>';
        $result = $this->service->sanitizeSvg($svg);

        expect($result)->not->toContain('onload');
    });

    it('removes javascript: URLs', function () {
        $svg = '<svg><a href="javascript:alert(1)">link</a></svg>';
        $result = $this->service->sanitizeSvg($svg);

        expect($result)->not->toContain('javascript:');
    });

    it('removes xlink:href javascript: URLs', function () {
        $svg = '<svg><use xlink:href="javascript:alert(1)"/></svg>';
        $result = $this->service->sanitizeSvg($svg);

        expect($result)->not->toContain('javascript:');
    });

    it('removes data: URLs in href', function () {
        $svg = '<svg><a href="data:text/html,<script>alert(1)</script>">link</a></svg>';
        $result = $this->service->sanitizeSvg($svg);

        expect($result)->not->toContain('data:');
    });

    it('removes foreignObject elements', function () {
        $svg = '<svg><foreignObject><body><script>alert(1)</script></body></foreignObject></svg>';
        $result = $this->service->sanitizeSvg($svg);

        expect($result)->not->toContain('foreignObject');
    });

    it('removes external resource use elements', function () {
        $svg = '<svg><use xlink:href="https://evil.com/exploit.svg#id"/></svg>';
        $result = $this->service->sanitizeSvg($svg);

        expect($result)->not->toContain('https://evil.com');
    });

    it('preserves safe SVG content', function () {
        $svg = '<svg viewBox="0 0 100 100"><rect x="10" y="10" width="80" height="80" fill="red"/></svg>';
        $result = $this->service->sanitizeSvg($svg);

        expect($result)->toContain('rect');
        expect($result)->toContain('fill="red"');
    });
});

describe('validateUpload', function () {
    it('rejects blocked extensions', function () {
        $file = UploadedFile::fake()->create('malware.php', 100);

        Log::shouldReceive('warning')
            ->once()
            ->with('FileManager blocked file upload: dangerous extension', Mockery::type('array'));

        $result = $this->service->validateUpload($file);

        expect($result['valid'])->toBeFalse();
        expect($result['error'])->toContain('.php');
        expect($result['sanitized_name'])->toBeNull();
    });

    it('rejects double extensions', function () {
        $file = UploadedFile::fake()->create('exploit.php.jpg', 100, 'image/jpeg');

        Log::shouldReceive('warning')
            ->once()
            ->with('FileManager blocked file upload: double extension', Mockery::type('array'));

        $result = $this->service->validateUpload($file);

        expect($result['valid'])->toBeFalse();
        expect($result['error'])->toContain('multiple extensions');
    });

    it('accepts valid files', function () {
        config()->set('filemanager.security.validate_mime', false);
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $result = $this->service->validateUpload($file);

        expect($result['valid'])->toBeTrue();
        expect($result['error'])->toBeNull();
        expect($result['sanitized_name'])->toBe('document.pdf');
    });

    it('sanitizes filenames', function () {
        config()->set('filemanager.security.validate_mime', false);
        $file = UploadedFile::fake()->create('my file name.pdf', 100);

        $result = $this->service->validateUpload($file);

        expect($result['valid'])->toBeTrue();
        expect($result['sanitized_name'])->toBe('my_file_name.pdf');
    });

    it('truncates long filenames', function () {
        config()->set('filemanager.security.validate_mime', false);
        config()->set('filemanager.security.max_filename_length', 20);

        $longName = str_repeat('a', 50) . '.pdf';
        $file = UploadedFile::fake()->create($longName, 100);

        $result = $this->service->validateUpload($file);

        expect($result['valid'])->toBeTrue();
        expect(strlen($result['sanitized_name']))->toBeLessThanOrEqual(20);
    });

    it('rejects files matching blocked patterns', function () {
        config()->set('filemanager.security.blocked_filename_patterns', ['/\.\./', '/\x00/']);
        config()->set('filemanager.security.validate_mime', false);

        $file = Mockery::mock(UploadedFile::class);
        $file->shouldReceive('getClientOriginalName')->andReturn('file..test.pdf');
        $file->shouldReceive('getClientOriginalExtension')->andReturn('pdf');

        Log::shouldReceive('warning')
            ->once()
            ->with('FileManager blocked file upload: malicious filename pattern', Mockery::type('array'));

        $result = $this->service->validateUpload($file);

        expect($result['valid'])->toBeFalse();
        expect($result['error'])->toContain('invalid characters');
    });
});

describe('detectMimeType', function () {
    it('detects JPEG mime type', function () {
        $file = UploadedFile::fake()->image('test.jpg');

        $result = $this->service->detectMimeType($file);

        expect($result)->toBe('image/jpeg');
    });

    it('detects PNG mime type', function () {
        $file = UploadedFile::fake()->image('test.png');

        $result = $this->service->detectMimeType($file);

        expect($result)->toBe('image/png');
    });

    it('detects plain text mime type', function () {
        $file = UploadedFile::fake()->create('test.txt', 100, 'text/plain');

        $result = $this->service->detectMimeType($file);

        // finfo detects by content, not extension
        expect($result)->toBeString();
    });

    it('returns null for non-existent file', function () {
        $file = Mockery::mock(UploadedFile::class);
        $file->shouldReceive('getRealPath')->andReturn('/nonexistent/path');

        $result = $this->service->detectMimeType($file);

        expect($result)->toBeNull();
    });

    it('returns null when getRealPath returns false', function () {
        $file = Mockery::mock(UploadedFile::class);
        $file->shouldReceive('getRealPath')->andReturn(false);

        $result = $this->service->detectMimeType($file);

        expect($result)->toBeNull();
    });
});
