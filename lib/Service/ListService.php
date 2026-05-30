<?php

declare(strict_types=1);

namespace OCA\Lists\Service;

use OCA\Lists\Db\CategoryMapper;
use OCA\Lists\Db\ItemMapper;
use OCA\Lists\Db\ListEntity;
use OCA\Lists\Db\ListMapper;
use OCA\Lists\Db\ShareMapper;
use OCA\Lists\Db\UserPositionMapper;
use OCA\Lists\Exception\ForbiddenException;
use OCA\Lists\Exception\NotFoundException;
use OCP\IGroupManager;
use OCP\IUserManager;

class ListService {
    public function __construct(
        private readonly ListMapper         $mapper,
        private readonly ItemMapper         $itemMapper,
        private readonly ShareMapper        $shareMapper,
        private readonly CategoryMapper     $categoryMapper,
        private readonly UserPositionMapper $positionMapper,
        private readonly IGroupManager      $groupManager,
        private readonly IUserManager       $userManager,
    ) {}

    /** @return ListEntity[] owned + shared, with activeItemCount populated */
    public function findAll(string $uid): array {
        $groups = $this->getUserGroups($uid);
        $lists  = $this->mapper->findAllForUser($uid, $groups);

        if (!empty($lists)) {
            $ids    = array_map(fn($l) => $l->getId(), $lists);
            $counts = $this->itemMapper->countUncheckedByLists($ids);
            foreach ($lists as $list) {
                $list->setActiveItemCount($counts[$list->getId()] ?? 0);
            }
        }

        return $lists;
    }

    /** @throws NotFoundException|ForbiddenException */
    public function find(int $id, string $uid): ListEntity {
        return $this->mapper->find($id, $uid);
    }

    public function create(string $uid, string $name, ?string $description = null, ?string $icon = null, int $hasQuantities = 0): ListEntity {
        $entity = new ListEntity();
        $entity->setUid($uid);
        $entity->setName($name);
        $entity->setDescription($description);
        $entity->setIcon($icon);
        $entity->setHasQuantities($hasQuantities);

        $created = $this->mapper->insert($entity);

        // Owner sees their new list at the bottom of their personal order.
        $max = $this->positionMapper->maxPositionFor($uid);
        $this->positionMapper->ensureRow($uid, $created->getId(), ($max ?? -1) + 1);

        $created->setUserPosition(($max ?? -1) + 1);
        return $created;
    }

    /**
     * @throws NotFoundException
     * @throws ForbiddenException
     */
    public function update(int $id, string $uid, ?string $name = null, ?string $description = null, ?string $icon = null, int|false $hasQuantities = false): ListEntity {
        $entity = $this->mapper->find($id, $uid);

        if ($entity->getUid() !== $uid) {
            throw new ForbiddenException();
        }

        if ($name !== null) {
            $entity->setName($name);
        }
        if ($description !== null) {
            $entity->setDescription($description);
        }
        if ($icon !== null) {
            $entity->setIcon($icon);
        }
        if ($hasQuantities !== false) {
            $entity->setHasQuantities($hasQuantities);
        }

        return $this->mapper->update($entity);
    }

    /**
     * Save the user's personal ordering of any lists they can see.
     * IDs not visible to the user are silently dropped.
     *
     * @param int[] $orderedIds  list IDs in the desired display order
     */
    public function reorder(string $uid, array $orderedIds): void {
        $groups  = $this->getUserGroups($uid);
        $visible = $this->mapper->findAllForUser($uid, $groups);
        $visibleIds = array_flip(array_map(fn($l) => $l->getId(), $visible));

        $positions = [];
        $pos = 0;
        foreach ($orderedIds as $id) {
            $id = (int) $id;
            if (isset($visibleIds[$id])) {
                $positions[$id] = $pos++;
            }
        }

        if (!empty($positions)) {
            $this->positionMapper->setPositions($uid, $positions);
        }
    }

    /**
     * @throws NotFoundException
     * @throws ForbiddenException
     */
    public function delete(int $id, string $uid): void {
        $entity = $this->mapper->find($id, $uid);

        if ($entity->getUid() !== $uid) {
            throw new ForbiddenException();
        }

        $this->itemMapper->deleteAllForList($id);
        $this->categoryMapper->deleteAllForList($id);
        $this->shareMapper->deleteAllForList($id);
        $this->positionMapper->deleteAllForList($id);
        $this->mapper->delete($entity);
    }

    /** @return string[] */
    private function getUserGroups(string $uid): array {
        $user = $this->userManager->get($uid);
        if ($user === null) {
            return [];
        }
        return $this->groupManager->getUserGroupIds($user);
    }
}
