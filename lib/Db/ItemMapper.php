<?php

declare(strict_types=1);

namespace OCA\Lists\Db;

use OCA\Lists\Exception\NotFoundException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/** @template-extends QBMapper<ItemEntity> */
class ItemMapper extends QBMapper {
    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'lists_items', ItemEntity::class);
    }

    /** @throws NotFoundException */
    public function find(int $id, int $listId): ItemEntity {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
            ->andWhere($qb->expr()->eq('list_id', $qb->createNamedParameter($listId, IQueryBuilder::PARAM_INT)));

        try {
            return $this->findEntity($qb);
        } catch (DoesNotExistException) {
            throw new NotFoundException("Item $id not found");
        }
    }

    /** @return ItemEntity[] */
    public function findAll(int $listId): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('list_id', $qb->createNamedParameter($listId, IQueryBuilder::PARAM_INT)))
            ->orderBy('checked', 'ASC')
            ->addOrderBy('position', 'ASC')
            ->addOrderBy('created_at', 'ASC');

        return $this->findEntities($qb);
    }

    /**
     * Suggest items whose title starts with $q (case-insensitive), max 5.
     * Unchecked items come first, then most-recently-checked.
     *
     * @return ItemEntity[]
     */
    public function suggest(int $listId, string $q): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('list_id', $qb->createNamedParameter($listId, IQueryBuilder::PARAM_INT)))
            ->andWhere($qb->expr()->like(
                $qb->createFunction('LOWER(title)'),
                $qb->createNamedParameter(mb_strtolower($q) . '%')
            ))
            ->orderBy('checked', 'ASC')
            ->addOrderBy('checked_at', 'DESC')
            ->setMaxResults(5);

        return $this->findEntities($qb);
    }

    /**
     * Returns [listId => uncheckedCount] for the given list IDs in a single query.
     *
     * @param int[] $listIds
     * @return array<int,int>
     */
    public function countUncheckedByLists(array $listIds): array {
        if (empty($listIds)) {
            return [];
        }
        $qb = $this->db->getQueryBuilder();
        $params = array_map(
            fn($id) => $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT),
            $listIds
        );
        $qb->select('list_id')
            ->selectAlias($qb->createFunction('COUNT(*)'), 'cnt')
            ->from($this->getTableName())
            ->where($qb->expr()->in('list_id', $params))
            ->andWhere($qb->expr()->eq('checked', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)))
            ->groupBy('list_id');

        $result = $qb->executeQuery();
        $counts = [];
        while ($row = $result->fetch()) {
            $counts[(int) $row['list_id']] = (int) $row['cnt'];
        }
        $result->closeCursor();
        return $counts;
    }

    public function nullifyCategory(int $categoryId): void {
        $qb = $this->db->getQueryBuilder();
        $qb->update($this->getTableName())
            ->set('category_id', $qb->createNamedParameter(null, IQueryBuilder::PARAM_NULL))
            ->where($qb->expr()->eq('category_id', $qb->createNamedParameter($categoryId, IQueryBuilder::PARAM_INT)));
        $qb->executeStatement();
    }

    public function deleteAllForList(int $listId): void {
        $qb = $this->db->getQueryBuilder();
        $qb->delete($this->getTableName())
            ->where($qb->expr()->eq('list_id', $qb->createNamedParameter($listId, IQueryBuilder::PARAM_INT)));
        $qb->executeStatement();
    }

    public function insert(\OCP\AppFramework\Db\Entity $entity): \OCP\AppFramework\Db\Entity {
        assert($entity instanceof ItemEntity);
        $now = time();
        if ($entity->getCreatedAt() === 0) {
            $entity->setCreatedAt($now);
        }
        $entity->setUpdatedAt($now);
        return parent::insert($entity);
    }

    public function update(\OCP\AppFramework\Db\Entity $entity): \OCP\AppFramework\Db\Entity {
        assert($entity instanceof ItemEntity);
        $entity->setUpdatedAt(time());
        return parent::update($entity);
    }
}
