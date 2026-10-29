<?php

use MWGuerra\FileManager\Services\FileUrlService;

beforeEach(function () {
    $this->service = new FileUrlService();

    // Set default streaming config
    config()->set('filemanager.streaming.url_strategy', 'auto');
    config()->set('filemanager.streaming.url_expiration', 60);
    config()->set('filemanager.streaming.route_prefix', 'filemanager');
    config()->set('filemanager.streaming.middleware', ['web']);
    config()->set('filemanager.streaming.force_signed_disks', []);
    config()->set('filemanager.streaming.public_disks', ['public']);
    config()->set('filemanager.streaming.public_access_disks', []);

    // Set up test disk configurations
    config()->set('filesystems.disks.public', [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'url' => env('APP_URL') . '/storage',
        'visibility' => 'public',
    ]);

    config()->set('filesystems.disks.local', [
        'driver' => 'local',
        'root' => storage_path('app'),
    ]);

    config()->set('filesystems.disks.s3', [
        'driver' => 's3',
        'key' => 'test-key',
        'secret' => 'test-secret',
        'region' => 'us-east-1',
        'bucket' => 'test-bucket',
    ]);

    config()->set('filesystems.disks.minio', [
        'driver' => 's3',
        'key' => 'test-key',
        'secret' => 'test-secret',
        'region' => 'us-east-1',
        'bucket' => 'test-bucket',
        'endpoint' => 'http://localhost:9000',
    ]);
});

describe('getUrlStrategy', function () {
    it('returns public_url for public disk', function () {
        expect($this->service->getUrlStrategy('public'))->toBe('public_url');
    });

    it('returns signed_route for local disk', function () {
        expect($this->service->getUrlStrategy('local'))->toBe('signed_route');
    });

    it('returns temporary_url for s3 disk', function () {
        expect($this->service->getUrlStrategy('s3'))->toBe('temporary_url');
    });

    it('returns temporary_url for minio disk', function () {
        expect($this->service->getUrlStrategy('minio'))->toBe('temporary_url');
    });

    it('returns signed_route for unknown disk', function () {
        expect($this->service->getUrlStrategy('nonexistent'))->toBe('signed_route');
    });

    it('respects force_signed_disks config', function () {
        config()->set('filemanager.streaming.force_signed_disks', ['public']);
        expect($this->service->getUrlStrategy('public'))->toBe('signed_route');
    });

    it('respects url_strategy config when set to signed_route', function () {
        config()->set('filemanager.streaming.url_strategy', 'signed_route');
        expect($this->service->getUrlStrategy('public'))->toBe('signed_route');
        expect($this->service->getUrlStrategy('s3'))->toBe('signed_route');
    });

    it('respects url_strategy config when set to direct', function () {
        config()->set('filemanager.streaming.url_strategy', 'direct');
        expect($this->service->getUrlStrategy('public'))->toBe('public_url');
        expect($this->service->getUrlStrategy('local'))->toBe('public_url');
    });
});

describe('requiresAuthentication', function () {
    it('returns true for disks not in public_access_disks', function () {
        expect($this->service->requiresAuthentication('local'))->toBeTrue();
        expect($this->service->requiresAuthentication('public'))->toBeTrue();
        expect($this->service->requiresAuthentication('s3'))->toBeTrue();
    });

    it('returns false for disks in public_access_disks', function () {
        config()->set('filemanager.streaming.public_access_disks', ['public', 'assets']);
        expect($this->service->requiresAuthentication('public'))->toBeFalse();
        expect($this->service->requiresAuthentication('assets'))->toBeFalse();
        expect($this->service->requiresAuthentication('local'))->toBeTrue();
    });
});

describe('getDiskInfo', function () {
    it('returns correct info for public disk', function () {
        $info = $this->service->getDiskInfo('public');

        expect($info['exists'])->toBeTrue();
        expect($info['strategy'])->toBe('public_url');
        expect($info['driver'])->toBe('local');
        expect($info['requires_auth'])->toBeTrue();
        expect($info['is_publicly_accessible'])->toBeTrue();
    });

    it('returns correct info for local disk', function () {
        $info = $this->service->getDiskInfo('local');

        expect($info['exists'])->toBeTrue();
        expect($info['strategy'])->toBe('signed_route');
        expect($info['driver'])->toBe('local');
        expect($info['requires_auth'])->toBeTrue();
        expect($info['is_publicly_accessible'])->toBeFalse();
    });

    it('returns correct info for s3 disk', function () {
        $info = $this->service->getDiskInfo('s3');

        expect($info['exists'])->toBeTrue();
        expect($info['strategy'])->toBe('temporary_url');
        expect($info['driver'])->toBe('s3');
        expect($info['supports_temporary_urls'])->toBeTrue();
    });

    it('returns correct info for nonexistent disk', function () {
        $info = $this->service->getDiskInfo('nonexistent');

        expect($info['exists'])->toBeFalse();
        expect($info['strategy'])->toBe('unknown');
        expect($info['driver'])->toBeNull();
    });
});

describe('getPreviewUrl', function () {
    it('returns null for nonexistent disk', function () {
        $url = $this->service->getPreviewUrl('nonexistent', 'test.jpg');
        expect($url)->toBeNull();
    });
});

describe('custom disk configurations', function () {
    it('recognizes temporary_url flag in disk config', function () {
        config()->set('filesystems.disks.custom', [
            'driver' => 'local',
            'root' => storage_path('app/custom'),
            'temporary_url' => true,
        ]);

        $info = $this->service->getDiskInfo('custom');
        expect($info['supports_temporary_urls'])->toBeTrue();
        expect($info['strategy'])->toBe('temporary_url');
    });

    it('recognizes public_url flag in disk config', function () {
        config()->set('filesystems.disks.custom', [
            'driver' => 'local',
            'root' => storage_path('app/custom'),
            'public_url' => true,
        ]);

        $info = $this->service->getDiskInfo('custom');
        expect($info['is_publicly_accessible'])->toBeTrue();
    });

    it('respects public_disks config for custom disks', function () {
        config()->set('filesystems.disks.cdn', [
            'driver' => 'local',
            'root' => storage_path('app/cdn'),
        ]);
        config()->set('filemanager.streaming.public_disks', ['public', 'cdn']);

        $info = $this->service->getDiskInfo('cdn');
        expect($info['is_publicly_accessible'])->toBeTrue();
        expect($info['strategy'])->toBe('public_url');
    });
});
