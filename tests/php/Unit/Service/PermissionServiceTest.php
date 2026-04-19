<?php

declare(strict_types=1);

namespace OCA\Lists\Tests\Unit\Service;

use OCA\Lists\Db\ListEntity;
use OCA\Lists\Db\ListMapper;
use OCA\Lists\Db\ShareEntity;
use OCA\Lists\Db\ShareMapper;
use OCA\Lists\Exception\ForbiddenException;
use OCA\Lists\Exception\NotFoundException;
use OCA\Lists\Service\PermissionService;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PermissionServiceTest extends TestCase {
    private ListMapper&MockObject    $listMapper;
    private ShareMapper&MockObject   $shareMapper;
    private IGroupManager&MockObject $groupManager;
    private IUserManager&MockObject  $userManager;
    private PermissionService        $service;

    protected function setUp(): void {
        $this->listMapper   = $this->createMock(ListMapper::class);
        $this->shareMapper  = $this->createMock(ShareMapper::class);
        $this->groupManager = $this->createMock(IGroupManager::class);
        $this->userManager  = $this->createMock(IUserManager::class);

        $this->service = new PermissionService(
            $this->listMapper,
            $this->shareMapper,
            $this->groupManager,
            $this->userManager,
        );
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function makeList(int $id, string $uid): ListEntity {
        $list = new ListEntity();
        $list->setId($id);
        $list->setUid($uid);
        $list->setName('test');
        return $list;
    }

    private function makeShare(int $permissions): ShareEntity {
        $share = new ShareEntity();
        $share->setPermissions($permissions);
        return $share;
    }

    private function mockUser(string $uid): IUser&MockObject {
        $user = $this->createMock(IUser::class);
        $this->userManager->method('get')->with($uid)->willReturn($user);
        return $user;
    }

    private function mockGroups(IUser $user, array $groupIds): void {
        $this->groupManager->method('getUserGroupIds')->with($user)->willReturn($groupIds);
    }

    // ── getAccessibleList ─────────────────────────────────────────────────────

    public function testGetAccessibleListOwner(): void {
        $list = $this->makeList(1, 'alice');
        $this->listMapper->method('findById')->with(1)->willReturn($list);

        $result = $this->service->getAccessibleList(1, 'alice');

        $this->assertSame($list, $result);
    }

    public function testGetAccessibleListDirectShare(): void {
        $list  = $this->makeList(1, 'alice');
        $share = $this->makeShare(ShareEntity::PERM_READ);

        $this->listMapper->method('findById')->with(1)->willReturn($list);
        $user = $this->mockUser('bob');
        $this->mockGroups($user, []);
        $this->shareMapper->method('findUserShare')->willReturn($share);

        $result = $this->service->getAccessibleList(1, 'bob');

        $this->assertSame($list, $result);
    }

    public function testGetAccessibleListGroupShare(): void {
        $list  = $this->makeList(1, 'alice');
        $share = $this->makeShare(ShareEntity::PERM_READ);

        $this->listMapper->method('findById')->with(1)->willReturn($list);
        $user = $this->mockUser('bob');
        $this->mockGroups($user, ['staff']);
        $this->shareMapper->method('findUserShare')
            ->with(1, 'bob', ['staff'])
            ->willReturn($share);

        $result = $this->service->getAccessibleList(1, 'bob');

        $this->assertSame($list, $result);
    }

    public function testGetAccessibleListNoShareThrowsForbidden(): void {
        $list = $this->makeList(1, 'alice');

        $this->listMapper->method('findById')->with(1)->willReturn($list);
        $user = $this->mockUser('bob');
        $this->mockGroups($user, []);
        $this->shareMapper->method('findUserShare')->willReturn(null);

        $this->expectException(ForbiddenException::class);
        $this->service->getAccessibleList(1, 'bob');
    }

    public function testGetAccessibleListNotFoundPropagates(): void {
        $this->listMapper->method('findById')
            ->willThrowException(new NotFoundException('not found'));

        $this->expectException(NotFoundException::class);
        $this->service->getAccessibleList(99, 'alice');
    }

    // ── requireOwner ─────────────────────────────────────────────────────────

    public function testRequireOwnerSucceeds(): void {
        $list = $this->makeList(1, 'alice');
        $this->listMapper->method('findById')->willReturn($list);

        $result = $this->service->requireOwner(1, 'alice');

        $this->assertSame($list, $result);
    }

    public function testRequireOwnerNonOwnerThrowsForbidden(): void {
        $list = $this->makeList(1, 'alice');
        $this->listMapper->method('findById')->willReturn($list);

        $this->expectException(ForbiddenException::class);
        $this->service->requireOwner(1, 'bob');
    }

    // ── canWrite ──────────────────────────────────────────────────────────────

    public function testCanWriteOwnerReturnsTrue(): void {
        $list = $this->makeList(1, 'alice');
        $this->listMapper->method('findById')->willReturn($list);

        $this->assertTrue($this->service->canWrite(1, 'alice'));
    }

    public function testCanWriteWithWriteShareReturnsTrue(): void {
        $list  = $this->makeList(1, 'alice');
        $share = $this->makeShare(ShareEntity::PERM_WRITE);

        $this->listMapper->method('findById')->willReturn($list);
        $user = $this->mockUser('bob');
        $this->mockGroups($user, []);
        $this->shareMapper->method('findUserShare')->willReturn($share);

        $this->assertTrue($this->service->canWrite(1, 'bob'));
    }

    public function testCanWriteWithReadOnlyShareReturnsFalse(): void {
        $list  = $this->makeList(1, 'alice');
        $share = $this->makeShare(ShareEntity::PERM_READ);

        $this->listMapper->method('findById')->willReturn($list);
        $user = $this->mockUser('bob');
        $this->mockGroups($user, []);
        $this->shareMapper->method('findUserShare')->willReturn($share);

        $this->assertFalse($this->service->canWrite(1, 'bob'));
    }

    public function testCanWriteNoShareReturnsFalse(): void {
        $list = $this->makeList(1, 'alice');

        $this->listMapper->method('findById')->willReturn($list);
        $user = $this->mockUser('bob');
        $this->mockGroups($user, []);
        $this->shareMapper->method('findUserShare')->willReturn(null);

        $this->assertFalse($this->service->canWrite(1, 'bob'));
    }

    public function testCanWriteListNotFoundReturnsFalse(): void {
        $this->listMapper->method('findById')
            ->willThrowException(new NotFoundException('not found'));

        $this->assertFalse($this->service->canWrite(99, 'alice'));
    }

    public function testCanWriteUnknownUserHasNoGroups(): void {
        $list = $this->makeList(1, 'alice');

        $this->listMapper->method('findById')->willReturn($list);
        $this->userManager->method('get')->with('ghost')->willReturn(null);
        $this->shareMapper->method('findUserShare')
            ->with(1, 'ghost', [])
            ->willReturn(null);

        $this->assertFalse($this->service->canWrite(1, 'ghost'));
    }
}
