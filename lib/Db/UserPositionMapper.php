<?php

declare(strict_types=1);

namespace OCA\Lists\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/** @template-extends QBMapper<UserPositionEntity> */
class UserPositionMapper extends QBMapper {
    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'lists_user_positions', UserPositionEntity::class);
    }

    /**
     * Insert a row only if (uid, list_id) doesn't already exist.
     * Used on list create (owner) and direct user-share create (recipient).
     */
    public function ensureRow(string $uid, int $listId, ?int $position): void {
        if ($this->existsFor($uid, $listId)) {
            return;
        }

        $qb = $this->db->getQueryBuilder();
        $qb->insert($this->getTableName())
            ->values([
                'uid'      => $qb->createNamedParameter($uid),
                'list_id'  => $qb->createNamedParameter($listId, IQueryBuilder::PARAM_INT),
                'position' => $position === null
                    ? $qb->createNamedParameter(null, IQueryBuilder::PARAM_NULL)
                    : $qb->createNamedParameter($position, IQueryBuilder::PARAM_INT),
            ]);
        $qb->executeStatement();
    }

    private function existsFor(string $uid, int $listId): bool {
        $qb = $this->db->getQueryBuilder();
        $qb->select('id')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('uid', $qb->createNamedParameter($uid)))
            ->andWhere($qb->expr()->eq('list_id', $qb->createNamedParameter($listId, IQueryBuilder::PARAM_INT)))
            ->setMaxResults(1);
        return $qb->executeQuery()->fetchOne() !== false;
    }

    /**
     * Returns the current max position for a user across their rows,
     * or null if the user has no row yet.
     */
    public function maxPositionFor(string $uid): ?int {
        $qb = $this->db->getQueryBuilder();
        $qb->select($qb->func()->max('position'))
            ->from($this->getTableName())
            ->where($qb->expr()->eq('uid', $qb->createNamedParameter($uid)));
        $max = $qb->executeQuery()->fetchOne();
        return $max === false || $max === null ? null : (int) $max;
    }

    /**
     * Bulk-write positions for a user. UPSERTs each (uid, list_id) row.
     *
     * @param string $uid
     * @param array<int,int> $positions  map of listId => position (0-based)
     */
    public function setPositions(string $uid, array $positions): void {
        foreach ($positions as $listId => $position) {
            if ($this->existsFor($uid, (int) $listId)) {
                $qb = $this->db->getQueryBuilder();
                $qb->update($this->getTableName())
                    ->set('position', $qb->createNamedParameter($position, IQueryBuilder::PARAM_INT))
                    ->where($qb->expr()->eq('uid', $qb->createNamedParameter($uid)))
                    ->andWhere($qb->expr()->eq('list_id', $qb->createNamedParameter((int) $listId, IQueryBuilder::PARAM_INT)));
                $qb->executeStatement();
            } else {
                $this->ensureRow($uid, (int) $listId, $position);
            }
        }
    }

    /** Delete the row for one (uid, list_id) pair. No-op if absent. */
    public function deleteFor(string $uid, int $listId): void {
        $qb = $this->db->getQueryBuilder();
        $qb->delete($this->getTableName())
            ->where($qb->expr()->eq('uid', $qb->createNamedParameter($uid)))
            ->andWhere($qb->expr()->eq('list_id', $qb->createNamedParameter($listId, IQueryBuilder::PARAM_INT)));
        $qb->executeStatement();
    }

    /** Delete all rows for a list (used when the list is destroyed). */
    public function deleteAllForList(int $listId): void {
        $qb = $this->db->getQueryBuilder();
        $qb->delete($this->getTableName())
            ->where($qb->expr()->eq('list_id', $qb->createNamedParameter($listId, IQueryBuilder::PARAM_INT)));
        $qb->executeStatement();
    }
}
