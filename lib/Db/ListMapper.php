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

    /** @throws NotFoundException */
    public function findById(int $id): ListEntity {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));

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

    /**
     * Returns all lists owned by or shared with the user (directly or via groups).
     * Uses a LEFT JOIN on lists_shares to avoid subquery cross-table reference issues.
     *
     * @param string[] $groups
     * @return ListEntity[]
     */
    public function findAllForUser(string $uid, array $groups): array {
        $qb = $this->db->getQueryBuilder();
        $l  = $this->getTableName();
        $s  = 'lists_shares';

        // Build the JOIN condition for matching shares
        if (empty($groups)) {
            $joinCondition = $qb->expr()->andX(
                $qb->expr()->eq('s.list_id', 'l.id'),
                $qb->expr()->eq('s.share_type', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)),
                $qb->expr()->eq('s.share_with', $qb->createNamedParameter($uid))
            );
        } else {
            $groupParams = array_map(
                fn($g) => $qb->createNamedParameter($g),
                $groups
            );
            $joinCondition = $qb->expr()->andX(
                $qb->expr()->eq('s.list_id', 'l.id'),
                $qb->expr()->orX(
                    $qb->expr()->andX(
                        $qb->expr()->eq('s.share_type', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)),
                        $qb->expr()->eq('s.share_with', $qb->createNamedParameter($uid))
                    ),
                    $qb->expr()->andX(
                        $qb->expr()->eq('s.share_type', $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT)),
                        $qb->expr()->in('s.share_with', $groupParams)
                    )
                )
            );
        }

        $qb->selectDistinct('l.*')
            ->from($l, 'l')
            ->leftJoin('l', $s, 's', $joinCondition)
            ->where($qb->expr()->orX(
                $qb->expr()->eq('l.uid', $qb->createNamedParameter($uid)),
                $qb->expr()->isNotNull('s.id')
            ))
            ->orderBy('l.name', 'ASC');

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
