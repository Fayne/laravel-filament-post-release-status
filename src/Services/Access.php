<?php

namespace Fayne\PostReleaseStatus\Services;

use Fayne\CMS\Models\Tenant;
use Fayne\CMS\Support\Authorization\AuthorizationContext;
use Fayne\CMS\Support\TenantContext;

final class Access
{
    public static function authorize(string $permission): void
    {
        $id = (int) app(TenantContext::class)->requireId();
        $user = auth(config('filament-cms.guard', 'admin'))->user();
        abort_unless($user, 403);
        if (config('filament-cms.tenancy.enabled', false)) {
            abort_unless($user->canAccessTenant(Tenant::query()->findOrFail($id)), 404);
        }
        app(AuthorizationContext::class)->run($id, fn () => abort_unless($user->can($permission), 403));
    }
}
