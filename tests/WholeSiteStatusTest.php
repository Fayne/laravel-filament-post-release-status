<?php

use Fayne\CMS\Contracts\ReleaseSnapshotProvider;
use Fayne\CMS\Models\Admin;
use Fayne\CMS\Models\DeploymentRecord;
use Fayne\CMS\Publishing\PublicationLifecycle;
use Fayne\CMS\Publishing\TenantWriteBarrier;
use Fayne\CMS\Support\Authorization\AuthorizationContext;
use Fayne\CMS\Support\Authorization\PermissionCatalog;
use Fayne\CMS\Support\ReleaseSnapshotRegistry;
use Fayne\PostReleaseStatus\Filament\Pages\PublicationHistory;
use Fayne\PostReleaseStatus\Filament\Pages\PublishStatus;
use Fayne\PostReleaseStatus\PostReleaseStatusPlugin;
use Fayne\PostReleaseStatus\PostReleaseStatusServiceProvider;
use Fayne\PostReleaseStatus\Services\ReleaseChanges;
use Fayne\PostReleaseStatus\Services\Scope;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\FilamentTestPanelProvider;
use Tests\TestCase;

final class WholeSiteStatusTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [...array_map(fn ($provider) => $provider === FilamentTestPanelProvider::class ? WholeSiteStatusPanelProvider::class : $provider, parent::getPackageProviders($app)), PostReleaseStatusServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('database.connections.cn', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        $app['config']->set('database.connections.us', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        $app['config']->set('filament-cms.regions', [['id' => 'cn', 'database' => 'cn'], ['id' => 'us', 'database' => 'us']]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['filament-cms.tenancy.enabled' => false, 'filament-cms.site_id' => 'main', 'filament-cms.default_locale' => 'en', 'filament-cms.multilingual_enabled' => true, 'filament-cms.supported_locales' => ['en' => 'English', 'zh_CN' => '中文'],
            'filament-cms.publication_routing' => ['region_mode' => 'host', 'regions' => [['id' => 'cn', 'host' => 'cn.test'], ['id' => 'us', 'host' => 'us.test']], 'language_prefixes' => ['en' => '/en', 'zh_CN' => '/zh']],
            'filament-cms.deployment.frontend_driver' => 'astro', 'filament-cms.deployment.build_webhook_url' => 'https://build.test', 'filament-cms.deployment.webhook_secret' => 'secret']);
        $this->artisan('migrate')->assertExitCode(0);
        PermissionCatalog::seed();
        app(AuthorizationContext::class)->activate(0);
        $admin = Admin::factory()->create();
        $admin->givePermissionTo(['tenant.publication.view', 'tenant.publication.history', 'tenant.content.publish']);
        $this->actingAs($admin, 'admin');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $registry = new ReleaseSnapshotRegistry;
        $registry->register(new class implements ReleaseSnapshotProvider
        {
            public function entityType(): string
            {
                return 'page';
            }
            public function snapshot(DeploymentRecord $release): array
            {
                return [['entity_id' => '1', 'path' => '/about', 'projection' => ['title' => $release->deployment_contents['locale'], 'path' => '/about']]];
            }
        });
        $this->app->instance(ReleaseSnapshotRegistry::class, $registry);
        Http::fake(function ($request) {
            $data = str_ends_with($request->url(), '/capabilities')
                ? ['schema_version' => 2, 'whole_site_v2' => true, 'activation' => 'shared_release_pointer', 'routing_modes' => ['host', 'path']]
                : ['schema_version' => 2, 'accepted' => true, ...array_intersect_key($request->data(), array_flip(['website_id', 'release_id', 'attempt_id', 'manifest_digest']))];
            $body = json_encode($data);
            $timestamp = (string) now()->timestamp;

            return Http::response($body, 200, ['X-Nova-CMS-Timestamp' => $timestamp, 'X-Nova-CMS-Signature' => 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $body, 'secret')]);
        });
    }

    private function publish(): DeploymentRecord
    {
        $release = app(PublicationLifecycle::class)->start(['locale' => 'en', 'region' => 'cn']);
        $manifest = $release->releaseManifest;
        app(PublicationLifecycle::class)->completeWholeSite($release, [...app(\Fayne\CMS\Publishing\WholeSitePublisher::class)->identity($release), 'success' => true, 'activation' => 'activated',
            'targets' => array_map(fn ($target) => ['target_id' => $target['target_id'], 'status' => 'activated'], $manifest->context['targets'])]);

        return $release;
    }

    public function test_all_scopes_compare_duplicate_ids_and_actual_livewire_filter_only_changes_display(): void
    {
        $pending = app(ReleaseChanges::class)->pending(Scope::context(['locale' => 'zh_CN', 'region' => 'us']));
        self::assertCount(4, $pending['added']);
        Livewire::withQueryParams(['locale' => 'zh_CN', 'region' => 'us'])->test(PublishStatus::class)->assertOk()->assertSee('所有区域和语言');
        $this->post(route('post-release-status.start', ['publicationTenant' => 0]), ['locale' => 'zh_CN', 'region' => 'us', 'site_id' => 'forged', 'panel' => 'admin'])->assertRedirect();
        $release = DeploymentRecord::latest('id')->firstOrFail();
        self::assertCount(4, $release->releaseManifest->entries['page']);
        self::assertSame('main', $release->releaseManifest->site_id);
    }

    public function test_default_overview_includes_all_languages_and_details_keep_region_scope_identity(): void
    {
        foreach (['cn', 'us'] as $region) {
            $component = Livewire::withQueryParams(['region' => $region])->test(PublishStatus::class)->assertOk();
            $rows = $component->viewData('changes')['added'];
            self::assertCount(2, $rows);
            self::assertSame(['en', 'zh_CN'], array_column(array_column($rows, 'after'), 'locale'));
            self::assertNotSame($rows[0]['after']['scope_id'], $rows[1]['after']['scope_id']);
            foreach ($rows as $row) {
                self::assertSame($region, $row['after']['region']);
                self::assertContains($row['after']['locale'], array_column($this->getJson($row['details_url'])->assertOk()->json('fields'), 'after'));
            }
        }
    }

    public function test_whole_site_status_hides_both_definition_types_in_every_scope(): void
    {
        $registry = app(ReleaseSnapshotRegistry::class);
        foreach (['global_page_definitions', 'global_menu_definitions'] as $type) {
            $registry->register(new class($type) implements ReleaseSnapshotProvider
            {
                public function __construct(private string $type) {}

                public function entityType(): string
                {
                    return $this->type;
                }

                public function snapshot(DeploymentRecord $release): array
                {
                    return [['entity_id' => 'definitions', 'projection' => ['component' => ['name' => 'Internal definition']]]];
                }
            });
        }
        foreach (['cn', 'us'] as $region) {
            $component = Livewire::withQueryParams(['locale' => 'zh_CN', 'region' => $region])->test(PublishStatus::class)
                ->assertOk()->assertDontSee('页面组件定义')->assertDontSee('菜单组件定义');
            $changes = $component->viewData('changes');
            self::assertCount(1, $changes['added']);
            self::assertSame('page', $changes['added'][0]['after']['entity_type']);
            self::assertSame([], $changes['global']);
        }
    }

    public function test_routing_change_is_full_site_impact_and_history_stays_frozen(): void
    {
        $first = $this->publish();
        config(['filament-cms.publication_routing.regions.1.host' => 'new-us.test']);
        $pending = app(ReleaseChanges::class)->pending(Scope::context([]));
        self::assertTrue($pending['routing_changed']);
        self::assertCount(4, $pending['modified']);
        self::assertNotEmpty($pending['global']);
        $history = app(ReleaseChanges::class)->history($first->releaseManifest);
        self::assertCount(4, $history['added']);
        self::assertFalse($history['routing_changed']);
        config(['filament-cms.publication_routing' => null]);
        Livewire::withQueryParams(['release' => $first->releaseManifest->id])->test(PublicationHistory::class)->assertOk()->assertSee($first->releaseManifest->release_id);
    }

    public function test_capability_error_redirect_is_visible_without_record_or_lock(): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['*' => Http::response([], 404)]);
        $this->from(PublishStatus::getUrl())->post(route('post-release-status.start', ['publicationTenant' => 0]), ['locale' => 'en', 'region' => 'cn', 'panel' => 'admin'])->assertRedirect()->assertSessionHasErrors('publication');
        self::assertSame(0, DeploymentRecord::count());
        self::assertFalse(app(TenantWriteBarrier::class)->isLocked());
        Livewire::test(PublishStatus::class)->assertOk()->assertSee('构建服务');
    }

    public function test_upgrade_baseline_and_legacy_history_remain_accessible(): void
    {
        config(['filament-cms.publication_routing' => null, 'filament-cms.multilingual_enabled' => false, 'filament-cms.deployment.frontend_driver' => 'blade']);
        $old = app(PublicationLifecycle::class)->start(Scope::context([]));
        config(['filament-cms.multilingual_enabled' => true, 'filament-cms.deployment.frontend_driver' => 'astro', 'filament-cms.publication_routing' => ['region_mode' => 'host', 'regions' => [['id' => 'cn', 'host' => 'cn.test'], ['id' => 'us', 'host' => 'us.test']], 'language_prefixes' => ['en' => '/en', 'zh_CN' => '/zh']]]);
        self::assertFalse(app(ReleaseChanges::class)->pending(Scope::context([]))['baseline_available']);
        $first = $this->publish();
        self::assertTrue($first->releaseManifest->context['baseline_unavailable']);
        Livewire::withQueryParams(['release' => $old->releaseManifest->id])->test(PublicationHistory::class)->assertOk()->assertSee($old->releaseManifest->release_id);
        self::assertTrue(app(ReleaseChanges::class)->pending(Scope::context([]))['baseline_available']);
    }
    #[\PHPUnit\Framework\Attributes\DataProvider('obsoleteHistoryFilters')]
    public function test_frozen_history_survives_removed_scope_configuration(bool $wholeSite, string $removed): void
    {
        if ($wholeSite) {
            $release = $this->publish();
        } else {
            config(['filament-cms.publication_routing' => null, 'filament-cms.deployment.frontend_driver' => 'blade']);
            $release = app(PublicationLifecycle::class)->start(Scope::context(['region' => 'us', 'locale' => 'zh_CN']));
        }
        $manifest = $release->releaseManifest;
        config(match ($removed) {
            'region' => ['filament-cms.regions' => [['id' => 'cn', 'database' => 'cn']]],
            'locale' => ['filament-cms.supported_locales' => ['en' => 'English']],
            'disabled' => ['filament-cms.multilingual_enabled' => false],
        });
        Livewire::withQueryParams(['release' => $manifest->id, 'region' => 'us', 'locale' => 'zh_CN'])
            ->test(PublicationHistory::class)->assertOk()->assertSee($manifest->release_id)->assertSee('zh_CN')->assertSee('us');
    }

    public static function obsoleteHistoryFilters(): array
    {
        return [
            'v2 removed region' => [true, 'region'], 'v2 removed locale' => [true, 'locale'], 'v2 disabled locale' => [true, 'disabled'],
            'v1 removed region' => [false, 'region'], 'v1 removed locale' => [false, 'locale'], 'v1 disabled locale' => [false, 'disabled'],
        ];
    }

    public function test_frozen_history_cannot_read_a_foreign_manifest_with_obsolete_filters(): void
    {
        $release = $this->publish();
        \Illuminate\Support\Facades\DB::table('cms_release_manifests')->where('id', $release->releaseManifest->id)->update(['tenant_id' => 99]);
        config(['filament-cms.regions' => [['id' => 'cn', 'database' => 'cn']], 'filament-cms.supported_locales' => ['en' => 'English']]);
        Livewire::withQueryParams(['release' => $release->releaseManifest->id, 'region' => 'us', 'locale' => 'zh_CN'])
            ->test(PublicationHistory::class)->assertNotFound();
    }

}

class WholeSiteStatusPanelProvider extends FilamentTestPanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return parent::panel($panel)->plugin(PostReleaseStatusPlugin::make());
    }
}
