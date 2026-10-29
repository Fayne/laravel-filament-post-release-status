<?php

namespace Fayne\PostReleaseStatus\Http;

use Fayne\CMS\Models\Tenant;
use Fayne\CMS\Publishing\PublicationLifecycle;
use Fayne\CMS\Support\TenantContext;
use Fayne\PostReleaseStatus\Filament\Pages\PublishStatus;
use Fayne\PostReleaseStatus\Services\Access;
use Fayne\PostReleaseStatus\Services\Scope;
use Filament\Facades\Filament;
use Illuminate\Http\Request;

final class StartPublicationController
{
    public function __invoke(Request $request, string $publicationTenant): mixed
    {
        $start = function () use ($request) {
            Access::authorize('tenant.content.publish');
            $context = Scope::context($request->only(['locale', 'region']));
            $record = app(PublicationLifecycle::class)->start($context, $request->user(config('filament-cms.guard', 'admin'))->getKey());

            // A normal redirect occurs after synchronous after-commit completion too.
            return redirect(PublishStatus::getUrl([...$context, 'deployment' => $record->getKey()], panel: $request->input('panel') && in_array($request->input('panel'), array_keys(Filament::getPanels()), true) ? $request->input('panel') : null, tenant: config('filament-cms.tenancy.enabled', false) ? Filament::getTenant() : null));
        };
        if (config('filament-cms.tenancy.enabled', false)) {
            $tenant = Tenant::query()->findOrFail($publicationTenant);
            abort_unless($request->user(config('filament-cms.guard', 'admin'))->canAccessTenant($tenant), 404);
            Filament::setTenant($tenant);

            return app(TenantContext::class)->run($tenant, $start);
        }
        abort_unless($publicationTenant === '0', 404);

        return $start();
    }
}
