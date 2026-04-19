<?php

declare(strict_types=1);

namespace OCA\Lists\Service;

use OCA\Lists\Db\ItemEntity;
use OCA\Lists\Db\ItemMapper;
use OCA\Lists\Exception\ForbiddenException;
use OCA\Lists\Exception\NotFoundException;

class ItemService {
    public function __construct(
        private readonly ItemMapper        $itemMapper,
        private readonly PermissionService $permissionService,
    ) {}

    /** @return ItemEntity[] */
    public function findAll(int $listId, string $uid): array {
        $this->permissionService->getAccessibleList($listId, $uid);
        return $this->itemMapper->findAll($listId);
    }

    /**
     * @return ItemEntity[]
     * @throws NotFoundException|ForbiddenException
     */
    public function suggest(int $listId, string $uid, string $q): array {
        $this->permissionService->getAccessibleList($listId, $uid);
        return $this->itemMapper->suggest($listId, $q);
    }

    /** @throws NotFoundException|ForbiddenException */
    public function create(int $listId, string $uid, string $title, ?string $description = null, ?int $categoryId = null, ?int $quantity = null): ItemEntity {
        if (!$this->permissionService->canWrite($listId, $uid)) {
            throw new ForbiddenException();
        }

        $entity = new ItemEntity();
        $entity->setListId($listId);
        $entity->setTitle($title);
        $entity->setDescription($description);
        $entity->setChecked(0);
        $entity->setPosition(0);
        $entity->setCategoryId($categoryId);
        $entity->setQuantity($quantity);

        return $this->itemMapper->insert($entity);
    }

    /** @throws NotFoundException|ForbiddenException */
    public function update(int $id, int $listId, string $uid, ?string $title = null, ?string $description = null, int|null|false $categoryId = false, int|null|false $quantity = false): ItemEntity {
        if (!$this->permissionService->canWrite($listId, $uid)) {
            throw new ForbiddenException();
        }
        $entity = $this->itemMapper->find($id, $listId);

        if ($title !== null) {
            $entity->setTitle($title);
        }
        if ($description !== null) {
            $entity->setDescription($description);
        }
        if ($categoryId !== false) {
            $entity->setCategoryId($categoryId);
        }
        // quantity: false = not provided, null = unset (send 0 as sentinel from JS), int = set
        if ($quantity !== false) {
            $entity->setQuantity($quantity === 0 ? null : $quantity);
        }

        return $this->itemMapper->update($entity);
    }

    /** @throws NotFoundException|ForbiddenException */
    public function toggle(int $id, int $listId, string $uid): ItemEntity {
        if (!$this->permissionService->canWrite($listId, $uid)) {
            throw new ForbiddenException();
        }
        $entity = $this->itemMapper->find($id, $listId);

        $nowChecked = $entity->isChecked() ? 0 : 1;
        $entity->setChecked($nowChecked);
        $entity->setCheckedAt($nowChecked === 1 ? time() : null);

        return $this->itemMapper->update($entity);
    }

    /** @throws NotFoundException|ForbiddenException */
    public function delete(int $id, int $listId, string $uid): void {
        if (!$this->permissionService->canWrite($listId, $uid)) {
            throw new ForbiddenException();
        }
        $entity = $this->itemMapper->find($id, $listId);
        $this->itemMapper->delete($entity);
    }
}
