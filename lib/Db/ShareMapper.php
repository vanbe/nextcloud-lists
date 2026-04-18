<?php

declare(strict_types=1);

namespace OCA\Lists\Db;

use OCA\Lists\Exception\NotFoundException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/** @template-extends QBMapper<ShareEntity> */
class ShareMapper extends QBMapper {
    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'lists_shares', ShareEntity::class);
    }

    /** @throws NotFoundException */
    public function find(int $id, int $listId): ShareEntity {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
            ->andWhere($qb->expr()->eq('list_id', $qb->createNamedParameter($listId, IQueryBuilder::PARAM_INT)));

        try {
            return $this->findEntity($qb);
        } catch (DoesNotExistException) {
            throw new NotFoundException("Share $id not found");
        }
    }

    /** @return ShareEntity[] */
    public function findAll(int $listId): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('list_id', $qb->createNamedParameter($listId, IQueryBuilder::PARAM_INT)))
            ->orderBy('created_at', 'ASC');

        return $this->findEntities($qb);
    }

    /**
     * Check if a user (directly or via groups) has access to a list.
     * Returns the share entity if found, null otherwise.
     *
     * @param string[] $groups
     */
    public function findUserShare(int $listId, string $uid, array $groups): ?ShareEntity {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('list_id', $qb->createNamedParameter($listId, IQueryBuilder::PARAM_INT)));

        if (empty($groups)) {
            $qb->andWhere($qb->expr()->andX(
                $qb->expr()->eq('share_type', $qb->createNamedParameter(ShareEntity::TYPE_USER, IQueryBuilder::PARAM_INT)),
                $qb->expr()->eq('share_with', $qb->createNamedParameter($uid))
            ));
        } else {
            $groupParams = array_map(
                fn($g) => $qb->createNamedParameter($g),
                $groups
            );
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->andX(
                    $qb->expr()->eq('share_type', $qb->createNamedParameter(ShareEntity::TYPE_USER, IQueryBuilder::PARAM_INT)),
                    $qb->expr()->eq('share_with', $qb->createNamedParameter($uid))
                ),
                $qb->expr()->andX(
                    $qb->expr()->eq('share_type', $qb->createNamedParameter(ShareEntity::TYPE_GROUP, IQueryBuilder::PARAM_INT)),
                    $qb->expr()->in('share_with', $groupParams)
                )
            ));
        }

        $qb->setMaxResults(1);

        try {
            return $this->findEntity($qb);
        } catch (DoesNotExistException) {
            return null;
        }
    }

    public function deleteAllForList(int $listId): void {
        $qb = $this->db->getQueryBuilder();
        $qb->delete($this->getTableName())
            ->where($qb->expr()->eq('list_id', $qb->createNamedParameter($listId, IQueryBuilder::PARAM_INT)));
        $qb->executeStatement();
    }

    public function insert(\OCP\AppFramework\Db\Entity $entity): \OCP\AppFramework\Db\Entity {
        assert($entity instanceof ShareEntity);
        if ($entity->getCreatedAt() === 0) {
            $entity->setCreatedAt(time());
        }
        return parent::insert($entity);
    }
}
