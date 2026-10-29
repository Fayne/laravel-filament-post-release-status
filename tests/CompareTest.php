<?php

use Fayne\PostReleaseStatus\Services\ReleaseChanges;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../src/Services/ReleaseChanges.php';
final class CompareTest extends TestCase
{
    public function test_paths_types_order_and_metadata(): void
    {
        $service = new ReleaseChanges;
        $row = ['entity_type' => 'page', 'entity_id' => 1, 'path' => '/old', 'projection' => ['a' => 1, 'b' => [1, 2]], 'updated_at' => 'old'];
        $after = [...$row, 'projection' => ['b' => [1, 2], 'a' => 1], 'updated_at' => 'new'];
        self::assertSame([], $service->compare(['page' => [$row]], ['page' => [$after]])['modified']);
        foreach ([['path' => '/new'], ['projection' => ['a' => '1', 'b' => [1, 2]]], ['projection' => ['a' => 1, 'b' => [2, 1]]]] as $change) {
            self::assertCount(1, $service->compare(['page' => [$row]], ['page' => [[...$after, ...$change]]])['modified']);
        }
        self::assertCount(1, $service->compare([], ['page' => [$row]])['added']);
        self::assertCount(1, $service->compare(['page' => [$row]], [])['deleted']);
    }

    public function test_global_restore_and_null_are_distinct(): void
    {
        $service = new ReleaseChanges;
        $row = ['entity_id' => 'settings', 'projection' => ['value' => null]];
        $changes = $service->compare([], ['global_settings' => [$row]]);
        self::assertCount(1, $changes['global']);
        self::assertCount(1, $changes['added']);
        self::assertCount(1, $service->compare(['global_settings' => [$row]], ['global_settings' => [[...$row, 'projection' => ['value' => '']]]])['modified']);
    }

    public function test_malformed_identity_is_explicitly_rejected(): void
    {
        foreach ([[], ['entity_id' => null], ['entity_id' => []], ['entity_id' => new stdClass], ['entity_id' => true], ['entity_id' => 1, 'entity_type' => 'wrong'], ['entity_id' => 1, 'entity_type' => '']] as $row) {
            try {
                (new ReleaseChanges)->compare([], ['page' => [$row]]);
                self::fail('Malformed identity was accepted.');
            } catch (InvalidArgumentException $exception) {
                self::assertStringContainsString('identity', $exception->getMessage());
            }
        }
    }

    public function test_comparison_projection_excludes_build_metadata_but_keeps_paths(): void
    {
        $row = ['entity_id' => 1, 'projection' => ['updated_at' => 'old'], 'comparison_projection' => ['title' => 'A'], 'path' => '/a'];
        $after = [...$row, 'projection' => ['updated_at' => 'new']];
        $service = new ReleaseChanges;
        self::assertSame([], $service->compare(['page' => [$row]], ['page' => [$after]])['modified']);
        self::assertCount(1, $service->compare(['page' => [$row]], ['page' => [[...$after, 'path' => '/b']]])['modified']);
        self::assertCount(1, $service->compare(['page' => [$row]], ['page' => [[...$after, 'comparison_projection' => ['title' => 'B']]]])['modified']);
    }

    public function test_duplicate_identity_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new ReleaseChanges)->compare([], ['page' => [['entity_id' => 1], ['entity_id' => 1]]]);
    }
}
