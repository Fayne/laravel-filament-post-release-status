<?php

use Fayne\CMS\Contracts\ReleaseSnapshotProvider;
use Fayne\CMS\Models\Admin;
use Fayne\CMS\Models\DeploymentRecord;
use Fayne\CMS\Support\Authorization\AuthorizationContext;
use Fayne\CMS\Support\Authorization\PermissionCatalog;
use Fayne\CMS\Support\ReleaseSnapshotRegistry;
use Fayne\PostReleaseStatus\Filament\Pages\PublishStatus;
use Fayne\PostReleaseStatus\PostReleaseStatusServiceProvider;
use Fayne\PostReleaseStatus\Services\PendingChanges;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\TestCase;

final class PendingDetailsTest extends TestCase
{
    private object $state;

    protected function getPackageProviders($app): array
    {
        return [...parent::getPackageProviders($app), PostReleaseStatusServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['filament-cms.tenancy.enabled' => false, 'filament-cms.default_locale' => 'en', 'filament-cms.multilingual_enabled' => true, 'filament-cms.supported_locales' => ['en' => 'English', 'zh_CN' => '简体中文']]);
        $this->artisan('migrate')->assertExitCode(0);
        PermissionCatalog::seed();
        app(AuthorizationContext::class)->activate(0);
        $admin = Admin::factory()->create();
        $admin->givePermissionTo('tenant.publication.view');
        $this->actingAs($admin, 'admin');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->state = (object) ['value' => 'DETAIL_VALUE_ONLY_ON_CLICK'];
        $registry = new ReleaseSnapshotRegistry;
        $registry->register(new class($this->state) implements ReleaseSnapshotProvider
        {
            public function __construct(private object $state) {}

            public function entityType(): string
            {
                return 'page';
            }

            public function snapshot(DeploymentRecord $release): array
            {
                $locale = $release->deployment_contents['locale'];

                return [['entity_id' => '1', 'path' => '/', 'locale' => $locale, 'projection' => ['name' => $locale === 'en' ? 'Demo store homepage' : '示例商店首页', 'description' => $this->state->value], 'content_snapshot' => ['secret' => 'INTERNAL_RAW'], 'connection' => 'INTERNAL_CONNECTION']];
            }
        });
        app()->instance(ReleaseSnapshotRegistry::class, $registry);
    }

    public function test_default_list_includes_both_languages_but_no_field_values(): void
    {
        $component = Livewire::test(PublishStatus::class)->assertOk()->assertSee('Demo store homepage')->assertSee('示例商店首页')
            ->assertDontSee('DETAIL_VALUE_ONLY_ON_CLICK')->assertDontSee('INTERNAL_RAW')->assertDontSee('INTERNAL_CONNECTION');
        $changes = $component->viewData('changes');
        self::assertCount(2, $changes['added']);
        foreach ($changes['added'] as $change) {
            self::assertArrayNotHasKey('projection', $change['after']);
            self::assertArrayHasKey('details_url', $change);
        }
    }

    public function test_explicit_language_filter_is_respected(): void
    {
        Livewire::withQueryParams(['locale' => 'zh_CN'])->test(PublishStatus::class)->assertOk()
            ->assertSee('示例商店首页')->assertDontSee('Demo store homepage');
    }

    public function test_click_reference_loads_only_the_requested_language_and_public_field_rows(): void
    {
        $changes = app(PendingChanges::class)->overview([]);
        $english = $changes['added'][0]['details_url'];
        $chinese = $changes['added'][1]['details_url'];
        $response = $this->getJson($chinese)->assertOk();
        $fields = $response->json('fields');
        self::assertContains('示例商店首页', array_column($fields, 'after'));
        self::assertContains('DETAIL_VALUE_ONLY_ON_CLICK', array_column($fields, 'after'));
        self::assertStringNotContainsString('INTERNAL_RAW', $response->getContent());
        self::assertStringNotContainsString('INTERNAL_CONNECTION', $response->getContent());
        self::assertContains('Demo store homepage', array_column($this->getJson($english)->assertOk()->json('fields'), 'after'));
    }

    public function test_tampered_expired_and_stale_details_fail_without_content(): void
    {
        $url = app(PendingChanges::class)->overview([])['added'][0]['details_url'];
        $this->getJson(str_replace('locale=en', 'locale=zh_CN', $url))->assertForbidden();
        $this->state->value = 'UPDATED_DETAIL';
        $this->getJson($url)->assertStatus(409)->assertDontSee('UPDATED_DETAIL');
        $this->travel(20)->minutes();
        $this->getJson($url)->assertForbidden();
    }

    public function test_detail_rechecks_permission_and_cannot_be_shared_with_another_account(): void
    {
        $url = app(PendingChanges::class)->overview([])['added'][0]['details_url'];
        auth('admin')->user()->revokePermissionTo('tenant.publication.view');
        $this->getJson($url)->assertForbidden();
        $other = Admin::factory()->create();
        $other->givePermissionTo('tenant.publication.view');
        $this->actingAs($other, 'admin')->getJson($url)->assertNotFound();
        auth('admin')->logout();
        $this->getJson($url)->assertUnauthorized();
    }

    public function test_missing_baseline_for_one_language_does_not_hide_the_other_languages(): void
    {
        $context = \Fayne\PostReleaseStatus\Services\Scope::context(['locale' => 'zh_CN']);
        $record = DeploymentRecord::create(['deployment_type' => 'full', 'status' => 19]);
        $record->forceFill(['tenant_id' => 0, 'scope_key' => \Fayne\PostReleaseStatus\Services\Scope::key(0, $context)])->save();
        $component = Livewire::test(PublishStatus::class)->assertOk()->assertSee('Demo store homepage')->assertSee('以下语言缺少可靠发布基线');
        $changes = $component->viewData('changes');
        self::assertCount(1, $changes['added']);
        self::assertSame(['zh_CN'], $changes['unavailable_scopes']);
    }

    public function test_details_remain_available_while_content_writes_are_locked(): void
    {
        $url = app(PendingChanges::class)->overview([])['added'][0]['details_url'];
        $record = DeploymentRecord::create(['deployment_type' => 'full', 'status' => 0, 'tenant_id' => 0]);
        \Illuminate\Support\Facades\DB::table('cms_publication_locks')->insert(['tenant_id' => 0, 'deployment_record_id' => $record->id, 'locked_at' => now()]);
        $this->getJson($url)->assertOk();
    }

    public function test_large_field_values_do_not_expand_the_initial_html_payload(): void
    {
        $small = Livewire::test(PublishStatus::class)->assertOk()->html();
        $this->state->value = str_repeat('LARGE_BODY_MARKER', 10000);
        $large = Livewire::test(PublishStatus::class)->assertOk()->assertDontSee('LARGE_BODY_MARKER')->html();
        self::assertLessThan(1024, abs(strlen($large) - strlen($small)));
    }
}
