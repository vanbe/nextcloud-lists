<?php

declare(strict_types=1);

namespace OCA\Lists\Service;

use OCA\Lists\Db\CategoryEntity;
use OCA\Lists\Db\CategoryMapper;
use OCA\Lists\Db\ItemMapper;
use OCA\Lists\Exception\ForbiddenException;
use OCA\Lists\Exception\NotFoundException;

class CategoryService {
    public function __construct(
        private readonly CategoryMapper    $mapper,
        private readonly ItemMapper        $itemMapper,
        private readonly PermissionService $permissions,
    ) {}

    /** @return CategoryEntity[] */
    public function findAll(int $listId, string $uid): array {
        $this->permissions->getAccessibleList($listId, $uid);
        return $this->mapper->findAll($listId);
    }

    /** @throws NotFoundException|ForbiddenException */
    public function create(int $listId, string $uid, string $name, int $position = 0): CategoryEntity {
        if (!$this->permissions->canWrite($listId, $uid)) {
            throw new ForbiddenException();
        }
        $entity = new CategoryEntity();
        $entity->setListId($listId);
        $entity->setName($name);
        $entity->setPosition($position);
        return $this->mapper->insert($entity);
    }

    /** @throws NotFoundException|ForbiddenException */
    public function update(int $id, int $listId, string $uid, string $name): CategoryEntity {
        if (!$this->permissions->canWrite($listId, $uid)) {
            throw new ForbiddenException();
        }
        $entity = $this->mapper->find($id, $listId);
        $entity->setName($name);
        return $this->mapper->update($entity);
    }

    /** @throws NotFoundException|ForbiddenException */
    public function delete(int $id, int $listId, string $uid): void {
        if (!$this->permissions->canWrite($listId, $uid)) {
            throw new ForbiddenException();
        }
        $entity = $this->mapper->find($id, $listId);
        // Unassign items before deleting the category
        $this->itemMapper->nullifyCategory($id);
        $this->mapper->delete($entity);
    }

    public function deleteAllForList(int $listId): void {
        $this->mapper->deleteAllForList($listId);
    }
}
