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
     * Returns all lists owned by or shared with the user (directly or via groups),
     * sorted by the user's personal ordering. Lists with no per-user position row
     * (typically newly-shared group lists) sort to the end by created_at.
     *
     * @param string[] $groups
     * @return ListEntity[]  with userPosition virtual field populated
     */
    public function findAllForUser(string $uid, array $groups): array {
        $qb = $this->db->getQueryBuilder();
        $l  = $this->getTableName();
        $s  = 'lists_shares';
        $p  = 'lists_user_positions';

        // Access JOIN: list is visible if owned, or matched by a user-share, or a group-share for one of the user's groups.
        if (empty($groups)) {
            $shareJoin = $qb->expr()->andX(
                $qb->expr()->eq('s.list_id', 'l.id'),
                $qb->expr()->eq('s.share_type', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)),
                $qb->expr()->eq('s.share_with', $qb->createNamedParameter($uid))
            );
        } else {
            $groupParams = array_map(
                fn($g) => $qb->createNamedParameter($g),
                $groups
            );
            $shareJoin = $qb->expr()->andX(
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

        // Per-user position JOIN — separate parameter for uid to avoid sharing the param expression.
        $positionJoin = $qb->expr()->andX(
            $qb->expr()->eq('p.list_id', 'l.id'),
            $qb->expr()->eq('p.uid', $qb->createNamedParameter($uid))
        );

        $qb->selectAlias('l.id', 'id')
            ->selectAlias('l.uid', 'uid')
            ->selectAlias('l.name', 'name')
            ->selectAlias('l.description', 'description')
            ->selectAlias('l.icon', 'icon')
            ->selectAlias('l.has_quantities', 'has_quantities')
            ->selectAlias('l.created_at', 'created_at')
            ->selectAlias('l.updated_at', 'updated_at')
            ->selectAlias('p.position', 'user_position')
            ->from($l, 'l')
            ->leftJoin('l', $s, 's', $shareJoin)
            ->leftJoin('l', $p, 'p', $positionJoin)
            ->where($qb->expr()->orX(
                $qb->expr()->eq('l.uid', $qb->createNamedParameter($uid)),
                $qb->expr()->isNotNull('s.id')
            ))
            ->groupBy('l.id', 'p.position')
            // NULL positions sort to the end (portable: COALESCE with a large sentinel).
            ->orderBy($qb->createFunction('COALESCE(p.position, 2147483647)'), 'ASC')
            ->addOrderBy('l.created_at', 'ASC')
            ->addOrderBy('l.id', 'ASC');

        $result = $qb->executeQuery();
        $entities = [];
        while ($row = $result->fetch()) {
            // userPosition is a virtual (private) field on ListEntity, not a column
            // on `lists` — Entity::fromRow would choke on it, so strip it first.
            $userPos = $row['user_position'] ?? null;
            unset($row['user_position']);
            $entity = ListEntity::fromRow($row);
            $entity->setUserPosition($userPos !== null ? (int) $userPos : null);
            $entities[] = $entity;
        }
        $result->closeCursor();

        return $entities;
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
