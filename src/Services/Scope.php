<?php

namespace Fayne\PostReleaseStatus\Services;

use Fayne\CMS\Support\ContentLocale;
use Fayne\CMS\Support\PublicationRegion;

final class Scope
{
    public static function context(array $input): array
    {
        $region = PublicationRegion::resolve(isset($input['region']) ? (string) $input['region'] : null);

        return ['site_id' => (string) config('filament-cms.site_id', ''), 'locale' => ContentLocale::resolve($input['locale'] ?? null), 'region' => $region];
    }

    public static function key(int $tenant, array $context): string
    {
        if (app(\Fayne\CMS\Publishing\PublicationRouting::class)->configured()) {
            return app(\Fayne\CMS\Publishing\PublicationRouting::class)->scopeKey($context);
        }

        return hash('sha256', json_encode([$tenant, (string) ($context['site_id'] ?? ''), (string) ($context['region'] ?? ''), $context['locale']], JSON_THROW_ON_ERROR));
    }
}
