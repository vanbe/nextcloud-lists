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
    public function create(int $listId, string $uid, string $name, string $icon = '', ?int $position = null): CategoryEntity {
        if (!$this->permissions->canWrite($listId, $uid)) {
            throw new ForbiddenException();
        }
        // Default position: end of the list (max + 1) so new categories don't tie at 0.
        if ($position === null) {
            $max = $this->mapper->maxPositionFor($listId);
            $position = ($max ?? -1) + 1;
        }
        $entity = new CategoryEntity();
        $entity->setListId($listId);
        $entity->setName($name);
        $entity->setIcon($icon);
        $entity->setPosition($position);
        return $this->mapper->insert($entity);
    }

    /**
     * Reorder categories within a list. IDs not belonging to the list are silently dropped.
     *
     * @param int[] $orderedIds  category IDs in the desired display order
     * @throws NotFoundException|ForbiddenException
     */
    public function reorder(int $listId, string $uid, array $orderedIds): void {
        if (!$this->permissions->canWrite($listId, $uid)) {
            throw new ForbiddenException();
        }
        $existing = $this->mapper->findAll($listId);
        $valid = array_flip(array_map(fn($c) => $c->getId(), $existing));

        $positions = [];
        $pos = 0;
        foreach ($orderedIds as $id) {
            $id = (int) $id;
            if (isset($valid[$id])) {
                $positions[$id] = $pos++;
            }
        }

        if (!empty($positions)) {
            $this->mapper->updatePositions($listId, $positions);
        }
    }

    /** @throws NotFoundException|ForbiddenException */
    public function update(int $id, int $listId, string $uid, ?string $name = null, ?string $icon = null): CategoryEntity {
        if (!$this->permissions->canWrite($listId, $uid)) {
            throw new ForbiddenException();
        }
        $entity = $this->mapper->find($id, $listId);
        if ($name !== null) {
            $entity->setName($name);
        }
        if ($icon !== null) {
            $entity->setIcon($icon);
        }
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
