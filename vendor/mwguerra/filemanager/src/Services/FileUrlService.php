<?php

namespace MWGuerra\FileManager\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * Service for generating file URLs with intelligent strategy detection.
 *
 * This service determines the best URL generation strategy based on the disk configuration:
 * - S3-compatible disks: Use temporaryUrl() directly from storage
 * - Public disk (with symlink): Use Storage::url() for direct access
 * - Local/other disks: Generate signed route to streaming controller
 */
class FileUrlService
{
    /**
     * Generate a URL for file preview/streaming.
     *
     * @param string $disk The storage disk name
     * @param string $path The file path within the disk
     * @param string $mode The adapter mode ('storage' or 'database')
     * @param string|null $identifier File identifier (path for storage, ID for database)
     * @param int|null $expirationMinutes URL expiration time in minutes
     * @return string|null The generated URL or null on failure
     */
    public function getPreviewUrl(
        string $disk,
        string $path,
        string $mode = 'storage',
        ?string $identifier = null,
        ?int $expirationMinutes = null
    ): ?string {
        $diskConfig = config("filesystems.disks.{$disk}");

        if (!$diskConfig) {
            return null;
        }

        $expirationMinutes = $expirationMinutes ?? config('filemanager.streaming.url_expiration', 60);
        $driver = $diskConfig['driver'] ?? 'local';
        $strategy = $this->getUrlStrategy($disk);

        // Strategy 1: S3-compatible disks with temporary URLs
        if ($strategy === 'temporary_url') {
            try {
                return Storage::disk($disk)->temporaryUrl(
                    $path,
                    now()->addMinutes($expirationMinutes)
                );
            } catch (\Exception $e) {
                // Fall through to signed route
            }
        }

        // Strategy 2: Public disk with symlink - direct URL access
        if ($strategy === 'public_url') {
            try {
                return Storage::disk($disk)->url($path);
            } catch (\Exception $e) {
                // Fall through to signed route
            }
        }

        // Strategy 3: Generate signed route to streaming controller
        return $this->generateSignedStreamUrl($disk, $path, $mode, $identifier, $expirationMinutes);
    }

    /**
     * Generate a signed URL to the streaming controller.
     */
    protected function generateSignedStreamUrl(
        string $disk,
        string $path,
        string $mode,
        ?string $identifier,
        int $expirationMinutes
    ): string {
        return URL::signedRoute(
            'filemanager.stream',
            [
                'disk' => $disk,
                'path' => $path,
                'mode' => $mode,
                'identifier' => $identifier,
            ],
            now()->addMinutes($expirationMinutes)
        );
    }

    /**
     * Generate a download URL for a file.
     *
     * @param string $disk The storage disk name
     * @param string $path The file path within the disk
     * @param string $mode The adapter mode ('storage' or 'database')
     * @param string|null $identifier File identifier
     * @param string|null $filename Optional filename for the download
     * @param int|null $expirationMinutes URL expiration time in minutes
     * @return string|null The generated download URL or null on failure
     */
    public function getDownloadUrl(
        string $disk,
        string $path,
        string $mode = 'storage',
        ?string $identifier = null,
        ?string $filename = null,
        ?int $expirationMinutes = null
    ): ?string {
        $expirationMinutes = $expirationMinutes ?? config('filemanager.streaming.url_expiration', 60);

        return URL::signedRoute(
            'filemanager.download',
            array_filter([
                'disk' => $disk,
                'path' => $path,
                'mode' => $mode,
                'identifier' => $identifier,
                'filename' => $filename,
            ]),
            now()->addMinutes($expirationMinutes)
        );
    }

    /**
     * Get the URL strategy for a disk.
     *
     * @param string $disk The disk name
     * @return string One of: 'temporary_url', 'public_url', 'signed_route'
     */
    public function getUrlStrategy(string $disk): string
    {
        $diskConfig = config("filesystems.disks.{$disk}");

        if (!$diskConfig) {
            return 'signed_route';
        }

        // Check configured strategy override
        $configuredStrategy = config('filemanager.streaming.url_strategy', 'auto');
        if ($configuredStrategy !== 'auto') {
            return match ($configuredStrategy) {
                'signed_route' => 'signed_route',
                'direct' => 'public_url',
                default => 'signed_route',
            };
        }

        // Check if disk should be forced to use signed routes
        $forcedDisks = config('filemanager.streaming.force_signed_disks', []);
        if (in_array($disk, $forcedDisks)) {
            return 'signed_route';
        }

        $driver = $diskConfig['driver'] ?? 'local';

        // S3-compatible drivers support temporary URLs
        if ($this->supportsTemporaryUrls($driver, $diskConfig)) {
            return 'temporary_url';
        }

        // Check if disk is publicly accessible
        if ($this->isPubliclyAccessible($disk, $driver, $diskConfig)) {
            return 'public_url';
        }

        return 'signed_route';
    }

    /**
     * Check if a disk/driver combination supports temporary URLs.
     */
    protected function supportsTemporaryUrls(string $driver, array $diskConfig): bool
    {
        // S3-compatible drivers (S3, MinIO, DigitalOcean Spaces, etc.)
        if (in_array($driver, ['s3'])) {
            return true;
        }

        // Check for custom temporary URL support flag
        if (isset($diskConfig['temporary_url']) && $diskConfig['temporary_url'] === true) {
            return true;
        }

        return false;
    }

    /**
     * Check if files on this disk are publicly accessible via URL.
     */
    protected function isPubliclyAccessible(string $disk, string $driver, array $diskConfig): bool
    {
        // Check configured public disks
        $publicDisks = config('filemanager.streaming.public_disks', ['public']);
        if (in_array($disk, $publicDisks)) {
            return true;
        }

        // Check for visibility setting (mainly for S3)
        if (isset($diskConfig['visibility']) && $diskConfig['visibility'] === 'public') {
            if ($driver === 's3') {
                return true;
            }
        }

        // Check for explicit public URL flag
        if (isset($diskConfig['public_url']) && $diskConfig['public_url'] === true) {
            return true;
        }

        return false;
    }

    /**
     * Check if a disk requires authentication for access.
     *
     * @param string $disk The disk name
     * @return bool True if authentication is required
     */
    public function requiresAuthentication(string $disk): bool
    {
        $publicAccessDisks = config('filemanager.streaming.public_access_disks', []);

        return !in_array($disk, $publicAccessDisks);
    }

    /**
     * Get detailed information about how a disk's URLs will be generated.
     * Useful for debugging and testing.
     *
     * @param string $disk The disk name
     * @return array Information about the disk's URL strategy
     */
    public function getDiskInfo(string $disk): array
    {
        $diskConfig = config("filesystems.disks.{$disk}");

        if (!$diskConfig) {
            return [
                'exists' => false,
                'strategy' => 'unknown',
                'driver' => null,
                'requires_auth' => true,
            ];
        }

        return [
            'exists' => true,
            'strategy' => $this->getUrlStrategy($disk),
            'driver' => $diskConfig['driver'] ?? 'local',
            'requires_auth' => $this->requiresAuthentication($disk),
            'supports_temporary_urls' => $this->supportsTemporaryUrls(
                $diskConfig['driver'] ?? 'local',
                $diskConfig
            ),
            'is_publicly_accessible' => $this->isPubliclyAccessible(
                $disk,
                $diskConfig['driver'] ?? 'local',
                $diskConfig
            ),
        ];
    }
}
