<?php

declare(strict_types=1);

namespace OCA\Lists\Db;

use OCA\Lists\Exception\NotFoundException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/** @template-extends QBMapper<ListEntity> */
class ListMapper extends QBMapper {
    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'lists', ListEntity::class);
    }

    /** @throws NotFoundException */
    public function find(int $id, string $uid): ListEntity {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
            ->andWhere($qb->expr()->eq('uid', $qb->createNamedParameter($uid)));

        try {
            return $this->findEntity($qb);
        } catch (DoesNotExistException) {
            throw new NotFoundException("List $id not found");
        }
    }

    /** @return ListEntity[] */
    public function findAll(string $uid): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('uid', $qb->createNamedParameter($uid)))
            ->orderBy('name', 'ASC');

        return $this->findEntities($qb);
    }

    public function insert(\OCP\AppFramework\Db\Entity $entity): \OCP\AppFramework\Db\Entity {
        assert($entity instanceof ListEntity);
        $now = time();
        if ($entity->getCreatedAt() === 0) {
            $entity->setCreatedAt($now);
        }
        $entity->setUpdatedAt($now);
        return parent::insert($entity);
    }

    public function update(\OCP\AppFramework\Db\Entity $entity): \OCP\AppFramework\Db\Entity {
        assert($entity instanceof ListEntity);
        $entity->setUpdatedAt(time());
        return parent::update($entity);
    }
}
