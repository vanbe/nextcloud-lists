<?php

declare(strict_types=1);

namespace OCA\Lists\Service;

use OCA\Lists\Db\ListEntity;
use OCA\Lists\Db\ListMapper;
use OCA\Lists\Exception\ForbiddenException;
use OCA\Lists\Exception\NotFoundException;

class ListService {
    public function __construct(private readonly ListMapper $mapper) {}

    /** @return ListEntity[] */
    public function findAll(string $uid): array {
        return $this->mapper->findAll($uid);
    }

    /** @throws NotFoundException */
    public function find(int $id, string $uid): ListEntity {
        return $this->mapper->find($id, $uid);
    }

    public function create(string $uid, string $name, ?string $description = null, ?string $icon = null): ListEntity {
        $entity = new ListEntity();
        $entity->setUid($uid);
        $entity->setName($name);
        $entity->setDescription($description);
        $entity->setIcon($icon);

        return $this->mapper->insert($entity);
    }

    /**
     * @throws NotFoundException
     * @throws ForbiddenException
     */
    public function update(int $id, string $uid, ?string $name = null, ?string $description = null, ?string $icon = null): ListEntity {
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

        return $this->mapper->update($entity);
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

        $this->mapper->delete($entity);
    }
}
