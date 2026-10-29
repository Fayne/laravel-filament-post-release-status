<?php

namespace Fayne\PostReleaseStatus\Services;

use Fayne\CMS\Models\DeploymentRecord;
use Fayne\CMS\Models\ReleaseManifest;
use Fayne\CMS\Support\ReleaseSnapshotRegistry;
use Fayne\CMS\Support\TenantContext;
use InvalidArgumentException;

final class ReleaseChanges
{
    public function pending(array $context): array
    {
        Access::authorize('tenant.publication.view');
        $context = Scope::context($context);
        $tenant = (int) app(TenantContext::class)->requireId();
        $scope = Scope::key($tenant, $context);
        $deployment = DeploymentRecord::query()->where('tenant_id', $tenant)->where('scope_key', $scope)->where('status', 19)->latest('id')->first();
        $baseline = $deployment?->releaseManifest;
        $available = ! $deployment || ($baseline && $baseline->tenant_id !== null && $baseline->status === 'succeeded' && (int) $baseline->tenant_id === $tenant && $baseline->scope_key === $scope);
        $wholeSite = app(\Fayne\CMS\Publishing\PublicationRouting::class)->configured();
        if ($wholeSite && ! $deployment) {
            $available = ! DeploymentRecord::query()->where('tenant_id', $tenant)->where('status', 19)->where('deployment_contents->site_id', $context['site_id'])->exists();
        }
        $preview = new DeploymentRecord;
        $preview->forceFill(['tenant_id' => $tenant, 'scope_key' => $scope, 'deployment_contents' => $context]);
        $entries = [];
        if ($wholeSite) {
            $preview->deployment_contents = ['publication_mode' => 'whole_site', 'baseline_unavailable' => ! $available, ...$context];
            $snapshot = app(\Fayne\CMS\Publishing\WholeSiteSnapshot::class)->capture($preview);
            $entries = $snapshot['entries'];
            $context = $snapshot['context'];
        }
        foreach (($wholeSite ? [] : app(ReleaseSnapshotRegistry::class)->all()) as $type => $provider) {
            $entries[$type] = $provider->snapshot($preview);
        }

        return ['baseline_available' => $available, 'baseline' => $baseline, 'current_release' => $preview, ...($available ? $this->compare($baseline?->entries ?? [], $entries, $baseline?->context, $context) : $this->emptyChanges())];
    }

    public function history(ReleaseManifest $manifest): array
    {
        Access::authorize('tenant.publication.history');
        $tenant = (int) app(TenantContext::class)->requireId();
        abort_unless($manifest->tenant_id !== null && (int) $manifest->tenant_id === $tenant, 404);
        $baseline = $manifest->baseline_manifest_id ? ReleaseManifest::query()->find($manifest->baseline_manifest_id) : null;
        $available = ! ($manifest->context['baseline_unavailable'] ?? false) && (! $manifest->baseline_manifest_id || ($baseline && $baseline->tenant_id !== null && (int) $baseline->tenant_id === $tenant && $baseline->scope_key === $manifest->scope_key && $baseline->status === 'succeeded'));

        return ['baseline_available' => $available, 'baseline' => $baseline, ...($available ? $this->compare($baseline?->entries ?? [], $manifest->entries ?? [], $baseline?->context, $manifest->context) : $this->emptyChanges())];
    }

    public function compare(array $before, array $after, ?array $beforeContext = null, ?array $afterContext = null): array
    {
        $routingChanged = ($beforeContext['schema_version'] ?? null) === 2 && ($afterContext['schema_version'] ?? null) === 2
            && $this->normalize($beforeContext['routing']) !== $this->normalize($afterContext['routing']);
        $before = $this->index($before);
        $after = $this->index($after);
        $changes = $this->emptyChanges();
        foreach (array_unique([...array_keys($before), ...array_keys($after)]) as $key) {
            $old = $before[$key] ?? null;
            $new = $after[$key] ?? null;
            if (! $routingChanged && $old !== null && $new !== null && $this->normalize($this->comparable($old)) === $this->normalize($this->comparable($new))) {
                continue;
            }
            $kind = $old === null ? 'added' : ($new === null ? 'deleted' : 'modified');
            $change = ['before' => $old, 'after' => $new];
            $changes[$kind][] = $change;
            $type = ($new ?? $old)['entity_type'];
            if ($type === 'global' || str_starts_with($type, 'global_')) {
                $changes['global'][] = $change;
            }
        }

        $changes['routing_changed'] = $routingChanged;
        if ($routingChanged) {
            $changes['global'][] = ['before' => ['entity_type' => 'global_publication_routing', 'entity_id' => 'routing', 'projection' => $beforeContext['routing']], 'after' => ['entity_type' => 'global_publication_routing', 'entity_id' => 'routing', 'projection' => $afterContext['routing']]];
        }

        return $changes;
    }

    private function emptyChanges(): array
    {
        return ['added' => [], 'modified' => [], 'deleted' => [], 'global' => [], 'routing_changed' => false];
    }

    private function index(array $entries): array
    {
        $result = [];
        foreach ($entries as $type => $rows) {
            if (! is_string($type) || trim($type) === '' || ! is_array($rows)) {
                throw new InvalidArgumentException('Invalid release entity identity bucket.');
            }
            foreach ($rows as $row) {
                if (! is_array($row) || ! isset($row['entity_id']) || (! is_string($row['entity_id']) && ! is_int($row['entity_id'])) || (is_string($row['entity_id']) && trim($row['entity_id']) === '') || (array_key_exists('entity_type', $row) && $row['entity_type'] !== $type)) {
                    throw new InvalidArgumentException('Invalid release entity identity.');
                }
                $row['entity_type'] = $type;
                $key = json_encode([...(isset($row['scope_id']) ? [(string) $row['scope_id']] : []), $row['entity_type'], isset($row['scope_id']) ? (string) $row['entity_id'] : $row['entity_id']], JSON_THROW_ON_ERROR);
                if (array_key_exists($key, $result)) {
                    throw new InvalidArgumentException('Duplicate release entity identity.');
                }
                $result[$key] = $row;
            }
        }

        return $result;
    }

    private function comparable(array $row): array
    {
        if (array_key_exists('comparison_projection', $row)) {
            $row['projection'] = $row['comparison_projection'];
        }

        return array_intersect_key($row, array_flip(['projection', 'path', 'slug', 'paths', 'locale']));
    }

    private function normalize(mixed $value): mixed
    {
        if (is_array($value)) {
            if (! array_is_list($value)) {
                ksort($value);
            }

            return array_map($this->normalize(...), $value);
        }

        return $value;
    }
}
