<?php

namespace MWGuerra\FileManager\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use MWGuerra\FileManager\Adapters\AdapterFactory;
use MWGuerra\FileManager\Contracts\FileManagerAdapterInterface;
use MWGuerra\FileManager\Contracts\FileManagerItemInterface;

class FileSystemListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'filesystem:list
                            {path? : The folder path to list (defaults to root)}
                            {--disk= : The storage disk to use (defaults to FILEMANAGER_DISK or FILESYSTEM_DISK)}
                            {--type= : Filter by type (folder, file, or all)}
                            {--no-recursive : Disable recursive listing}
                            {--format=table : Output format (table, json, csv)}
                            {--show-hidden : Show hidden files and folders}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List files and folders directly from storage disk (recursive by default)';

    /**
     * Counter for items.
     */
    protected int $folderCount = 0;
    protected int $fileCount = 0;
    protected int $totalSize = 0;

    /**
     * Collected items for output.
     */
    protected array $items = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $path = $this->argument('path') ?? '';
        $type = $this->option('type');
        $recursive = !$this->option('no-recursive');
        $format = $this->option('format');
        $showHidden = $this->option('show-hidden');

        $disk = $this->option('disk')
            ?? config('filemanager.storage_mode.disk')
            ?? config('filemanager.upload.disk')
            ?? env('FILEMANAGER_DISK')
            ?? env('FILESYSTEM_DISK', 'public');

        // Validate type filter
        if ($type && !in_array($type, ['folder', 'file', 'all'])) {
            $this->error("Invalid type '{$type}'. Use 'folder', 'file', or 'all'.");
            return self::FAILURE;
        }

        // Validate format
        if (!in_array($format, ['table', 'json', 'csv'])) {
            $this->error("Invalid format '{$format}'. Use 'table', 'json', or 'csv'.");
            return self::FAILURE;
        }

        // Validate disk exists (use directories() which works with S3-compatible storage)
        try {
            Storage::disk($disk)->directories('');
        } catch (\Exception $e) {
            $this->error("Disk '{$disk}' is not configured or accessible.");
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        // Get the adapter (always storage mode)
        try {
            $adapter = AdapterFactory::makeStorage(disk: $disk, root: '');
        } catch (\Exception $e) {
            $this->error("Failed to initialize adapter: " . $e->getMessage());
            return self::FAILURE;
        }

        // Show header for table format
        if ($format === 'table') {
            $this->info("FileSystem List: {$disk}");
            $this->info(str_repeat('=', 20 + strlen($disk)));
            $this->newLine();
            $this->info("Path: " . ($path ?: '(root)'));
            if ($type) {
                $this->info("Type filter: {$type}");
            }
            if (!$recursive) {
                $this->info("Recursive: No");
            }
            $this->newLine();
        }

        // List items
        try {
            if ($recursive) {
                $this->listRecursive($adapter, $path ?: null, $type, $showHidden, 0);
            } else {
                $this->listItems($adapter, $path ?: null, $type, $showHidden);
            }
        } catch (\Exception $e) {
            $this->error("Failed to list items: " . $e->getMessage());
            return self::FAILURE;
        }

        // Output based on format
        $this->outputResults($format);

        return self::SUCCESS;
    }

    /**
     * List items in a single directory.
     */
    protected function listItems(
        FileManagerAdapterInterface $adapter,
        ?string $path,
        ?string $type,
        bool $showHidden
    ): void {
        $items = $adapter->getItems($path);

        foreach ($items as $item) {
            if (!$this->shouldIncludeItem($item, $type, $showHidden)) {
                continue;
            }

            $this->addItem($item, 0);
        }
    }

    /**
     * List items recursively.
     */
    protected function listRecursive(
        FileManagerAdapterInterface $adapter,
        ?string $path,
        ?string $type,
        bool $showHidden,
        int $depth
    ): void {
        $items = $adapter->getItems($path);

        foreach ($items as $item) {
            if (!$this->shouldIncludeItem($item, $type, $showHidden)) {
                continue;
            }

            $this->addItem($item, $depth);

            // Recurse into folders
            if ($item->isFolder()) {
                $this->listRecursive($adapter, $item->getIdentifier(), $type, $showHidden, $depth + 1);
            }
        }
    }

    /**
     * Check if an item should be included based on filters.
     */
    protected function shouldIncludeItem(
        FileManagerItemInterface $item,
        ?string $type,
        bool $showHidden
    ): bool {
        // Check hidden
        if (!$showHidden && str_starts_with($item->getName(), '.')) {
            return false;
        }

        // Check type filter
        if ($type === 'folder' && !$item->isFolder()) {
            return false;
        }

        if ($type === 'file' && !$item->isFile()) {
            return false;
        }

        return true;
    }

    /**
     * Add an item to the collection.
     */
    protected function addItem(FileManagerItemInterface $item, int $depth): void
    {
        $size = $item->getSize();
        $lastModified = $item->getLastModified();

        $this->items[] = [
            'depth' => $depth,
            'type' => $item->isFolder() ? 'folder' : 'file',
            'name' => $item->getName(),
            'id' => $item->getIdentifier(),
            'path' => $item->getPath(),
            'size' => $size,
            'size_formatted' => $size ? $this->formatBytes($size) : '-',
            'modified' => $lastModified ? date('Y-m-d H:i:s', $lastModified) : '-',
        ];

        if ($item->isFolder()) {
            $this->folderCount++;
        } else {
            $this->fileCount++;
            $this->totalSize += $size ?? 0;
        }
    }

    /**
     * Output results in the specified format.
     */
    protected function outputResults(string $format): void
    {
        if (empty($this->items)) {
            if ($format === 'table') {
                $this->info('No items found.');
            } elseif ($format === 'json') {
                $this->line(json_encode([], JSON_PRETTY_PRINT));
            } else {
                // CSV - just headers
                $this->line('type,name,path,size,modified');
            }
            return;
        }

        switch ($format) {
            case 'json':
                $this->outputJson();
                break;

            case 'csv':
                $this->outputCsv();
                break;

            default:
                $this->outputTable();
                break;
        }
    }

    /**
     * Output as table.
     */
    protected function outputTable(): void
    {
        $rows = [];

        foreach ($this->items as $item) {
            $indent = str_repeat('  ', $item['depth']);
            $icon = $item['type'] === 'folder' ? '[D]' : '[F]';

            $rows[] = [
                $icon,
                $indent . $item['name'],
                $item['size_formatted'],
                $item['modified'],
            ];
        }

        $this->table(
            ['Type', 'Name', 'Size', 'Modified'],
            $rows
        );

        $this->newLine();
        $this->info("Summary:");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Folders', $this->folderCount],
                ['Files', $this->fileCount],
                ['Total items', $this->folderCount + $this->fileCount],
                ['Total size', $this->formatBytes($this->totalSize)],
            ]
        );
    }

    /**
     * Output as JSON.
     */
    protected function outputJson(): void
    {
        $output = [
            'items' => array_map(function ($item) {
                unset($item['depth']);
                unset($item['size_formatted']);
                return $item;
            }, $this->items),
            'summary' => [
                'folders' => $this->folderCount,
                'files' => $this->fileCount,
                'total_items' => $this->folderCount + $this->fileCount,
                'total_size' => $this->totalSize,
                'total_size_formatted' => $this->formatBytes($this->totalSize),
            ],
        ];

        $this->line(json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Output as CSV.
     */
    protected function outputCsv(): void
    {
        // Header
        $this->line('type,name,path,size,modified');

        // Data rows
        foreach ($this->items as $item) {
            $this->line(sprintf(
                '%s,"%s","%s",%s,"%s"',
                $item['type'],
                str_replace('"', '""', $item['name']),
                str_replace('"', '""', $item['path'] ?? ''),
                $item['size'] ?? 0,
                $item['modified']
            ));
        }
    }

    /**
     * Format bytes to human-readable string.
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $unitIndex = 0;
        $size = (float) $bytes;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }
}
