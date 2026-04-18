<?php

declare(strict_types=1);

namespace OCA\Lists\Service;

use OCA\Lists\Db\ItemEntity;
use OCA\Lists\Db\ItemMapper;
use OCA\Lists\Exception\ForbiddenException;
use OCA\Lists\Exception\NotFoundException;

class ItemService {
    public function __construct(
        private readonly ItemMapper $itemMapper,
        private readonly ListService $listService,
    ) {}

    /** @return ItemEntity[] */
    public function findAll(int $listId, string $uid): array {
        // Verify the list belongs to this user (throws NotFoundException/ForbiddenException)
        $this->listService->find($listId, $uid);
        return $this->itemMapper->findAll($listId);
    }

    /** @throws NotFoundException|ForbiddenException */
    public function create(int $listId, string $uid, string $title, ?string $description = null): ItemEntity {
        $this->listService->find($listId, $uid);

        $entity = new ItemEntity();
        $entity->setListId($listId);
        $entity->setTitle($title);
        $entity->setDescription($description);
        $entity->setChecked(0);
        $entity->setPosition(0);

        return $this->itemMapper->insert($entity);
    }

    /** @throws NotFoundException|ForbiddenException */
    public function update(int $id, int $listId, string $uid, ?string $title = null, ?string $description = null): ItemEntity {
        $this->listService->find($listId, $uid);
        $entity = $this->itemMapper->find($id, $listId);

        if ($title !== null) {
            $entity->setTitle($title);
        }
        if ($description !== null) {
            $entity->setDescription($description);
        }

        return $this->itemMapper->update($entity);
    }

    /** @throws NotFoundException|ForbiddenException */
    public function toggle(int $id, int $listId, string $uid): ItemEntity {
        $this->listService->find($listId, $uid);
        $entity = $this->itemMapper->find($id, $listId);

        $nowChecked = $entity->isChecked() ? 0 : 1;
        $entity->setChecked($nowChecked);
        $entity->setCheckedAt($nowChecked === 1 ? time() : null);

        return $this->itemMapper->update($entity);
    }

    /** @throws NotFoundException|ForbiddenException */
    public function delete(int $id, int $listId, string $uid): void {
        $this->listService->find($listId, $uid);
        $entity = $this->itemMapper->find($id, $listId);
        $this->itemMapper->delete($entity);
    }
}
