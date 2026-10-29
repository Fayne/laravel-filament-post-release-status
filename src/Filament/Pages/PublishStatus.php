<?php

namespace Fayne\PostReleaseStatus\Filament\Pages;

use Fayne\CMS\Models\DeploymentRecord;
use Fayne\CMS\Models\PublicationLock;
use Fayne\CMS\Publishing\TenantWriteBarrier;
use Fayne\CMS\Support\TenantContext;
use Fayne\PostReleaseStatus\Services\Access;
use Fayne\PostReleaseStatus\Services\PendingChanges;
use Fayne\PostReleaseStatus\Services\Scope;
use Filament\Pages\Page;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PublishStatus extends Page
{
    protected string $view = 'post-release-status::publish-status';

    public static function getNavigationLabel(): string
    {
        return __('post-release-status::ui.status');
    }

    public function getTitle(): string
    {
        return __('post-release-status::ui.status');
    }

    public static function canAccess(): bool
    {
        try {
            Access::authorize('tenant.publication.view');

            return true;
        } catch (HttpException|ValidationException $e) {
            return false;
        }
    }

    protected function getViewData(): array
    {
        Access::authorize('tenant.publication.view');
        $filterLocale = filled(request()->query('locale')) ? (string) request()->query('locale') : '';
        $context = Scope::context(array_filter(request()->query(), fn ($value, $key) => $key !== 'locale' || filled($value), ARRAY_FILTER_USE_BOTH));
        $tenant = (int) app(TenantContext::class)->requireId();
        $locked = app(TenantWriteBarrier::class)->isLocked();
        $active = $locked ? PublicationLock::query()->find($tenant)?->deployment_record_id : null;
        $locales = $filterLocale === '' && \Fayne\CMS\Support\ContentLocale::enabled() ? array_keys(\Fayne\CMS\Support\ContentLocale::supported()) : [$context['locale']];
        $scopeKeys = array_map(fn ($locale) => Scope::key($tenant, [...$context, 'locale' => $locale]), $locales);
        $record = DeploymentRecord::query()->where('tenant_id', $tenant)->when($active, fn ($query) => $query->whereKey($active), fn ($query) => $query->whereIn('scope_key', $scopeKeys))->latest('id')->first();

        $changes = app(PendingChanges::class)->overview(request()->query());
        $wholeSite = app(\Fayne\CMS\Publishing\PublicationRouting::class)->configured();

        return ['context' => $context, 'filterLocale' => $filterLocale, 'deferredDetails' => true, 'wholeSite' => $wholeSite, 'changes' => $changes, 'record' => $record, 'locked' => $locked, 'tenantId' => $tenant];
    }
}
