<?php

use Fayne\PostReleaseStatus\PostReleaseStatusServiceProvider;
use Fayne\PostReleaseStatus\Services\ChangePresentation;
use Tests\TestCase;

final class ChangePresentationTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [...parent::getPackageProviders($app), PostReleaseStatusServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('zh_CN');
    }

    public function test_field_differences_hide_unchanged_and_internal_snapshot_data(): void
    {
        $before = ['projection' => ['values' => ['website_name' => 'Old', 'component_show_border' => true, 'primary_color' => '#2563eb']], 'content_snapshot' => ['private' => 'raw'], 'connection' => 'private_db'];
        $after = ['projection' => ['values' => ['website_name' => 'New', 'component_show_border' => false, 'primary_color' => '#2563eb']], 'content_snapshot' => ['private' => 'new raw'], 'connection' => 'other_db'];
        self::assertSame([
            ['label' => '网站名称', 'before' => 'Old', 'after' => 'New'],
            ['label' => '显示边框', 'before' => '是', 'after' => '否'],
        ], ChangePresentation::fields(['before' => $before, 'after' => $after]));
    }

    public function test_missing_and_unset_values_are_distinguished_and_nested_fields_are_readable(): void
    {
        self::assertSame([
            ['label' => '网站名称', 'before' => '—', 'after' => '未设置'],
            ['label' => '租户 › 名称', 'before' => '—', 'after' => 'Demo'],
        ], ChangePresentation::fields(['before' => null, 'after' => ['projection' => ['values' => ['website_name' => null], 'tenant' => ['name' => 'Demo']]]]));
    }

    public function test_global_impact_is_not_duplicated_but_routing_only_changes_are_kept(): void
    {
        $setting = ['before' => null, 'after' => ['entity_type' => 'global_settings', 'entity_id' => 'settings', 'projection' => []]];
        $routing = ['before' => null, 'after' => ['entity_type' => 'global_publication_routing', 'entity_id' => 'routing', 'projection' => []]];
        $groups = ChangePresentation::groups(['added' => [$setting], 'modified' => [], 'deleted' => [], 'global' => [$setting, $routing]]);
        self::assertSame([$setting], $groups['added']);
        self::assertSame([$routing], $groups['global']);
    }

    public function test_rendered_changes_escape_content_and_keep_raw_snapshots_out_of_details(): void
    {
        $change = ['before' => null, 'after' => ['entity_type' => 'page', 'entity_id' => '1', 'projection' => ['title' => '<script>alert(1)</script>'], 'content_snapshot' => ['token' => 'private-raw-token'], 'connection' => 'private-db']];
        $html = view('post-release-status::changes', ['changes' => ['baseline_available' => true, 'added' => [$change], 'modified' => [], 'deleted' => [], 'global' => []]])->render();
        self::assertStringContainsString('&lt;script&gt;', $html);
        self::assertStringNotContainsString('<script>alert(1)</script>', $html);
        self::assertStringNotContainsString('private-raw-token', $html);
        self::assertStringNotContainsString('private-db', $html);
        self::assertStringContainsString('<details', $html);
        self::assertDoesNotMatchRegularExpression('/<details\b[^>]*\bopen\b/', $html);
    }
}
