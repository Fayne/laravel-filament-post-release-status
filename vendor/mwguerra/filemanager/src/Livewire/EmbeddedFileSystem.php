<?php

namespace MWGuerra\FileManager\Livewire;

use MWGuerra\FileManager\Adapters\AdapterFactory;
use MWGuerra\FileManager\Contracts\FileManagerAdapterInterface;

/**
 * Embeddable File System Livewire component (read-only).
 *
 * This component provides a read-only storage-mode file browser
 * as an embeddable Livewire component for use in forms and pages.
 * Only browsing and downloading is allowed - no write operations.
 */
class EmbeddedFileSystem extends EmbeddedFileManager
{
    public function mount(
        string $height = '500px',
        bool $showHeader = true,
        bool $showSidebar = true,
        string $defaultViewMode = 'grid',
        ?string $disk = null,
        ?string $target = null,
        ?string $initialFolder = null,
        string $sidebarRootLabel = 'Root',
        string $sidebarHeading = 'Folders',
        string $breadcrumbsRootLabel = 'Root'
    ): void {
        parent::mount($height, $showHeader, $showSidebar, $defaultViewMode, $disk, $target, $initialFolder, $sidebarRootLabel, $sidebarHeading, $breadcrumbsRootLabel);
    }

    protected function getAdapter(): FileManagerAdapterInterface
    {
        return AdapterFactory::makeStorage(
            disk: $this->disk,
            root: $this->target
        );
    }

    public function getMode(): string
    {
        return 'storage';
    }

    /**
     * Embedded File System is always read-only.
     * Only browsing and downloading is allowed.
     */
    public function isReadOnly(): bool
    {
        return true;
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('filemanager::livewire.embedded-file-system');
    }
}
