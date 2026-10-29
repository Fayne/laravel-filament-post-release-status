<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;
use MWGuerra\FileManager\Services\AuthorizationService;

beforeEach(function () {
    // Disable authorization by default for easier testing
    config()->set('filemanager.authorization.enabled', true);
    $this->service = new AuthorizationService();
});

// Helper to create a mock user
function createAuthUser(): Authenticatable
{
    $user = Mockery::mock(Authenticatable::class);
    $user->shouldReceive('getAuthIdentifier')->andReturn(1);
    $user->shouldReceive('getAuthIdentifierName')->andReturn('id');
    $user->shouldReceive('getAuthPassword')->andReturn('password');
    $user->shouldReceive('getRememberToken')->andReturn(null);
    $user->shouldReceive('setRememberToken');
    $user->shouldReceive('getRememberTokenName')->andReturn('remember_token');
    return $user;
}

// Helper to create a mock item
function createMockItem(): object
{
    $item = new stdClass();
    $item->id = 123;
    return $item;
}

describe('canViewAny', function () {
    it('returns true for authenticated user', function () {
        $user = createAuthUser();

        expect($this->service->canViewAny($user))->toBeTrue();
    });

    it('returns false for guest and logs denial', function () {
        Log::shouldReceive('warning')
            ->once()
            ->with('FileManager authorization denied', Mockery::type('array'));

        expect($this->service->canViewAny(null))->toBeFalse();
    });

    it('uses current authenticated user when not provided', function () {
        config()->set('filemanager.authorization.enabled', false);

        expect($this->service->canViewAny())->toBeTrue();
    });
});

describe('canView', function () {
    it('returns true for authenticated user', function () {
        $user = createAuthUser();
        $item = createMockItem();

        expect($this->service->canView($user, $item))->toBeTrue();
    });

    it('returns false for guest and logs denial', function () {
        Log::shouldReceive('warning')
            ->once()
            ->with('FileManager authorization denied', Mockery::type('array'));

        expect($this->service->canView(null, null))->toBeFalse();
    });
});

describe('canCreate', function () {
    it('returns true for authenticated user', function () {
        $user = createAuthUser();

        expect($this->service->canCreate($user))->toBeTrue();
    });

    it('returns false for guest and logs denial', function () {
        Log::shouldReceive('warning')
            ->once()
            ->with('FileManager authorization denied', Mockery::type('array'));

        expect($this->service->canCreate(null))->toBeFalse();
    });
});

describe('canUpdate', function () {
    it('returns true for authenticated user', function () {
        $user = createAuthUser();
        $item = createMockItem();

        expect($this->service->canUpdate($user, $item))->toBeTrue();
    });

    it('returns false for guest and logs denial', function () {
        Log::shouldReceive('warning')
            ->once()
            ->with('FileManager authorization denied', Mockery::type('array'));

        expect($this->service->canUpdate(null, null))->toBeFalse();
    });
});

describe('canDelete', function () {
    it('returns true for authenticated user', function () {
        $user = createAuthUser();
        $item = createMockItem();

        expect($this->service->canDelete($user, $item))->toBeTrue();
    });

    it('returns false for guest and logs denial', function () {
        Log::shouldReceive('warning')
            ->once()
            ->with('FileManager authorization denied', Mockery::type('array'));

        expect($this->service->canDelete(null, null))->toBeFalse();
    });
});

describe('canDeleteAny', function () {
    it('returns true for authenticated user', function () {
        $user = createAuthUser();

        expect($this->service->canDeleteAny($user))->toBeTrue();
    });

    it('returns false for guest and logs denial', function () {
        Log::shouldReceive('warning')
            ->once()
            ->with('FileManager authorization denied', Mockery::type('array'));

        expect($this->service->canDeleteAny(null))->toBeFalse();
    });
});

describe('canDownload', function () {
    it('returns true for authenticated user', function () {
        $user = createAuthUser();
        $item = createMockItem();

        expect($this->service->canDownload($user, $item))->toBeTrue();
    });

    it('returns false for guest and logs denial', function () {
        Log::shouldReceive('warning')
            ->once()
            ->with('FileManager authorization denied', Mockery::type('array'));

        expect($this->service->canDownload(null, null))->toBeFalse();
    });
});

describe('authorize', function () {
    it('does not throw for authorized viewAny', function () {
        config()->set('filemanager.authorization.enabled', false);

        $this->service->authorize('viewAny');

        expect(true)->toBeTrue(); // No exception thrown
    });

    it('does not throw for authorized view', function () {
        config()->set('filemanager.authorization.enabled', false);

        $this->service->authorize('view', createMockItem());

        expect(true)->toBeTrue();
    });

    it('does not throw for authorized create', function () {
        config()->set('filemanager.authorization.enabled', false);

        $this->service->authorize('create');

        expect(true)->toBeTrue();
    });

    it('does not throw for authorized update', function () {
        config()->set('filemanager.authorization.enabled', false);

        $this->service->authorize('update', createMockItem());

        expect(true)->toBeTrue();
    });

    it('does not throw for authorized delete', function () {
        config()->set('filemanager.authorization.enabled', false);

        $this->service->authorize('delete', createMockItem());

        expect(true)->toBeTrue();
    });

    it('does not throw for authorized deleteAny', function () {
        config()->set('filemanager.authorization.enabled', false);

        $this->service->authorize('deleteAny');

        expect(true)->toBeTrue();
    });

    it('does not throw for authorized download', function () {
        config()->set('filemanager.authorization.enabled', false);

        $this->service->authorize('download', createMockItem());

        expect(true)->toBeTrue();
    });

    it('throws AuthorizationException for unauthorized viewAny', function () {
        config()->set('filemanager.authorization.enabled', true);

        Log::shouldReceive('warning')->once();

        $this->service->authorize('viewAny');
    })->throws(AuthorizationException::class, 'You are not authorized to viewAny this resource.');

    it('throws AuthorizationException for unauthorized view', function () {
        config()->set('filemanager.authorization.enabled', true);

        Log::shouldReceive('warning')->once();

        $this->service->authorize('view', createMockItem());
    })->throws(AuthorizationException::class, 'You are not authorized to view this resource.');

    it('throws AuthorizationException for unauthorized create', function () {
        config()->set('filemanager.authorization.enabled', true);

        Log::shouldReceive('warning')->once();

        $this->service->authorize('create');
    })->throws(AuthorizationException::class, 'You are not authorized to create this resource.');

    it('throws AuthorizationException for unknown ability', function () {
        config()->set('filemanager.authorization.enabled', true);

        $this->service->authorize('unknownAbility');
    })->throws(AuthorizationException::class, 'You are not authorized to unknownAbility this resource.');
});

describe('custom policy', function () {
    it('uses custom policy class from config', function () {
        config()->set('filemanager.authorization.policy', \MWGuerra\FileManager\Policies\FileSystemItemPolicy::class);

        $service = new AuthorizationService();

        // Should not throw - uses the standard policy
        config()->set('filemanager.authorization.enabled', false);
        expect($service->canViewAny())->toBeTrue();
    });
});
