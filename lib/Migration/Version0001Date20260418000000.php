<?php

declare(strict_types=1);

namespace OCA\Lists\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version0001Date20260418000000 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('lists')) {
            $table = $schema->createTable('lists');
            $table->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true, 'unsigned' => true]);
            $table->addColumn('uid', Types::STRING, ['notnull' => true, 'length' => 64]);
            $table->addColumn('name', Types::STRING, ['notnull' => true, 'length' => 255]);
            $table->addColumn('description', Types::TEXT, ['notnull' => false]);
            $table->addColumn('icon', Types::STRING, ['notnull' => false, 'length' => 64]);
            $table->addColumn('created_at', Types::BIGINT, ['notnull' => true, 'unsigned' => true]);
            $table->addColumn('updated_at', Types::BIGINT, ['notnull' => true, 'unsigned' => true]);
            $table->setPrimaryKey(['id']);
            $table->addIndex(['uid'], 'lists_uid_idx');
        }

        if (!$schema->hasTable('lists_items')) {
            $table = $schema->createTable('lists_items');
            $table->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true, 'unsigned' => true]);
            $table->addColumn('list_id', Types::BIGINT, ['notnull' => true, 'unsigned' => true]);
            $table->addColumn('title', Types::STRING, ['notnull' => true, 'length' => 255]);
            $table->addColumn('description', Types::TEXT, ['notnull' => false]);
            $table->addColumn('checked', Types::SMALLINT, ['notnull' => true, 'default' => 0]);
            $table->addColumn('checked_at', Types::BIGINT, ['notnull' => false, 'unsigned' => true]);
            $table->addColumn('position', Types::INTEGER, ['notnull' => true, 'default' => 0]);
            $table->addColumn('created_at', Types::BIGINT, ['notnull' => true, 'unsigned' => true]);
            $table->addColumn('updated_at', Types::BIGINT, ['notnull' => true, 'unsigned' => true]);
            $table->setPrimaryKey(['id']);
            $table->addIndex(['list_id'], 'lists_items_list_idx');
        }

        if (!$schema->hasTable('lists_shares')) {
            $table = $schema->createTable('lists_shares');
            $table->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true, 'unsigned' => true]);
            $table->addColumn('list_id', Types::BIGINT, ['notnull' => true, 'unsigned' => true]);
            $table->addColumn('share_type', Types::SMALLINT, ['notnull' => true]);
            $table->addColumn('share_with', Types::STRING, ['notnull' => true, 'length' => 255]);
            $table->addColumn('permissions', Types::SMALLINT, ['notnull' => true, 'default' => 1]);
            $table->addColumn('created_at', Types::BIGINT, ['notnull' => true, 'unsigned' => true]);
            $table->setPrimaryKey(['id']);
            $table->addIndex(['list_id'], 'lists_shares_list_idx');
            $table->addIndex(['share_type', 'share_with'], 'lists_shares_with_idx');
        }

        return $schema;
    }
}
