<?php

namespace Tests;

use Fayne\CMS\CMSServiceProvider;
use Fayne\CMS\Filament\CmsPlugin;
use Fayne\CMS\Models\Admin;
use Fayne\QrCode\QrcodeServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Panel;
use Filament\PanelProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Permission\PermissionServiceProvider;

abstract class TestCase extends Orchestra
{
    protected $enablesPackageDiscoveries = true;

    protected function getPackageProviders($app): array
    {
        return [FilamentServiceProvider::class, CMSServiceProvider::class, QrcodeServiceProvider::class, PermissionServiceProvider::class, FilamentTestPanelProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:5qJq8O4L7PJkq4+9eX6xqz5VxA8w3iH+0mZkR4j7r7U=');
        $app['config']->set('app.locale', 'zh_CN');
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]);
        $app['config']->set('auth.defaults.guard', 'admin');
        $app['config']->set('auth.guards.admin', ['driver' => 'session', 'provider' => 'admins']);
        $app['config']->set('auth.providers.admins', ['driver' => 'eloquent', 'model' => Admin::class]);
        $app['config']->set('permission.testing', true);
    }
}

class FilamentTestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel->default()->id('admin')->path('admin')->authGuard('admin')->login()->middleware(['web'])->plugins([CmsPlugin::make()]);
    }
}
