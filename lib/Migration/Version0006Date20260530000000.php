<?php

declare(strict_types=1);

namespace OCA\Lists\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Moves list ordering from per-list (lists.position, owner-only) to per-user
 * (lists_user_positions). Each user — owner, direct sharee, or group member —
 * gets their own ordering of the lists they can see.
 *
 * Group-share recipients are NOT seeded here (groups aren't enumerated eagerly);
 * the read query falls back to created_at for rows with no position.
 *
 * lists.position is left in place (dead column) — drop in a follow-up if desired.
 */
class Version0006Date20260530000000 extends SimpleMigrationStep {
    public function __construct(private readonly IDBConnection $db) {}

    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('lists_user_positions')) {
            $table = $schema->createTable('lists_user_positions');
            $table->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true, 'unsigned' => true]);
            $table->addColumn('uid', Types::STRING, ['notnull' => true, 'length' => 64]);
            $table->addColumn('list_id', Types::BIGINT, ['notnull' => true, 'unsigned' => true]);
            // NULL = user has not explicitly ordered this list yet (default sort fallback applies)
            $table->addColumn('position', Types::INTEGER, ['notnull' => false, 'default' => null]);
            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['uid', 'list_id'], 'lists_user_pos_unique_idx');
            $table->addIndex(['uid', 'position'], 'lists_user_pos_lookup_idx');
        }

        return $schema;
    }

    public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
        // Seed owner positions from the legacy lists.position column.
        $qb = $this->db->getQueryBuilder();
        $qb->select('id', 'uid', 'position')
            ->from('lists');
        $rows = $qb->executeQuery()->fetchAll();

        foreach ($rows as $row) {
            $this->upsert((string) $row['uid'], (int) $row['id'], (int) $row['position']);
        }

        // Seed direct user-shares with NULL position so each sharee can order them.
        $qb = $this->db->getQueryBuilder();
        $qb->select('list_id', 'share_with')
            ->from('lists_shares')
            ->where($qb->expr()->eq('share_type', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)));
        $shares = $qb->executeQuery()->fetchAll();

        foreach ($shares as $row) {
            $this->upsert((string) $row['share_with'], (int) $row['list_id'], null);
        }
    }

    private function upsert(string $uid, int $listId, ?int $position): void {
        $check = $this->db->getQueryBuilder();
        $check->select('id')
            ->from('lists_user_positions')
            ->where($check->expr()->eq('uid', $check->createNamedParameter($uid)))
            ->andWhere($check->expr()->eq('list_id', $check->createNamedParameter($listId, IQueryBuilder::PARAM_INT)))
            ->setMaxResults(1);
        if ($check->executeQuery()->fetchOne() !== false) {
            return; // already seeded, skip
        }

        $insert = $this->db->getQueryBuilder();
        $insert->insert('lists_user_positions')
            ->values([
                'uid'      => $insert->createNamedParameter($uid),
                'list_id'  => $insert->createNamedParameter($listId, IQueryBuilder::PARAM_INT),
                'position' => $position === null
                    ? $insert->createNamedParameter(null, IQueryBuilder::PARAM_NULL)
                    : $insert->createNamedParameter($position, IQueryBuilder::PARAM_INT),
            ]);
        $insert->executeStatement();
    }
}
