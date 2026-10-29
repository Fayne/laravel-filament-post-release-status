<?php

namespace Fayne\PostReleaseStatus;

use Fayne\PostReleaseStatus\Filament\Pages\PublicationHistory;
use Fayne\PostReleaseStatus\Filament\Pages\PublishStatus;
use Filament\Contracts\Plugin;
use Filament\Panel;

final class PostReleaseStatusPlugin implements Plugin
{
    public static function make(): static
    {
        return new self;
    }

    public function getId(): string
    {
        return 'post-release-status';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([PublishStatus::class, PublicationHistory::class]);
    }

    public function boot(Panel $panel): void {}
}
