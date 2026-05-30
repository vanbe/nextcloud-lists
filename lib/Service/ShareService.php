<?php

declare(strict_types=1);

namespace OCA\Lists\Service;

use OCA\Lists\Db\ShareEntity;
use OCA\Lists\Db\ShareMapper;
use OCA\Lists\Db\UserPositionMapper;
use OCA\Lists\Exception\ForbiddenException;
use OCA\Lists\Exception\NotFoundException;

class ShareService {
    public function __construct(
        private readonly ShareMapper        $shareMapper,
        private readonly UserPositionMapper $positionMapper,
        private readonly PermissionService  $permissionService,
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

        $created = $this->shareMapper->insert($entity);

        // Direct user-share: give the recipient a row with NULL position so they can reorder.
        // Group share: no per-user row (recipients aren't enumerated; falls back to default sort).
        if ($shareType === ShareEntity::TYPE_USER) {
            $this->positionMapper->ensureRow($shareWith, $listId, null);
        }

        return $created;
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

        // Mirror: only direct user-shares had a per-user position row.
        if ($entity->getShareType() === ShareEntity::TYPE_USER) {
            $this->positionMapper->deleteFor($entity->getShareWith(), $listId);
        }
    }
}
