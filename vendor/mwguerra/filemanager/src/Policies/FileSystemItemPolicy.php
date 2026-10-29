<?php

namespace MWGuerra\FileManager\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Policy for FileSystemItem authorization.
 *
 * This policy controls access to file manager operations.
 * By default, it requires authentication and allows all operations
 * for authenticated users.
 *
 * To customize authorization:
 * 1. Extend this class in your application
 * 2. Override the methods you want to customize
 * 3. Register your custom policy in AuthServiceProvider
 *
 * Example custom policy:
 *
 * class CustomFileSystemItemPolicy extends FileSystemItemPolicy
 * {
 *     public function delete(Authenticatable $user, $item): bool
 *     {
 *         return $user->hasRole('admin');
 *     }
 * }
 */
class FileSystemItemPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any file system items.
     * This controls access to the file manager page itself.
     */
    public function viewAny(?Authenticatable $user): bool
    {
        // Check if authorization is enabled in config
        if (!config('filemanager.authorization.enabled', true)) {
            return true;
        }

        // Require authentication by default
        if (!$user) {
            return false;
        }

        // Check for required permission if configured
        $permission = config('filemanager.authorization.permissions.view_any');
        if ($permission && method_exists($user, 'can')) {
            return $user->can($permission);
        }

        return true;
    }

    /**
     * Determine whether the user can view a specific file system item.
     */
    public function view(?Authenticatable $user, $item): bool
    {
        if (!config('filemanager.authorization.enabled', true)) {
            return true;
        }

        if (!$user) {
            return false;
        }

        $permission = config('filemanager.authorization.permissions.view');
        if ($permission && method_exists($user, 'can')) {
            return $user->can($permission);
        }

        return true;
    }

    /**
     * Determine whether the user can create file system items.
     * This controls folder creation and file uploads.
     */
    public function create(?Authenticatable $user): bool
    {
        if (!config('filemanager.authorization.enabled', true)) {
            return true;
        }

        if (!$user) {
            return false;
        }

        $permission = config('filemanager.authorization.permissions.create');
        if ($permission && method_exists($user, 'can')) {
            return $user->can($permission);
        }

        return true;
    }

    /**
     * Determine whether the user can update a file system item.
     * This controls renaming and moving items.
     */
    public function update(?Authenticatable $user, $item): bool
    {
        if (!config('filemanager.authorization.enabled', true)) {
            return true;
        }

        if (!$user) {
            return false;
        }

        $permission = config('filemanager.authorization.permissions.update');
        if ($permission && method_exists($user, 'can')) {
            return $user->can($permission);
        }

        return true;
    }

    /**
     * Determine whether the user can delete a file system item.
     */
    public function delete(?Authenticatable $user, $item): bool
    {
        if (!config('filemanager.authorization.enabled', true)) {
            return true;
        }

        if (!$user) {
            return false;
        }

        $permission = config('filemanager.authorization.permissions.delete');
        if ($permission && method_exists($user, 'can')) {
            return $user->can($permission);
        }

        return true;
    }

    /**
     * Determine whether the user can download a file.
     */
    public function download(?Authenticatable $user, $item): bool
    {
        if (!config('filemanager.authorization.enabled', true)) {
            return true;
        }

        if (!$user) {
            return false;
        }

        $permission = config('filemanager.authorization.permissions.download');
        if ($permission && method_exists($user, 'can')) {
            return $user->can($permission);
        }

        return true;
    }

    /**
     * Determine whether the user can perform bulk delete operations.
     */
    public function deleteAny(?Authenticatable $user): bool
    {
        if (!config('filemanager.authorization.enabled', true)) {
            return true;
        }

        if (!$user) {
            return false;
        }

        $permission = config('filemanager.authorization.permissions.delete_any');
        if ($permission && method_exists($user, 'can')) {
            return $user->can($permission);
        }

        // By default, if user can delete single items, they can bulk delete
        return $this->delete($user, null);
    }
}
