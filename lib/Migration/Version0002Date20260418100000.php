<?php

declare(strict_types=1);

namespace OCA\Lists\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version0002Date20260418100000 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('lists_categories')) {
            $table = $schema->createTable('lists_categories');
            $table->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true, 'unsigned' => true]);
            $table->addColumn('list_id', Types::BIGINT, ['notnull' => true, 'unsigned' => true]);
            $table->addColumn('name', Types::STRING, ['notnull' => true, 'length' => 255]);
            $table->addColumn('position', Types::INTEGER, ['notnull' => true, 'default' => 0]);
            $table->addColumn('created_at', Types::BIGINT, ['notnull' => true, 'unsigned' => true, 'default' => 0]);
            $table->setPrimaryKey(['id']);
            $table->addIndex(['list_id'], 'lists_categories_list_idx');
        }

        if ($schema->hasTable('lists_items')) {
            $table = $schema->getTable('lists_items');
            if (!$table->hasColumn('category_id')) {
                $table->addColumn('category_id', Types::BIGINT, ['notnull' => false, 'unsigned' => true, 'default' => null]);
            }
        }

        return $schema;
    }
}
