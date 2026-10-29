<?php

use Fayne\CMS\Contracts\ReleaseSnapshotProvider;
use Fayne\CMS\Models\Admin;
use Fayne\CMS\Models\DeploymentRecord;
use Fayne\CMS\Models\ReleaseManifest;
use Fayne\CMS\Publishing\PublicationLifecycle;
use Fayne\CMS\Support\Authorization\AuthorizationContext;
use Fayne\CMS\Support\Authorization\PermissionCatalog;
use Fayne\CMS\Support\ReleaseSnapshotRegistry;
use Fayne\PostReleaseStatus\Filament\Pages\PublishStatus;
use Fayne\PostReleaseStatus\PostReleaseStatusPlugin;
use Fayne\PostReleaseStatus\PostReleaseStatusServiceProvider;
use Fayne\PostReleaseStatus\Services\ReleaseChanges;
use Fayne\PostReleaseStatus\Services\Scope;
use Filament\Panel;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\FilamentTestPanelProvider;
use Tests\TestCase;

final class ReleaseChangesTest extends TestCase
{
    private array $rows = [];

    protected function getPackageProviders($app): array
    {
        return [...array_map(fn ($provider) => $provider === FilamentTestPanelProvider::class ? StatusTestPanelProvider::class : $provider, parent::getPackageProviders($app)), PostReleaseStatusServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['filament-cms.tenancy.enabled' => false, 'filament-cms.default_locale' => 'en', 'filament-cms.supported_locales' => ['en' => 'English'], 'filament-cms.multilingual_enabled' => false, 'filament-cms.deployment.frontend_driver' => 'astro', 'filament-cms.deployment.build_webhook_url' => 'https://build.test', 'filament-cms.deployment.webhook_secret' => 'secret']);
        $this->artisan('migrate')->assertExitCode(0);
        PermissionCatalog::seed();
        $admin = Admin::factory()->create();
        app(AuthorizationContext::class)->activate(0);
        $admin->givePermissionTo(['tenant.publication.view', 'tenant.publication.history', 'tenant.content.publish']);
        $this->actingAs($admin, 'admin');
        Http::fake(['*' => Http::response([], 202)]);
        $registry = new ReleaseSnapshotRegistry;
        $owner = $this;
        $registry->register(new class($owner) implements ReleaseSnapshotProvider
        {
            public function __construct(private $owner) {}

            public function entityType(): string
            {
                return 'test';
            }

            public function snapshot(DeploymentRecord $release): array
            {
                return $this->owner->snapshot();
            }
        });
        $this->app->instance(ReleaseSnapshotRegistry::class, $registry);
        $this->rows = [['entity_id' => '1', 'projection' => ['title' => '<script>alert(1)</script>'], 'path' => '/old']];
    }

    public function snapshot(): array
    {
        return $this->rows;
    }

    private function context(string $locale = 'en'): array
    {
        return ['locale' => $locale, 'region' => '', 'site_id' => ''];
    }

    private function release(bool $success = true): DeploymentRecord
    {
        $release = app(PublicationLifecycle::class)->start($this->context());
        app(PublicationLifecycle::class)->complete($release, $release->releaseManifest->attempt_id, $success);

        return $release;
    }

    public function test_baseline_scope_failed_missing_and_deletion(): void
    {
        $service = app(ReleaseChanges::class);
        self::assertCount(1, $service->pending($this->context())['added']);
        $success = $this->release();
        self::assertSame([], $service->pending($this->context())['added']);
        $this->rows = [];
        $failed = $this->release(false);
        self::assertSame($success->releaseManifest->id, $service->pending($this->context())['baseline']->id);
        self::assertCount(1, $service->pending($this->context())['deleted']);
        self::assertCount(1, $service->history($failed->releaseManifest)['deleted']);
        $this->rows = [['entity_id' => 'unrelated', 'projection' => ['new' => 'content']]];
        self::assertCount(1, $service->history($failed->releaseManifest)['deleted']);
        config(['filament-cms.supported_locales' => ['en' => 'English', 'zh' => '中文'], 'filament-cms.multilingual_enabled' => true]);
        self::assertNull($service->pending($this->context('zh'))['baseline']);
        $success->releaseManifest->delete();
        self::assertFalse($service->pending($this->context())['baseline_available']);
    }

    public function test_missing_prior_success_manifest_does_not_invent_first_history(): void
    {
        $first = $this->release();
        $first->releaseManifest->delete();
        $second = $this->release();
        self::assertTrue($second->fresh()->deployment_contents['baseline_unavailable']);
        $history = app(ReleaseChanges::class)->history($second->releaseManifest);
        self::assertFalse($history['baseline_available']);
        self::assertSame([], $history['added']);
    }

    public function test_frozen_unavailable_baseline_survives_mutable_deployment_changes(): void
    {
        $first = $this->release();
        $first->releaseManifest->delete();
        $second = $this->release();
        $manifest = $second->releaseManifest;
        self::assertTrue($manifest->context['baseline_unavailable']);
        $second->forceFill(['deployment_contents' => []])->save();
        $manifest->setRelation('deploymentRecord', null);
        self::assertFalse(app(ReleaseChanges::class)->history($manifest)['baseline_available']);
    }

    public function test_null_owned_legacy_baseline_is_never_accepted_in_scope_zero(): void
    {
        $first = $this->release();
        $second = $this->release();
        DB::table('cms_release_manifests')->where('id', $first->releaseManifest->id)->update(['tenant_id' => null]);
        self::assertFalse(app(ReleaseChanges::class)->history($second->releaseManifest)['baseline_available']);
        DB::table('cms_release_manifests')->where('id', $second->releaseManifest->id)->update(['tenant_id' => null]);
        self::assertFalse(app(ReleaseChanges::class)->pending($this->context())['baseline_available']);
    }

    public function test_full_rebaseline_form_and_history_provenance_are_rendered(): void
    {
        $first = $this->release();
        $first->releaseManifest->delete();
        $changes = app(ReleaseChanges::class)->pending($this->context());
        Filament\Facades\Filament::setCurrentPanel(Filament\Facades\Filament::getPanel('admin'));
        $html = view('post-release-status::publish-form', ['tenantId' => 0, 'context' => $this->context(), 'changes' => $changes])->render();
        self::assertStringContainsString('完整发布并重建基线', $html);
        self::assertDoesNotMatchRegularExpression('/<button\b[^>]*\sdisabled(?:\s|=|>)/', $html);
        $second = $this->release();
        $third = app(PublicationLifecycle::class)->start($this->context());
        app(PublicationLifecycle::class)->recover($third, '<script>repair</script>');
        $manifest = $third->releaseManifest->fresh();
        $html = view('post-release-status::history-record', compact('manifest'))->render();
        self::assertStringContainsString($second->releaseManifest->release_id, $html);
        self::assertStringContainsString($manifest->digest, $html);
        self::assertStringContainsString('&lt;script&gt;repair&lt;/script&gt;', $html);
        self::assertStringContainsString((string) auth('admin')->id(), $html);
        self::assertStringContainsString($third->fresh()->deployment_contents['recovery']['at'], $html);
    }

    public function test_history_ownership_missing_baseline(): void
    {
        $first = $this->release();
        $second = $this->release();
        $first->releaseManifest->delete();
        self::assertFalse(app(ReleaseChanges::class)->history($second->releaseManifest)['baseline_available']);
        $foreign = new ReleaseManifest;
        $foreign->forceFill(['tenant_id' => 99, 'scope_key' => 'foreign']);
        $this->expectException(HttpException::class);
        app(ReleaseChanges::class)->history($foreign);
    }

    public function test_escaped_diff_and_get_cannot_start(): void
    {
        $changes = app(ReleaseChanges::class)->pending($this->context());
        $html = view('post-release-status::changes', compact('changes'))->render();
        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('&lt;script&gt;', $html);
        $this->get('/headless/tenants/0/publications')->assertStatus(405);
        self::assertSame(0, DeploymentRecord::query()->count());
    }

    public function test_authorized_post_redirects_and_forged_tenant_is_ignored(): void
    {
        $response = $this->post(route('post-release-status.start', ['publicationTenant' => 0]), ['locale' => 'en', 'panel' => 'admin', 'tenant_id' => 999]);
        $response->assertRedirect();
        self::assertSame(0, (int) DeploymentRecord::query()->firstOrFail()->tenant_id);
        self::assertStringContainsString('publish-status', $response->headers->get('Location'));
    }

    public function test_read_permission_cannot_start_and_direct_history_is_checked(): void
    {
        $user = auth('admin')->user();
        $user->revokePermissionTo('tenant.content.publish');
        $this->post(route('post-release-status.start', ['publicationTenant' => 0]), ['locale' => 'en', 'panel' => 'admin'])->assertForbidden();
        self::assertSame(0, DeploymentRecord::query()->count());
        $user->revokePermissionTo('tenant.publication.history');
        $this->expectException(HttpException::class);
        app(ReleaseChanges::class)->history(new ReleaseManifest);
    }

    public function test_region_scope_uses_trusted_selection_and_never_offers_control_database(): void
    {
        config(['database.connections.east' => config('database.connections.sqlite'), 'database.connections.west' => config('database.connections.sqlite'),
            'filament-cms.regions' => [['id' => 'east', 'database' => 'east'], ['id' => 'west', 'database' => 'west']]]);
        self::assertSame('east', Scope::context([])['region']);
        session(['filament-cms.region_id' => 'west']);
        self::assertSame('west', Scope::context([])['region']);
        Context::add('filament-cms.region_id', 'east');
        $context = Scope::context(['region' => '']);
        self::assertSame('east', $context['region']);
        self::assertSame('west', Scope::context(['region' => 'west'])['region']);
        $html = view('post-release-status::scope', compact('context'))->render();
        self::assertStringNotContainsString('value=""', $html);
        self::assertStringContainsString('value="east" selected', $html);
        $this->expectException(HttpException::class);
        Scope::context(['region' => 'missing']);
    }

    public function test_livewire_status_renders_without_regions_or_changes(): void
    {
        $this->rows = [];
        Filament\Facades\Filament::setCurrentPanel(Filament\Facades\Filament::getPanel('admin'));
        Livewire::test(PublishStatus::class)
            ->assertOk()->assertSee('默认')->assertSee('新增')->assertSee('发布状态');
    }

    public function test_status_excludes_page_and_menu_definitions_from_all_change_counts_but_keeps_snapshots(): void
    {
        $state = (object) ['entries' => []];
        $registry = new ReleaseSnapshotRegistry;
        foreach (['page', 'menu', 'global_settings', 'global_page_definitions', 'global_menu_definitions'] as $type) {
            $state->entries[$type] = [['entity_id' => '1', 'projection' => ['title' => $type, 'value' => 'before']]];
            $registry->register(new class($state, $type) implements ReleaseSnapshotProvider
            {
                public function __construct(private object $state, private string $type) {}

                public function entityType(): string
                {
                    return $this->type;
                }

                public function snapshot(DeploymentRecord $release): array
                {
                    return $this->state->entries[$this->type];
                }
            });
        }
        $this->app->instance(ReleaseSnapshotRegistry::class, $registry);
        Filament\Facades\Filament::setCurrentPanel(Filament\Facades\Filament::getPanel('admin'));
        $assertStatus = function (string $kind): void {
            $component = Livewire::test(PublishStatus::class)->assertOk()
                ->assertDontSee('页面组件定义')->assertDontSee('菜单组件定义');
            $changes = $component->viewData('changes');
            self::assertSame(['page', 'menu', 'global_settings'], array_map(fn ($change) => ($change['after'] ?? $change['before'])['entity_type'], $changes[$kind]));
            self::assertCount(1, $changes['global']);
        };
        $assertStatus('added');
        $release = $this->release();
        self::assertArrayHasKey('global_page_definitions', $release->releaseManifest->entries);
        self::assertArrayHasKey('global_menu_definitions', $release->releaseManifest->entries);
        foreach ($state->entries as &$rows) {
            $rows[0]['projection']['value'] = 'after';
        }
        unset($rows);
        $assertStatus('modified');
        foreach ($state->entries as &$rows) {
            $rows = [];
        }
        unset($rows);
        $assertStatus('deleted');
    }

    public function test_only_definition_changes_show_the_empty_content_state(): void
    {
        $registry = new ReleaseSnapshotRegistry;
        $registry->register(new class implements ReleaseSnapshotProvider
        {
            public function entityType(): string
            {
                return 'global_menu_definitions';
            }

            public function snapshot(DeploymentRecord $release): array
            {
                return [['entity_id' => 'definitions', 'projection' => ['component' => ['name' => 'Internal definition']]]];
            }
        });
        $this->app->instance(ReleaseSnapshotRegistry::class, $registry);
        Filament\Facades\Filament::setCurrentPanel(Filament\Facades\Filament::getPanel('admin'));
        $component = Livewire::test(PublishStatus::class)->assertOk()->assertSee('暂无待发布变更')->assertDontSee('Internal definition');
        $changes = $component->viewData('changes');
        foreach (['added', 'modified', 'deleted', 'global'] as $kind) {
            self::assertSame([], $changes[$kind]);
        }
    }

    public function test_status_without_a_baseline_does_not_claim_content_is_up_to_date(): void
    {
        $this->release()->releaseManifest->delete();
        $this->rows = [];
        Filament\Facades\Filament::setCurrentPanel(Filament\Facades\Filament::getPanel('admin'));
        Livewire::test(PublishStatus::class)->assertOk()
            ->assertSee('待建立基线')->assertSee('完整发布并重建基线')->assertDontSee('内容已同步');
    }

    public function test_locked_status_uses_get_progress_without_a_publication_form(): void
    {
        app(PublicationLifecycle::class)->start($this->context());
        Filament\Facades\Filament::setCurrentPanel(Filament\Facades\Filament::getPanel('admin'));
        Livewire::test(PublishStatus::class)->assertOk()->assertSee('发布进行中')
            ->assertSee('当前租户内容已锁定')->assertDontSee('<form method="post"', false);
    }

    public function test_reader_without_publish_permission_has_no_publish_form(): void
    {
        auth('admin')->user()->revokePermissionTo('tenant.content.publish');
        Filament\Facades\Filament::setCurrentPanel(Filament\Facades\Filament::getPanel('admin'));
        Livewire::test(PublishStatus::class)->assertOk()->assertSee('待发布变更')
            ->assertDontSee('<form method="post"', false);
    }

    public function test_status_can_render_in_english(): void
    {
        app()->setLocale('en');
        Filament\Facades\Filament::setCurrentPanel(Filament\Facades\Filament::getPanel('admin'));
        Livewire::test(PublishStatus::class)->assertOk()->assertSee('Publication overview')
            ->assertSee('Ready to publish')->assertSee('Publish changes');
    }

    public function test_livewire_status_renders_the_selected_configured_region(): void
    {
        $this->rows = [];
        config(['database.connections.regional' => config('database.connections.sqlite'),
            'filament-cms.regions' => [['id' => 'regional', 'name' => '区域站点', 'database' => 'regional']]]);
        Filament\Facades\Filament::setCurrentPanel(Filament\Facades\Filament::getPanel('admin'));
        Livewire::test(PublishStatus::class)
            ->assertOk()->assertSee('区域站点')->assertSee('新增')->assertSee('发布状态');
    }

    public function test_progress_route_authenticated_get(): void
    {
        $record = $this->release();
        $this->getJson(route('filament-cms.publication.progress', ['deployment' => $record->id]))->assertOk()->assertJsonPath('status_code', 19);
    }
}

class StatusTestPanelProvider extends FilamentTestPanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return parent::panel($panel)->plugin(PostReleaseStatusPlugin::make());
    }
}
