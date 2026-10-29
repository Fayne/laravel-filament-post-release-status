<?php

namespace MWGuerra\FileManager\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

/**
 * Service for validating file uploads and ensuring security.
 *
 * Note: When using S3/MinIO storage (recommended), most execution risks
 * are already mitigated since files cannot be executed on the object store.
 * This service provides additional validation for defense in depth.
 */
class FileSecurityService
{
    /**
     * Validate an uploaded file for security issues.
     *
     * @return array{valid: bool, error: ?string, sanitized_name: ?string}
     */
    public function validateUpload(UploadedFile $file): array
    {
        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());

        // Check blocked extensions
        if ($this->isBlockedExtension($extension)) {
            Log::warning('FileManager blocked file upload: dangerous extension', [
                'filename' => $originalName,
                'extension' => $extension,
                'ip' => request()->ip(),
                'user_id' => auth()->id(),
            ]);
            return [
                'valid' => false,
                'error' => "File type '.{$extension}' is not allowed for security reasons.",
                'sanitized_name' => null,
            ];
        }

        // Check for double extensions (e.g., file.php.jpg)
        if ($this->hasDoubleExtension($originalName)) {
            Log::warning('FileManager blocked file upload: double extension', [
                'filename' => $originalName,
                'ip' => request()->ip(),
                'user_id' => auth()->id(),
            ]);
            return [
                'valid' => false,
                'error' => 'Files with multiple extensions are not allowed.',
                'sanitized_name' => null,
            ];
        }

        // Validate MIME type using server-side detection (magic bytes)
        if (config('filemanager.security.validate_mime', true)) {
            $detectedMime = $this->detectMimeType($file);

            if (!$this->validateMimeType($extension, $detectedMime)) {
                Log::warning('FileManager blocked file upload: MIME type mismatch', [
                    'filename' => $originalName,
                    'extension' => $extension,
                    'detected_mime' => $detectedMime,
                    'ip' => request()->ip(),
                    'user_id' => auth()->id(),
                ]);
                return [
                    'valid' => false,
                    'error' => 'File content does not match its extension.',
                    'sanitized_name' => null,
                ];
            }

            // Additional check: ensure detected MIME is in allowed list
            $allowedMimes = config('filemanager.upload.allowed_mimes', []);
            if (!empty($allowedMimes) && !in_array($detectedMime, $allowedMimes)) {
                Log::warning('FileManager blocked file upload: disallowed MIME type', [
                    'filename' => $originalName,
                    'detected_mime' => $detectedMime,
                    'ip' => request()->ip(),
                    'user_id' => auth()->id(),
                ]);
                return [
                    'valid' => false,
                    'error' => 'File type is not allowed.',
                    'sanitized_name' => null,
                ];
            }
        }

        // Check blocked filename patterns
        foreach (config('filemanager.security.blocked_filename_patterns', []) as $pattern) {
            if (preg_match($pattern, $originalName)) {
                Log::warning('FileManager blocked file upload: malicious filename pattern', [
                    'filename' => $originalName,
                    'pattern' => $pattern,
                    'ip' => request()->ip(),
                    'user_id' => auth()->id(),
                ]);
                return [
                    'valid' => false,
                    'error' => 'Filename contains invalid characters or patterns.',
                    'sanitized_name' => null,
                ];
            }
        }

        // Sanitize filename
        $sanitizedName = $this->sanitizeFilename($originalName);

        // Check filename length
        $maxLength = config('filemanager.security.max_filename_length', 255);
        if (strlen($sanitizedName) > $maxLength) {
            $sanitizedName = $this->truncateFilename($sanitizedName, $maxLength);
        }

        return [
            'valid' => true,
            'error' => null,
            'sanitized_name' => $sanitizedName,
        ];
    }

    /**
     * Check if an extension is blocked.
     */
    public function isBlockedExtension(string $extension): bool
    {
        $blocked = config('filemanager.security.blocked_extensions', []);
        return in_array(strtolower($extension), array_map('strtolower', $blocked));
    }

    /**
     * Check if filename has a dangerous double extension.
     */
    public function hasDoubleExtension(string $filename): bool
    {
        $dangerousExtensions = config('filemanager.security.blocked_extensions', []);
        $parts = explode('.', $filename);

        if (count($parts) <= 2) {
            return false;
        }

        // Check if any middle part is a dangerous extension
        array_pop($parts); // Remove last extension
        array_shift($parts); // Remove filename

        foreach ($parts as $part) {
            if (in_array(strtolower($part), array_map('strtolower', $dangerousExtensions))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect the MIME type of a file using server-side magic bytes.
     *
     * This uses PHP's finfo extension which reads the file's magic bytes
     * rather than trusting the client-provided MIME type.
     */
    public function detectMimeType(UploadedFile $file): ?string
    {
        $path = $file->getRealPath();

        if (!$path || !file_exists($path)) {
            return null;
        }

        // Use finfo to detect MIME from file content (magic bytes)
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo->file($path);

        if ($detectedMime === false) {
            return null;
        }

        // Some files may be detected as application/octet-stream
        // In these cases, fall back to extension-based detection for certain types
        if ($detectedMime === 'application/octet-stream') {
            $extension = strtolower($file->getClientOriginalExtension());
            $fallbackMimes = [
                // Office documents often detected as octet-stream
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            ];

            if (isset($fallbackMimes[$extension])) {
                // Verify it's actually a ZIP-based format (Office docs are ZIP)
                if ($this->isZipFile($path)) {
                    return $fallbackMimes[$extension];
                }
            }
        }

        return $detectedMime;
    }

    /**
     * Check if a file is a ZIP archive (Office docs are ZIP-based).
     */
    protected function isZipFile(string $path): bool
    {
        $handle = fopen($path, 'rb');
        if (!$handle) {
            return false;
        }

        // ZIP magic bytes: PK (0x50 0x4B)
        $signature = fread($handle, 4);
        fclose($handle);

        return $signature !== false && str_starts_with($signature, "PK");
    }

    /**
     * Validate that MIME type matches the file extension.
     */
    public function validateMimeType(string $extension, ?string $mimeType): bool
    {
        if (!$mimeType) {
            return false;
        }

        $mimeMap = $this->getMimeTypeMap();

        // Get expected MIME types for this extension
        $expectedMimes = $mimeMap[strtolower($extension)] ?? null;

        if ($expectedMimes === null) {
            // Unknown extension - allow if MIME is in allowed list
            $allowedMimes = config('filemanager.upload.allowed_mimes', []);
            return in_array($mimeType, $allowedMimes);
        }

        return in_array($mimeType, (array) $expectedMimes);
    }

    /**
     * Sanitize a filename to remove dangerous characters.
     */
    public function sanitizeFilename(string $filename): string
    {
        if (!config('filemanager.security.sanitize_filenames', true)) {
            return $filename;
        }

        // Get extension
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $name = pathinfo($filename, PATHINFO_FILENAME);

        // Remove or replace dangerous characters
        $name = preg_replace('/[^\p{L}\p{N}\s\-_\.]/u', '', $name);
        $name = preg_replace('/\s+/', '_', $name);
        $name = preg_replace('/_+/', '_', $name);
        $name = trim($name, '_.-');

        // Ensure we have a name
        if (empty($name)) {
            $name = 'file_' . time();
        }

        // Add random prefix if configured
        if (config('filemanager.security.rename_uploads', false)) {
            $name = substr(md5(uniqid()), 0, 8) . '_' . $name;
        }

        return $extension ? "{$name}.{$extension}" : $name;
    }

    /**
     * Truncate a filename while preserving the extension.
     */
    public function truncateFilename(string $filename, int $maxLength): string
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $name = pathinfo($filename, PATHINFO_FILENAME);

        $extensionLength = $extension ? strlen($extension) + 1 : 0;
        $maxNameLength = $maxLength - $extensionLength;

        if ($maxNameLength < 1) {
            $maxNameLength = 1;
        }

        $name = substr($name, 0, $maxNameLength);

        return $extension ? "{$name}.{$extension}" : $name;
    }

    /**
     * Check if a file needs SVG/HTML sanitization.
     */
    public function needsSanitization(string $extension): bool
    {
        $sanitizeExtensions = config('filemanager.security.sanitize_extensions', []);
        return in_array(strtolower($extension), array_map('strtolower', $sanitizeExtensions));
    }

    /**
     * Sanitize SVG content to remove scripts.
     */
    public function sanitizeSvg(string $content): string
    {
        // Remove script tags
        $content = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $content);

        // Remove on* event handlers
        $content = preg_replace('/\s+on\w+\s*=\s*["\'][^"\']*["\']/i', '', $content);
        $content = preg_replace('/\s+on\w+\s*=\s*[^\s>]+/i', '', $content);

        // Remove javascript: URLs
        $content = preg_replace('/href\s*=\s*["\']javascript:[^"\']*["\']/i', 'href="#"', $content);
        $content = preg_replace('/xlink:href\s*=\s*["\']javascript:[^"\']*["\']/i', 'xlink:href="#"', $content);

        // Remove data: URLs that could contain scripts
        $content = preg_replace('/href\s*=\s*["\']data:[^"\']*["\']/i', 'href="#"', $content);

        // Remove foreignObject (can embed HTML)
        $content = preg_replace('/<foreignObject\b[^>]*>(.*?)<\/foreignObject>/is', '', $content);

        // Remove use elements pointing to external resources
        $content = preg_replace('/<use\b[^>]*xlink:href\s*=\s*["\']https?:[^"\']*["\']/i', '<use xlink:href="#"', $content);

        return $content;
    }

    /**
     * Get MIME type to extension mapping.
     */
    protected function getMimeTypeMap(): array
    {
        return [
            // Images
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'webp' => ['image/webp'],
            'svg' => ['image/svg+xml'],
            'ico' => ['image/x-icon', 'image/vnd.microsoft.icon'],
            'bmp' => ['image/bmp'],
            'tiff' => ['image/tiff'],
            'tif' => ['image/tiff'],

            // Videos
            'mp4' => ['video/mp4'],
            'webm' => ['video/webm'],
            'ogg' => ['video/ogg', 'audio/ogg'],
            'ogv' => ['video/ogg'],
            'avi' => ['video/x-msvideo'],
            'mov' => ['video/quicktime'],
            'wmv' => ['video/x-ms-wmv'],
            'mkv' => ['video/x-matroska'],

            // Audio
            'mp3' => ['audio/mpeg'],
            'wav' => ['audio/wav', 'audio/x-wav'],
            'oga' => ['audio/ogg'],
            'flac' => ['audio/flac'],
            'aac' => ['audio/aac'],
            'm4a' => ['audio/mp4', 'audio/x-m4a'],

            // Documents
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'xls' => ['application/vnd.ms-excel'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'ppt' => ['application/vnd.ms-powerpoint'],
            'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
            'txt' => ['text/plain'],
            'csv' => ['text/csv', 'text/plain'],
            'json' => ['application/json'],
            'xml' => ['application/xml', 'text/xml'],

            // Archives
            'zip' => ['application/zip'],
            'rar' => ['application/x-rar-compressed', 'application/vnd.rar'],
            '7z' => ['application/x-7z-compressed'],
            'tar' => ['application/x-tar'],
            'gz' => ['application/gzip'],
        ];
    }
}
