<?php

namespace MWGuerra\FileManager\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;
use MWGuerra\FileManager\Policies\FileSystemItemPolicy;

/**
 * Centralized authorization service for file manager operations.
 *
 * This service wraps the policy checks and provides a clean API
 * for checking permissions throughout the file manager.
 */
class AuthorizationService
{
    protected FileSystemItemPolicy $policy;

    public function __construct()
    {
        $policyClass = config('filemanager.authorization.policy', FileSystemItemPolicy::class);
        $this->policy = app($policyClass);
    }

    /**
     * Check if user can access the file manager.
     */
    public function canViewAny(?Authenticatable $user = null): bool
    {
        $user = $user ?? auth()->user();
        $result = $this->policy->viewAny($user);

        if (!$result) {
            $this->logDenied('viewAny', $user);
        }

        return $result;
    }

    /**
     * Check if user can view a specific item.
     */
    public function canView(?Authenticatable $user = null, $item = null): bool
    {
        $user = $user ?? auth()->user();
        $result = $this->policy->view($user, $item);

        if (!$result) {
            $this->logDenied('view', $user, $item);
        }

        return $result;
    }

    /**
     * Check if user can create items (upload files, create folders).
     */
    public function canCreate(?Authenticatable $user = null): bool
    {
        $user = $user ?? auth()->user();
        $result = $this->policy->create($user);

        if (!$result) {
            $this->logDenied('create', $user);
        }

        return $result;
    }

    /**
     * Check if user can update an item (rename, move).
     */
    public function canUpdate(?Authenticatable $user = null, $item = null): bool
    {
        $user = $user ?? auth()->user();
        $result = $this->policy->update($user, $item);

        if (!$result) {
            $this->logDenied('update', $user, $item);
        }

        return $result;
    }

    /**
     * Check if user can delete an item.
     */
    public function canDelete(?Authenticatable $user = null, $item = null): bool
    {
        $user = $user ?? auth()->user();
        $result = $this->policy->delete($user, $item);

        if (!$result) {
            $this->logDenied('delete', $user, $item);
        }

        return $result;
    }

    /**
     * Check if user can bulk delete items.
     */
    public function canDeleteAny(?Authenticatable $user = null): bool
    {
        $user = $user ?? auth()->user();
        $result = $this->policy->deleteAny($user);

        if (!$result) {
            $this->logDenied('deleteAny', $user);
        }

        return $result;
    }

    /**
     * Check if user can download a file.
     */
    public function canDownload(?Authenticatable $user = null, $item = null): bool
    {
        $user = $user ?? auth()->user();
        $result = $this->policy->download($user, $item);

        if (!$result) {
            $this->logDenied('download', $user, $item);
        }

        return $result;
    }

    /**
     * Authorize an action, throwing an exception if not allowed.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorize(string $ability, $item = null): void
    {
        $user = auth()->user();
        $allowed = match ($ability) {
            'viewAny' => $this->canViewAny($user),
            'view' => $this->canView($user, $item),
            'create' => $this->canCreate($user),
            'update' => $this->canUpdate($user, $item),
            'delete' => $this->canDelete($user, $item),
            'deleteAny' => $this->canDeleteAny($user),
            'download' => $this->canDownload($user, $item),
            default => false,
        };

        if (!$allowed) {
            throw new \Illuminate\Auth\Access\AuthorizationException(
                "You are not authorized to {$ability} this resource."
            );
        }
    }

    /**
     * Log denied authorization attempts for security monitoring.
     */
    protected function logDenied(string $ability, ?Authenticatable $user, $item = null): void
    {
        $userId = $user?->getAuthIdentifier() ?? 'guest';
        $itemId = is_object($item) && method_exists($item, 'getKey') ? $item->getKey() : null;

        Log::warning('FileManager authorization denied', [
            'ability' => $ability,
            'user_id' => $userId,
            'item_id' => $itemId,
            'ip' => request()->ip(),
        ]);
    }
}
