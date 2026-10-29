<?php

use Illuminate\Contracts\Auth\Authenticatable;
use MWGuerra\FileManager\Policies\FileSystemItemPolicy;

beforeEach(function () {
    $this->policy = new FileSystemItemPolicy();
});

/**
 * Test user class that implements Authenticatable with can() method
 */
class TestUser implements Authenticatable
{
    protected array $permissions;

    public function __construct(array $permissions = [])
    {
        $this->permissions = $permissions;
    }

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): mixed
    {
        return 1;
    }

    public function getAuthPassword(): string
    {
        return 'password';
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function getRememberToken(): ?string
    {
        return null;
    }

    public function setRememberToken($value): void
    {
    }

    public function getRememberTokenName(): string
    {
        return 'remember_token';
    }

    public function can(string $permission): bool
    {
        return in_array($permission, $this->permissions);
    }
}

// Helper to create a mock user
function createMockUser(array $permissions = []): Authenticatable
{
    return new TestUser($permissions);
}

describe('viewAny', function () {
    it('allows when authorization is disabled', function () {
        config()->set('filemanager.authorization.enabled', false);

        expect($this->policy->viewAny(null))->toBeTrue();
    });

    it('denies guest when authorization is enabled', function () {
        config()->set('filemanager.authorization.enabled', true);

        expect($this->policy->viewAny(null))->toBeFalse();
    });

    it('allows authenticated user by default', function () {
        config()->set('filemanager.authorization.enabled', true);
        config()->set('filemanager.authorization.permissions.view_any', null);
        $user = createMockUser();

        expect($this->policy->viewAny($user))->toBeTrue();
    });

    it('checks permission when configured', function () {
        config()->set('filemanager.authorization.enabled', true);
        config()->set('filemanager.authorization.permissions.view_any', 'filemanager.view');

        $userWithPermission = createMockUser(['filemanager.view']);
        expect($this->policy->viewAny($userWithPermission))->toBeTrue();

        $userWithoutPermission = createMockUser([]);
        expect($this->policy->viewAny($userWithoutPermission))->toBeFalse();
    });
});

describe('view', function () {
    it('allows when authorization is disabled', function () {
        config()->set('filemanager.authorization.enabled', false);

        expect($this->policy->view(null, null))->toBeTrue();
    });

    it('denies guest when authorization is enabled', function () {
        config()->set('filemanager.authorization.enabled', true);

        expect($this->policy->view(null, null))->toBeFalse();
    });

    it('allows authenticated user by default', function () {
        config()->set('filemanager.authorization.enabled', true);
        config()->set('filemanager.authorization.permissions.view', null);
        $user = createMockUser();

        expect($this->policy->view($user, null))->toBeTrue();
    });

    it('checks permission when configured', function () {
        config()->set('filemanager.authorization.enabled', true);
        config()->set('filemanager.authorization.permissions.view', 'filemanager.view-item');

        $userWithPermission = createMockUser(['filemanager.view-item']);
        expect($this->policy->view($userWithPermission, null))->toBeTrue();

        $userWithoutPermission = createMockUser([]);
        expect($this->policy->view($userWithoutPermission, null))->toBeFalse();
    });
});

describe('create', function () {
    it('allows when authorization is disabled', function () {
        config()->set('filemanager.authorization.enabled', false);

        expect($this->policy->create(null))->toBeTrue();
    });

    it('denies guest when authorization is enabled', function () {
        config()->set('filemanager.authorization.enabled', true);

        expect($this->policy->create(null))->toBeFalse();
    });

    it('allows authenticated user by default', function () {
        config()->set('filemanager.authorization.enabled', true);
        config()->set('filemanager.authorization.permissions.create', null);
        $user = createMockUser();

        expect($this->policy->create($user))->toBeTrue();
    });

    it('checks permission when configured', function () {
        config()->set('filemanager.authorization.enabled', true);
        config()->set('filemanager.authorization.permissions.create', 'filemanager.create');

        $userWithPermission = createMockUser(['filemanager.create']);
        expect($this->policy->create($userWithPermission))->toBeTrue();

        $userWithoutPermission = createMockUser([]);
        expect($this->policy->create($userWithoutPermission))->toBeFalse();
    });
});

describe('update', function () {
    it('allows when authorization is disabled', function () {
        config()->set('filemanager.authorization.enabled', false);

        expect($this->policy->update(null, null))->toBeTrue();
    });

    it('denies guest when authorization is enabled', function () {
        config()->set('filemanager.authorization.enabled', true);

        expect($this->policy->update(null, null))->toBeFalse();
    });

    it('allows authenticated user by default', function () {
        config()->set('filemanager.authorization.enabled', true);
        config()->set('filemanager.authorization.permissions.update', null);
        $user = createMockUser();

        expect($this->policy->update($user, null))->toBeTrue();
    });

    it('checks permission when configured', function () {
        config()->set('filemanager.authorization.enabled', true);
        config()->set('filemanager.authorization.permissions.update', 'filemanager.update');

        $userWithPermission = createMockUser(['filemanager.update']);
        expect($this->policy->update($userWithPermission, null))->toBeTrue();

        $userWithoutPermission = createMockUser([]);
        expect($this->policy->update($userWithoutPermission, null))->toBeFalse();
    });
});

describe('delete', function () {
    it('allows when authorization is disabled', function () {
        config()->set('filemanager.authorization.enabled', false);

        expect($this->policy->delete(null, null))->toBeTrue();
    });

    it('denies guest when authorization is enabled', function () {
        config()->set('filemanager.authorization.enabled', true);

        expect($this->policy->delete(null, null))->toBeFalse();
    });

    it('allows authenticated user by default', function () {
        config()->set('filemanager.authorization.enabled', true);
        config()->set('filemanager.authorization.permissions.delete', null);
        $user = createMockUser();

        expect($this->policy->delete($user, null))->toBeTrue();
    });

    it('checks permission when configured', function () {
        config()->set('filemanager.authorization.enabled', true);
        config()->set('filemanager.authorization.permissions.delete', 'filemanager.delete');

        $userWithPermission = createMockUser(['filemanager.delete']);
        expect($this->policy->delete($userWithPermission, null))->toBeTrue();

        $userWithoutPermission = createMockUser([]);
        expect($this->policy->delete($userWithoutPermission, null))->toBeFalse();
    });
});

describe('download', function () {
    it('allows when authorization is disabled', function () {
        config()->set('filemanager.authorization.enabled', false);

        expect($this->policy->download(null, null))->toBeTrue();
    });

    it('denies guest when authorization is enabled', function () {
        config()->set('filemanager.authorization.enabled', true);

        expect($this->policy->download(null, null))->toBeFalse();
    });

    it('allows authenticated user by default', function () {
        config()->set('filemanager.authorization.enabled', true);
        config()->set('filemanager.authorization.permissions.download', null);
        $user = createMockUser();

        expect($this->policy->download($user, null))->toBeTrue();
    });

    it('checks permission when configured', function () {
        config()->set('filemanager.authorization.enabled', true);
        config()->set('filemanager.authorization.permissions.download', 'filemanager.download');

        $userWithPermission = createMockUser(['filemanager.download']);
        expect($this->policy->download($userWithPermission, null))->toBeTrue();

        $userWithoutPermission = createMockUser([]);
        expect($this->policy->download($userWithoutPermission, null))->toBeFalse();
    });
});

describe('deleteAny', function () {
    it('allows when authorization is disabled', function () {
        config()->set('filemanager.authorization.enabled', false);

        expect($this->policy->deleteAny(null))->toBeTrue();
    });

    it('denies guest when authorization is enabled', function () {
        config()->set('filemanager.authorization.enabled', true);

        expect($this->policy->deleteAny(null))->toBeFalse();
    });

    it('allows authenticated user by default', function () {
        config()->set('filemanager.authorization.enabled', true);
        config()->set('filemanager.authorization.permissions.delete_any', null);
        config()->set('filemanager.authorization.permissions.delete', null);
        $user = createMockUser();

        expect($this->policy->deleteAny($user))->toBeTrue();
    });

    it('checks delete_any permission when configured', function () {
        config()->set('filemanager.authorization.enabled', true);
        config()->set('filemanager.authorization.permissions.delete_any', 'filemanager.bulk-delete');

        $userWithPermission = createMockUser(['filemanager.bulk-delete']);
        expect($this->policy->deleteAny($userWithPermission))->toBeTrue();

        $userWithoutPermission = createMockUser([]);
        expect($this->policy->deleteAny($userWithoutPermission))->toBeFalse();
    });

    it('falls back to delete permission when delete_any not configured', function () {
        config()->set('filemanager.authorization.enabled', true);
        config()->set('filemanager.authorization.permissions.delete_any', null);
        config()->set('filemanager.authorization.permissions.delete', 'filemanager.delete');

        $userWithDeletePermission = createMockUser(['filemanager.delete']);
        expect($this->policy->deleteAny($userWithDeletePermission))->toBeTrue();
    });
});
