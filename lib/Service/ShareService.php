<?php

declare(strict_types=1);

namespace OCA\Lists\Service;

use OCA\Lists\Db\ShareEntity;
use OCA\Lists\Db\ShareMapper;
use OCA\Lists\Exception\ForbiddenException;
use OCA\Lists\Exception\NotFoundException;

class ShareService {
    public function __construct(
        private readonly ShareMapper       $shareMapper,
        private readonly PermissionService $permissionService,
    ) {}

    /** @return ShareEntity[] */
    public function findAll(int $listId, string $uid): array {
        // Only the owner can see the full share list
        $this->permissionService->requireOwner($listId, $uid);
        return $this->shareMapper->findAll($listId);
    }

    /** @throws NotFoundException|ForbiddenException */
    public function create(int $listId, string $uid, int $shareType, string $shareWith, int $permissions = ShareEntity::PERM_READ): ShareEntity {
        $this->permissionService->requireOwner($listId, $uid);

        $entity = new ShareEntity();
        $entity->setListId($listId);
        $entity->setShareType($shareType);
        $entity->setShareWith($shareWith);
        $entity->setPermissions($permissions);

        return $this->shareMapper->insert($entity);
    }

    /** @throws NotFoundException|ForbiddenException */
    public function update(int $id, int $listId, string $uid, int $permissions): ShareEntity {
        $this->permissionService->requireOwner($listId, $uid);
        $entity = $this->shareMapper->find($id, $listId);
        $entity->setPermissions($permissions);
        return $this->shareMapper->update($entity);
    }

    /** @throws NotFoundException|ForbiddenException */
    public function delete(int $id, int $listId, string $uid): void {
        $this->permissionService->requireOwner($listId, $uid);
        $entity = $this->shareMapper->find($id, $listId);
        $this->shareMapper->delete($entity);
    }
}
