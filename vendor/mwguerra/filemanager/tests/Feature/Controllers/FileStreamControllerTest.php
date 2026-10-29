<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use MWGuerra\FileManager\Services\FileUrlService;

beforeEach(function () {
    // Set app key for signed URLs
    config()->set('app.key', 'base64:' . base64_encode(random_bytes(32)));

    // Set default streaming config
    config()->set('filemanager.streaming.url_strategy', 'auto');
    config()->set('filemanager.streaming.url_expiration', 60);
    config()->set('filemanager.streaming.route_prefix', 'filemanager');
    config()->set('filemanager.streaming.middleware', ['web']);
    config()->set('filemanager.streaming.force_signed_disks', []);
    config()->set('filemanager.streaming.public_disks', ['public']);
    config()->set('filemanager.streaming.public_access_disks', []);

    // Set up authorization config
    config()->set('filemanager.authorization.enabled', true);
    config()->set('filemanager.authorization.permissions.view', null);
    config()->set('filemanager.authorization.permissions.view_any', null);

    // Create a test file
    Storage::fake('local');
    Storage::disk('local')->put('test-file.txt', 'Hello, World!');
    Storage::disk('local')->put('test-image.jpg', 'fake image content');
});

describe('stream endpoint', function () {
    it('returns 403 for invalid signature', function () {
        $response = $this->get('/filemanager/stream?disk=local&path=test-file.txt');

        $response->assertStatus(403);
    });

    it('returns 400 for missing required parameters', function () {
        $signedUrl = URL::signedRoute('filemanager.stream', [
            'disk' => 'local',
            // missing 'path'
        ], now()->addMinutes(60));

        $response = $this->get($signedUrl);

        $response->assertStatus(400);
    });

    it('returns 403 for unauthenticated user on protected disk', function () {
        $signedUrl = URL::signedRoute('filemanager.stream', [
            'disk' => 'local',
            'path' => 'test-file.txt',
        ], now()->addMinutes(60));

        $response = $this->get($signedUrl);

        $response->assertStatus(403);
    });

    it('returns 404 for nonexistent file', function () {
        // Create authenticated user
        $user = new class extends \Illuminate\Foundation\Auth\User {
            protected $fillable = ['id'];
        };
        $user->id = 1;
        $this->actingAs($user);

        $signedUrl = URL::signedRoute('filemanager.stream', [
            'disk' => 'local',
            'path' => 'nonexistent.txt',
        ], now()->addMinutes(60));

        $response = $this->get($signedUrl);

        $response->assertStatus(404);
    });

    it('streams file for authenticated user with valid signed URL', function () {
        // Create authenticated user
        $user = new class extends \Illuminate\Foundation\Auth\User {
            protected $fillable = ['id'];
        };
        $user->id = 1;
        $this->actingAs($user);

        $signedUrl = URL::signedRoute('filemanager.stream', [
            'disk' => 'local',
            'path' => 'test-file.txt',
            'mode' => 'storage',
        ], now()->addMinutes(60));

        $response = $this->get($signedUrl);

        $response->assertStatus(200);
        // Content-Type may include charset suffix (e.g., 'text/plain; charset=utf-8')
        expect($response->headers->get('Content-Type'))->toStartWith('text/plain');
        $response->assertHeader('Content-Disposition', 'inline; filename="test-file.txt"');
    });

    it('allows access to public_access_disks without authentication', function () {
        config()->set('filemanager.streaming.public_access_disks', ['local']);

        $signedUrl = URL::signedRoute('filemanager.stream', [
            'disk' => 'local',
            'path' => 'test-file.txt',
        ], now()->addMinutes(60));

        $response = $this->get($signedUrl);

        $response->assertStatus(200);
    });

    it('returns 403 for expired signed URL', function () {
        $user = new class extends \Illuminate\Foundation\Auth\User {
            protected $fillable = ['id'];
        };
        $user->id = 1;
        $this->actingAs($user);

        // Create URL that expired 1 minute ago
        $expiredUrl = URL::signedRoute('filemanager.stream', [
            'disk' => 'local',
            'path' => 'test-file.txt',
        ], now()->subMinute());

        $response = $this->get($expiredUrl);

        $response->assertStatus(403);
    });
});

describe('download endpoint', function () {
    it('sets Content-Disposition to attachment', function () {
        $user = new class extends \Illuminate\Foundation\Auth\User {
            protected $fillable = ['id'];
        };
        $user->id = 1;
        $this->actingAs($user);

        $signedUrl = URL::signedRoute('filemanager.download', [
            'disk' => 'local',
            'path' => 'test-file.txt',
            'mode' => 'storage',
        ], now()->addMinutes(60));

        $response = $this->get($signedUrl);

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename="test-file.txt"');
    });

    it('uses custom filename when provided', function () {
        $user = new class extends \Illuminate\Foundation\Auth\User {
            protected $fillable = ['id'];
        };
        $user->id = 1;
        $this->actingAs($user);

        $signedUrl = URL::signedRoute('filemanager.download', [
            'disk' => 'local',
            'path' => 'test-file.txt',
            'filename' => 'custom-name.txt',
        ], now()->addMinutes(60));

        $response = $this->get($signedUrl);

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename="custom-name.txt"');
    });
});

describe('security', function () {
    it('includes X-Content-Type-Options header for inline content', function () {
        $user = new class extends \Illuminate\Foundation\Auth\User {
            protected $fillable = ['id'];
        };
        $user->id = 1;
        $this->actingAs($user);

        $signedUrl = URL::signedRoute('filemanager.stream', [
            'disk' => 'local',
            'path' => 'test-file.txt',
        ], now()->addMinutes(60));

        $response = $this->get($signedUrl);

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    });

    it('sanitizes dangerous characters in filename', function () {
        Storage::disk('local')->put('test<script>.txt', 'content');

        $user = new class extends \Illuminate\Foundation\Auth\User {
            protected $fillable = ['id'];
        };
        $user->id = 1;
        $this->actingAs($user);

        $signedUrl = URL::signedRoute('filemanager.download', [
            'disk' => 'local',
            'path' => 'test<script>.txt',
            'filename' => 'test<script>alert(1)</script>.txt',
        ], now()->addMinutes(60));

        $response = $this->get($signedUrl);

        // Filename should be sanitized
        $contentDisposition = $response->headers->get('Content-Disposition');
        expect($contentDisposition)->not->toContain('<script>');
    });
});
