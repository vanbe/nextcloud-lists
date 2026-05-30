<?php

declare(strict_types=1);

namespace OCA\Lists\Db;

use OCA\Lists\Exception\NotFoundException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/** @template-extends QBMapper<CategoryEntity> */
class CategoryMapper extends QBMapper {
    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'lists_categories', CategoryEntity::class);
    }

    /** @throws NotFoundException */
    public function find(int $id, int $listId): CategoryEntity {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
            ->andWhere($qb->expr()->eq('list_id', $qb->createNamedParameter($listId, IQueryBuilder::PARAM_INT)));

        try {
            return $this->findEntity($qb);
        } catch (DoesNotExistException) {
            throw new NotFoundException("Category $id not found");
        }
    }

    /** @return CategoryEntity[] */
    public function findAll(int $listId): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('list_id', $qb->createNamedParameter($listId, IQueryBuilder::PARAM_INT)))
            ->orderBy('position', 'ASC')
            ->addOrderBy('created_at', 'ASC');

        return $this->findEntities($qb);
    }

    /** Return the current max position for a list, or null if no categories. */
    public function maxPositionFor(int $listId): ?int {
        $qb = $this->db->getQueryBuilder();
        $qb->select($qb->func()->max('position'))
            ->from($this->getTableName())
            ->where($qb->expr()->eq('list_id', $qb->createNamedParameter($listId, IQueryBuilder::PARAM_INT)));
        $max = $qb->executeQuery()->fetchOne();
        return $max === false || $max === null ? null : (int) $max;
    }

    /**
     * Bulk-update positions for categories within a list.
     * Caller is responsible for ensuring all IDs belong to $listId.
     *
     * @param int $listId
     * @param array<int,int> $positions  map of categoryId => position
     */
    public function updatePositions(int $listId, array $positions): void {
        foreach ($positions as $id => $position) {
            $qb = $this->db->getQueryBuilder();
            $qb->update($this->getTableName())
                ->set('position', $qb->createNamedParameter($position, IQueryBuilder::PARAM_INT))
                ->where($qb->expr()->eq('id', $qb->createNamedParameter((int) $id, IQueryBuilder::PARAM_INT)))
                ->andWhere($qb->expr()->eq('list_id', $qb->createNamedParameter($listId, IQueryBuilder::PARAM_INT)));
            $qb->executeStatement();
        }
    }

    public function deleteAllForList(int $listId): void {
        $qb = $this->db->getQueryBuilder();
        $qb->delete($this->getTableName())
            ->where($qb->expr()->eq('list_id', $qb->createNamedParameter($listId, IQueryBuilder::PARAM_INT)));
        $qb->executeStatement();
    }

    public function insert(\OCP\AppFramework\Db\Entity $entity): \OCP\AppFramework\Db\Entity {
        assert($entity instanceof CategoryEntity);
        if ($entity->getCreatedAt() === 0) {
            $entity->setCreatedAt(time());
        }
        return parent::insert($entity);
    }
}
