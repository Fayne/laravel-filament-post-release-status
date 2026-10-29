<?php

namespace Fayne\PostReleaseStatus\Services;

use Fayne\CMS\Publishing\PublicationRouting;
use Fayne\CMS\Support\ContentLocale;
use Fayne\CMS\Support\TenantContext;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\URL;

final class PendingChanges
{
    private const HIDDEN = ['global_page_definitions', 'global_menu_definitions'];

    public function __construct(private readonly ReleaseChanges $changes) {}

    public function overview(array $input): array
    {
        $selected = filled($input['locale'] ?? null) ? ContentLocale::resolve($input['locale']) : null;
        $context = Scope::context(Arr::whereNotNull(['locale' => $selected, 'region' => $input['region'] ?? null]));
        $wholeSite = app(PublicationRouting::class)->configured();
        $locales = $wholeSite ? [$context['locale']] : ($selected ? [$selected] : (ContentLocale::enabled() ? array_keys(ContentLocale::supported()) : [ContentLocale::default()]));
        $result = ['baseline_available' => false, 'unavailable_scopes' => [], 'routing_changed' => false, 'added' => [], 'modified' => [], 'deleted' => [], 'global' => []];
        foreach ($locales as $locale) {
            $scope = [...$context, 'locale' => $locale];
            $pending = $this->changes->pending($scope);
            $result['baseline_available'] = $result['baseline_available'] || $pending['baseline_available'];
            $result['routing_changed'] = $result['routing_changed'] || $pending['routing_changed'];
            if (! $pending['baseline_available']) {
                $result['unavailable_scopes'][] = $locale;
            }
            foreach (['added', 'modified', 'deleted', 'global'] as $kind) {
                foreach ($pending[$kind] as $change) {
                    $row = $change['after'] ?? $change['before'];
                    if (in_array($row['entity_type'], self::HIDDEN, true)
                        || ($wholeSite && isset($row['scope_id']) && (($row['region'] ?? '') !== $context['region'] || ($selected !== null && ($row['locale'] ?? '') !== $selected)))) {
                        continue;
                    }
                    $detailScope = ['region' => $row['region'] ?? $scope['region'], 'locale' => $row['locale'] ?? $scope['locale']];
                    $summary = [];
                    foreach (['before', 'after'] as $side) {
                        $summary[$side] = $change[$side] === null ? null : [
                            ...Arr::only($change[$side], ['entity_type', 'entity_id', 'scope_id', 'region', 'locale', 'path', 'url', 'slug']),
                            ...$detailScope, 'name' => ChangePresentation::title($change[$side]),
                        ];
                    }
                    $summary['details_url'] = URL::temporarySignedRoute('post-release-status.change', now()->addMinutes(15), [
                        'publicationTenant' => app(TenantContext::class)->requireId(),
                        'viewer' => auth(config('filament-cms.guard', 'admin'))->id(),
                        ...$detailScope, 'kind' => $kind, 'type' => $row['entity_type'], 'entity' => (string) $row['entity_id'],
                        'scope' => $row['scope_id'] ?? '', 'version' => $this->version($change),
                        'ui_locale' => in_array(app()->getLocale(), ['en', 'zh_CN'], true) ? app()->getLocale() : 'en',
                    ]);
                    $result[$kind][] = $summary;
                }
            }
        }

        return $result;
    }

    public function detail(array $reference): array
    {
        abort_if(in_array($reference['type'], self::HIDDEN, true), 404);
        $pending = $this->changes->pending(Arr::only($reference, ['region', 'locale']));
        foreach ($pending[$reference['kind']] as $change) {
            $row = $change['after'] ?? $change['before'];
            if ($row['entity_type'] === $reference['type'] && (string) $row['entity_id'] === $reference['entity'] && (string) ($row['scope_id'] ?? '') === (string) ($reference['scope'] ?? '')) {
                abort_unless(hash_equals($this->version($change), $reference['version']), 409, __('post-release-status::ui.details_stale'));

                return ChangePresentation::fields($change);
            }
        }
        abort(409, __('post-release-status::ui.details_stale'));
    }

    private function version(array $change): string
    {
        return hash('sha256', json_encode($change, JSON_THROW_ON_ERROR));
    }
}
