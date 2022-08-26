<?php

namespace SkriptManufaktur\SimpleRestBundle\Tests\Unit;

use Exception;
use PHPUnit\Framework\TestCase;
use SkriptManufaktur\SimpleRestBundle\Tests\Fixtures\DummyUser;
use SkriptManufaktur\SimpleRestBundle\Voter\RoleService;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;

class RoleServiceTest extends TestCase
{
    private const HIERARCHY = [
        'ROLE_ADMIN' => ['ROLE_MANAGER', 'ROLE_DIRECTOR'],
        'ROLE_MANAGER' => ['ROLE_USER'],
        'ROLE_DIRECTOR' => ['ROLE_USER'],
        'ROLE_API' => [],
        'ROLE_USER' => [],
    ];

    private RoleService $roleService;


    protected function setUp(): void
    {
        parent::setUp();
        $this->roleService = new RoleService(self::HIERARCHY);
    }

    public function testRolesMap(): void
    {
        static::assertSame(
            [
                'ROLE_ADMIN' => ['ROLE_MANAGER', 'ROLE_DIRECTOR', 'ROLE_USER'],
                'ROLE_MANAGER' => ['ROLE_USER'],
                'ROLE_DIRECTOR' => ['ROLE_USER'],
                'ROLE_API' => [],
                'ROLE_USER' => [],
            ],
            $this->roleService->getRolesMap()
        );
        static::assertSame(
            ['ROLE_ADMIN', 'ROLE_MANAGER', 'ROLE_DIRECTOR', 'ROLE_API', 'ROLE_USER'],
            $this->roleService->getFlatRoles()
        );
    }

    /** @throws Exception */
    public function testUserHasRoles(): void
    {
        $user = new DummyUser('Dummy', ['ROLE_ADMIN']);

        static::assertTrue($this->roleService->userHasRoles($user, ['ROLE_USER', 'ROLE_DIRECTOR']));
        static::assertTrue($this->roleService->userHasRoles($user, ['ROLE_MANAGER', 'ROLE_DIRECTOR']));
        static::assertFalse($this->roleService->userHasRoles($user, ['ROLE_MANAGER', 'ROLE_API']));
    }

    /** @throws Exception */
    public function testUserHasRolesStrategyOr(): void
    {
        $user = new DummyUser('Dummy', ['ROLE_API']);

        static::assertTrue($this->roleService->userHasRoles($user, ['ROLE_API', 'ROLE_DIRECTOR'], RoleService::STRATEGY_OR));
        static::assertFalse($this->roleService->userHasRoles($user, ['ROLE_MANAGER']), RoleService::STRATEGY_OR);
        static::assertFalse($this->roleService->userHasRoles($user, ['ROLE_ADMIN']), RoleService::STRATEGY_OR);
    }

    /** @throws Exception */
    public function testTokenHasRoles(): void
    {
        $user = new DummyUser('Dummy', ['ROLE_ADMIN']);
        $token = new PreAuthenticatedToken($user, 'api', $user->getRoles());

        static::assertTrue($this->roleService->tokenHasRoles($token, ['ROLE_USER', 'ROLE_DIRECTOR']));
        static::assertTrue($this->roleService->tokenHasRoles($token, ['ROLE_MANAGER', 'ROLE_DIRECTOR']));
        static::assertFalse($this->roleService->tokenHasRoles($token, ['ROLE_MANAGER', 'ROLE_API']));
    }
}
