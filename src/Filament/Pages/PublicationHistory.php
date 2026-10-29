<?php

namespace Fayne\PostReleaseStatus\Filament\Pages;

use Fayne\CMS\Models\ReleaseManifest;
use Fayne\CMS\Support\TenantContext;
use Fayne\PostReleaseStatus\Services\Access;
use Fayne\PostReleaseStatus\Services\ReleaseChanges;
use Fayne\PostReleaseStatus\Services\Scope;
use Filament\Pages\Page;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PublicationHistory extends Page
{
    protected string $view = 'post-release-status::publication-history';

    public static function getNavigationLabel(): string
    {
        return '发布历史';
    }

    public function getTitle(): string
    {
        return '发布历史';
    }

    public static function canAccess(): bool
    {
        try {
            Access::authorize('tenant.publication.history');

            return true;
        } catch (HttpException|ValidationException $e) {
            return false;
        }
    }

    protected function getViewData(): array
    {
        Access::authorize('tenant.publication.history');
        $tenantId = (int) app(TenantContext::class)->requireId();
        $query = ReleaseManifest::query()->where('tenant_id', $tenantId);
        $detail = request()->query('release') ? (clone $query)->findOrFail(request()->query('release')) : null;
        if ($detail) {
            $context = $detail->context ?? [];
            $scopes = collect($context['scopes'] ?? []);
            $scope = $scopes->first(fn ($scope) => $scope['region'] === request()->query('region') && $scope['locale'] === request()->query('locale')) ?? $scopes->first();
            $context = [...$context, 'region' => (string) ($context['region'] ?? $scope['region'] ?? ''), 'locale' => (string) ($context['locale'] ?? $scope['locale'] ?? '')];
        } else {
            $context = Scope::context(request()->query());
            $query->when(! app(\Fayne\CMS\Publishing\PublicationRouting::class)->configured(), fn ($query) => $query->where(fn ($scopes) => $scopes->where('scope_key', Scope::key($tenantId, $context))->orWhere('context->schema_version', 2)));
        }

        return ['context' => $context, 'records' => $query->with('deploymentRecord')->latest('id')->limit(100)->get(), 'detail' => $detail, 'changes' => $detail ? app(ReleaseChanges::class)->history($detail) : null];
    }
}
