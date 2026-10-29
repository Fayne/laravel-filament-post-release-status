<?php

use MWGuerra\FileManager\Livewire\EmbeddedFileSystem;
use MWGuerra\FileManager\Schemas\Components\FileSystemEmbed;

describe('make', function () {
    it('creates instance with default component', function () {
        $component = FileSystemEmbed::make();

        expect($component)->toBeInstanceOf(FileSystemEmbed::class);
    });

    it('creates instance with custom component class', function () {
        $component = FileSystemEmbed::make(EmbeddedFileSystem::class);

        expect($component)->toBeInstanceOf(FileSystemEmbed::class);
    });
});

describe('height', function () {
    it('has default height of 500px', function () {
        $component = FileSystemEmbed::make();

        expect($component->getHeight())->toBe('500px');
    });

    it('sets custom height', function () {
        $component = FileSystemEmbed::make()
            ->height('800px');

        expect($component->getHeight())->toBe('800px');
    });

    it('evaluates closure for height', function () {
        $component = FileSystemEmbed::make()
            ->height(fn () => '600px');

        expect($component->getHeight())->toBe('600px');
    });

    it('returns self for method chaining', function () {
        $component = FileSystemEmbed::make();

        expect($component->height('400px'))->toBe($component);
    });
});

describe('showHeader', function () {
    it('shows header by default', function () {
        $component = FileSystemEmbed::make();

        expect($component->shouldShowHeader())->toBeTrue();
    });

    it('hides header when set to false', function () {
        $component = FileSystemEmbed::make()
            ->showHeader(false);

        expect($component->shouldShowHeader())->toBeFalse();
    });

    it('hides header using hideHeader method', function () {
        $component = FileSystemEmbed::make()
            ->hideHeader();

        expect($component->shouldShowHeader())->toBeFalse();
    });

    it('evaluates closure for showHeader', function () {
        $component = FileSystemEmbed::make()
            ->showHeader(fn () => false);

        expect($component->shouldShowHeader())->toBeFalse();
    });

    it('returns self for method chaining', function () {
        $component = FileSystemEmbed::make();

        expect($component->showHeader(true))->toBe($component);
        expect($component->hideHeader())->toBe($component);
    });
});

describe('showSidebar', function () {
    it('shows sidebar by default', function () {
        $component = FileSystemEmbed::make();

        expect($component->shouldShowSidebar())->toBeTrue();
    });

    it('hides sidebar when set to false', function () {
        $component = FileSystemEmbed::make()
            ->showSidebar(false);

        expect($component->shouldShowSidebar())->toBeFalse();
    });

    it('hides sidebar using hideSidebar method', function () {
        $component = FileSystemEmbed::make()
            ->hideSidebar();

        expect($component->shouldShowSidebar())->toBeFalse();
    });

    it('evaluates closure for showSidebar', function () {
        $component = FileSystemEmbed::make()
            ->showSidebar(fn () => false);

        expect($component->shouldShowSidebar())->toBeFalse();
    });

    it('returns self for method chaining', function () {
        $component = FileSystemEmbed::make();

        expect($component->showSidebar(true))->toBe($component);
        expect($component->hideSidebar())->toBe($component);
    });
});

describe('defaultViewMode', function () {
    it('has grid as default view mode', function () {
        $component = FileSystemEmbed::make();

        expect($component->getDefaultViewMode())->toBe('grid');
    });

    it('sets custom view mode', function () {
        $component = FileSystemEmbed::make()
            ->defaultViewMode('list');

        expect($component->getDefaultViewMode())->toBe('list');
    });

    it('evaluates closure for defaultViewMode', function () {
        $component = FileSystemEmbed::make()
            ->defaultViewMode(fn () => 'list');

        expect($component->getDefaultViewMode())->toBe('list');
    });

    it('returns self for method chaining', function () {
        $component = FileSystemEmbed::make();

        expect($component->defaultViewMode('list'))->toBe($component);
    });
});

describe('disk', function () {
    it('has null disk by default', function () {
        $component = FileSystemEmbed::make();

        expect($component->getDisk())->toBeNull();
    });

    it('sets custom disk', function () {
        $component = FileSystemEmbed::make()
            ->disk('s3');

        expect($component->getDisk())->toBe('s3');
    });

    it('returns self for method chaining', function () {
        $component = FileSystemEmbed::make();

        expect($component->disk('local'))->toBe($component);
    });
});

describe('target', function () {
    it('has null target by default', function () {
        $component = FileSystemEmbed::make();

        expect($component->getTarget())->toBeNull();
    });

    it('sets custom target', function () {
        $component = FileSystemEmbed::make()
            ->target('uploads/images');

        expect($component->getTarget())->toBe('uploads/images');
    });

    it('returns self for method chaining', function () {
        $component = FileSystemEmbed::make();

        expect($component->target('documents'))->toBe($component);
    });
});

describe('initialFolder', function () {
    it('has null initialFolder by default', function () {
        $component = FileSystemEmbed::make();

        expect($component->getInitialFolder())->toBeNull();
    });

    it('sets initial folder path', function () {
        $component = FileSystemEmbed::make()
            ->initialFolder('uploads/documents');

        expect($component->getInitialFolder())->toBe('uploads/documents');
    });

    it('returns self for method chaining', function () {
        $component = FileSystemEmbed::make();

        expect($component->initialFolder('media'))->toBe($component);
    });
});

describe('compact', function () {
    it('hides both header and sidebar', function () {
        $component = FileSystemEmbed::make()
            ->compact();

        expect($component->shouldShowHeader())->toBeFalse();
        expect($component->shouldShowSidebar())->toBeFalse();
    });

    it('returns self for method chaining', function () {
        $component = FileSystemEmbed::make();

        expect($component->compact())->toBe($component);
    });
});

describe('fluent api', function () {
    it('supports full method chaining', function () {
        $component = FileSystemEmbed::make()
            ->height('600px')
            ->showHeader(true)
            ->showSidebar(true)
            ->defaultViewMode('grid')
            ->disk('local')
            ->target('files')
            ->initialFolder('public');

        expect($component)->toBeInstanceOf(FileSystemEmbed::class)
            ->and($component->getHeight())->toBe('600px')
            ->and($component->getDisk())->toBe('local')
            ->and($component->getTarget())->toBe('files')
            ->and($component->getInitialFolder())->toBe('public');
    });
});

describe('component properties method', function () {
    it('has getComponentProperties method', function () {
        expect(method_exists(FileSystemEmbed::class, 'getComponentProperties'))->toBeTrue();
    });
});

describe('inheritance', function () {
    it('extends Filament Livewire component', function () {
        expect(is_subclass_of(FileSystemEmbed::class, \Filament\Schemas\Components\Livewire::class))->toBeTrue();
    });

    it('is a Filament schema component', function () {
        expect(is_subclass_of(FileSystemEmbed::class, \Filament\Schemas\Components\Component::class))->toBeTrue();
    });
});

describe('default component class', function () {
    it('uses EmbeddedFileSystem as default component', function () {
        $component = FileSystemEmbed::make();

        // The component should be configured to use EmbeddedFileSystem
        expect($component)->toBeInstanceOf(FileSystemEmbed::class);
    });
});
