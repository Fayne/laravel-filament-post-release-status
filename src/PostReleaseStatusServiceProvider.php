<?php

namespace Fayne\PostReleaseStatus;

use Fayne\PostReleaseStatus\Http\StartPublicationController;
use Fayne\PostReleaseStatus\Http\PublicationChangeController;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class PostReleaseStatusServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->callAfterResolving(\Fayne\CMS\Support\Modules\ModuleRegistry::class, function ($registry): void {
            $registry->register('post-release-status', 'Publication Status', \Fayne\PostReleaseStatus\PostReleaseStatusPlugin::class);
        });
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'post-release-status');
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'post-release-status');
        FilamentAsset::register([
            Css::make('publication-status', __DIR__ . '/../resources/css/publication-status.css'),
        ], package: 'fayne/laravel-filament-post-release-status');
        Route::middleware(['web', 'auth:' . config('filament-cms.guard', 'admin')])->post('/headless/tenants/{publicationTenant}/publications', StartPublicationController::class)->name('post-release-status.start');
        Route::middleware(['web', 'auth:' . config('filament-cms.guard', 'admin'), 'signed'])->get('/headless/tenants/{publicationTenant}/publication-changes', PublicationChangeController::class)->name('post-release-status.change');
    }
}
