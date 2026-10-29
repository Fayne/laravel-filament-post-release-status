<?php

namespace Fayne\PostReleaseStatus\Http;

use Fayne\CMS\Models\Tenant;
use Fayne\CMS\Support\TenantContext;
use Fayne\PostReleaseStatus\Services\PendingChanges;
use Illuminate\Http\Request;

final class PublicationChangeController
{
    public function __invoke(Request $request, string $publicationTenant): mixed
    {
        abort_unless((string) $request->query('viewer') === (string) $request->user(config('filament-cms.guard', 'admin'))->getKey(), 404);
        $reference = $request->validate([
            'locale' => ['required', 'string'], 'region' => ['nullable', 'string'],
            'kind' => ['required', 'in:added,modified,deleted,global'], 'type' => ['required', 'string'], 'entity' => ['required', 'string'],
            'scope' => ['nullable', 'string'], 'version' => ['required', 'string', 'size:64'], 'ui_locale' => ['required', 'in:en,zh_CN'],
        ]);
        $read = function () use ($reference) {
            $previousLocale = app()->getLocale();
            app()->setLocale($reference['ui_locale']);
            try {
                return response()->json(['fields' => app(PendingChanges::class)->detail($reference)])->header('Cache-Control', 'private, no-store');
            } finally {
                app()->setLocale($previousLocale);
            }
        };
        if (config('filament-cms.tenancy.enabled', false)) {
            $tenant = Tenant::query()->findOrFail($publicationTenant);
            abort_unless($request->user(config('filament-cms.guard', 'admin'))->canAccessTenant($tenant), 404);

            return app(TenantContext::class)->run($tenant, $read);
        }
        abort_unless($publicationTenant === '0', 404);

        return $read();
    }
}
