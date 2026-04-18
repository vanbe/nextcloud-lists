<?php

declare(strict_types=1);

namespace OCA\Lists\Service;

use OCA\Lists\Db\ListEntity;
use OCA\Lists\Db\ListMapper;
use OCA\Lists\Db\ShareMapper;
use OCA\Lists\Exception\ForbiddenException;
use OCA\Lists\Exception\NotFoundException;
use OCP\IGroupManager;
use OCP\IUserManager;

class PermissionService {
    public function __construct(
        private readonly ListMapper   $listMapper,
        private readonly ShareMapper  $shareMapper,
        private readonly IGroupManager $groupManager,
        private readonly IUserManager  $userManager,
    ) {}

    /**
     * Returns the list if the user is the owner or has at least read access via a share.
     *
     * @throws NotFoundException
     * @throws ForbiddenException
     */
    public function getAccessibleList(int $listId, string $uid): ListEntity {
        $list = $this->listMapper->findById($listId);

        if ($list->getUid() === $uid) {
            return $list;
        }

        $groups = $this->getUserGroups($uid);
        $share  = $this->shareMapper->findUserShare($listId, $uid, $groups);

        if ($share === null) {
            throw new ForbiddenException();
        }

        return $list;
    }

    /**
     * Returns the list only if the user is the owner.
     *
     * @throws NotFoundException
     * @throws ForbiddenException
     */
    public function requireOwner(int $listId, string $uid): ListEntity {
        $list = $this->listMapper->findById($listId);

        if ($list->getUid() !== $uid) {
            throw new ForbiddenException();
        }

        return $list;
    }

    /**
     * Returns true if the user can write to the list (owner or shared with write permission).
     */
    public function canWrite(int $listId, string $uid): bool {
        try {
            $list = $this->listMapper->findById($listId);
        } catch (NotFoundException) {
            return false;
        }

        if ($list->getUid() === $uid) {
            return true;
        }

        $groups = $this->getUserGroups($uid);
        $share  = $this->shareMapper->findUserShare($listId, $uid, $groups);

        return $share !== null && $share->canWrite();
    }

    /** @return string[] group IDs */
    private function getUserGroups(string $uid): array {
        $user = $this->userManager->get($uid);
        if ($user === null) {
            return [];
        }
        return $this->groupManager->getUserGroupIds($user);
    }
}
