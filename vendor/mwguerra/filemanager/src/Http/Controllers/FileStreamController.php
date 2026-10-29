<?php

namespace MWGuerra\FileManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use MWGuerra\FileManager\Adapters\AdapterFactory;
use MWGuerra\FileManager\Services\AuthorizationService;
use MWGuerra\FileManager\Services\FileUrlService;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controller for streaming files from storage.
 *
 * This controller handles secure file streaming for disks that don't have
 * direct web access (like the 'local' disk). It validates signed URLs
 * and checks authorization before streaming file contents.
 */
class FileStreamController extends Controller
{
    protected FileUrlService $fileUrlService;
    protected AuthorizationService $authorizationService;

    public function __construct(FileUrlService $fileUrlService, AuthorizationService $authorizationService)
    {
        $this->fileUrlService = $fileUrlService;
        $this->authorizationService = $authorizationService;
    }

    /**
     * Stream a file for inline viewing (preview).
     *
     * Query parameters (from signed URL):
     * - disk: The storage disk name
     * - path: The file path within the disk
     * - mode: 'storage' or 'database'
     * - identifier: File identifier (path for storage, ID for database)
     */
    public function stream(Request $request): StreamedResponse
    {
        return $this->serveFile($request, 'inline');
    }

    /**
     * Stream a file for download (attachment).
     *
     * Additional query parameter:
     * - filename: Optional custom filename for the download
     */
    public function download(Request $request): StreamedResponse
    {
        return $this->serveFile($request, 'attachment');
    }

    /**
     * Serve a file with the specified disposition.
     */
    protected function serveFile(Request $request, string $disposition): StreamedResponse
    {
        // Validate signed URL
        if (!$request->hasValidSignature()) {
            Log::warning('FileManager: Invalid or expired stream URL', [
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'user_id' => auth()->id(),
            ]);
            abort(403, 'Invalid or expired URL');
        }

        $disk = $request->query('disk');
        $path = $request->query('path');
        $mode = $request->query('mode', 'storage');
        $identifier = $request->query('identifier');
        $customFilename = $request->query('filename');

        // Validate required parameters
        if (!$disk || !$path) {
            abort(400, 'Missing required parameters');
        }

        // Check if disk requires authentication
        if ($this->fileUrlService->requiresAuthentication($disk)) {
            if (!auth()->check()) {
                abort(403, 'Authentication required');
            }

            // Authorization check based on mode
            if (!$this->checkAuthorization($mode, $disk, $identifier)) {
                Log::warning('FileManager: Unauthorized file access attempt', [
                    'disk' => $disk,
                    'path' => $path,
                    'mode' => $mode,
                    'identifier' => $identifier,
                    'ip' => $request->ip(),
                    'user_id' => auth()->id(),
                ]);
                abort(403, 'Unauthorized');
            }
        }

        // Validate disk exists
        $diskConfig = config("filesystems.disks.{$disk}");
        if (!$diskConfig) {
            abort(404, 'Storage disk not found');
        }

        $storage = Storage::disk($disk);

        // Check file exists
        if (!$storage->exists($path)) {
            Log::info('FileManager: File not found for streaming', [
                'disk' => $disk,
                'path' => $path,
            ]);
            abort(404, 'File not found');
        }

        // Get file info
        $mimeType = $storage->mimeType($path) ?? 'application/octet-stream';
        $size = $storage->size($path);
        $filename = $customFilename ?? basename($path);

        // Sanitize filename for Content-Disposition header
        $safeFilename = $this->sanitizeFilename($filename);

        Log::debug('FileManager: Streaming file', [
            'disk' => $disk,
            'path' => $path,
            'mime_type' => $mimeType,
            'size' => $size,
            'disposition' => $disposition,
            'user_id' => auth()->id(),
        ]);

        return new StreamedResponse(function () use ($storage, $path) {
            $stream = $storage->readStream($path);

            if ($stream === null) {
                return;
            }

            // Stream the file in chunks
            while (!feof($stream)) {
                echo fread($stream, 8192);
                flush();
            }

            fclose($stream);
        }, 200, $this->getStreamHeaders($mimeType, $size, $safeFilename, $disposition));
    }

    /**
     * Check authorization for file access.
     */
    protected function checkAuthorization(string $mode, string $disk, ?string $identifier): bool
    {
        // For database mode, check specific item authorization
        if ($mode === 'database' && $identifier) {
            $adapter = AdapterFactory::makeDatabase(disk: $disk);
            $item = $adapter->getItem($identifier);

            if (!$item) {
                return false;
            }

            // Check authorization with the item (user is resolved from auth())
            return $this->authorizationService->canView(null, $item);
        }

        // For storage mode, check general view permission
        return $this->authorizationService->canViewAny();
    }

    /**
     * Get headers for the streamed response.
     */
    protected function getStreamHeaders(string $mimeType, int $size, string $filename, string $disposition): array
    {
        $headers = [
            'Content-Type' => $mimeType,
            'Content-Length' => $size,
            'Cache-Control' => 'private, max-age=3600',
            'Accept-Ranges' => 'bytes',
        ];

        // Add Content-Disposition header
        if ($disposition === 'attachment') {
            $headers['Content-Disposition'] = "attachment; filename=\"{$filename}\"";
        } else {
            $headers['Content-Disposition'] = "inline; filename=\"{$filename}\"";
        }

        // Add security headers for inline content
        if ($disposition === 'inline') {
            // Prevent content sniffing
            $headers['X-Content-Type-Options'] = 'nosniff';
        }

        return $headers;
    }

    /**
     * Sanitize a filename for use in Content-Disposition header.
     */
    protected function sanitizeFilename(string $filename): string
    {
        // Remove any path components
        $filename = basename($filename);

        // Remove or replace potentially dangerous characters
        $filename = preg_replace('/[^\w\-\.\s]/', '_', $filename);

        // Limit length
        if (strlen($filename) > 255) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $maxNameLength = 255 - strlen($ext) - 1;
            $filename = substr($name, 0, $maxNameLength) . '.' . $ext;
        }

        return $filename;
    }
}
