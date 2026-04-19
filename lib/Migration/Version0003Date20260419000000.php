<?php

declare(strict_types=1);

namespace OCA\Lists\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version0003Date20260419000000 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if ($schema->hasTable('lists')) {
            $table = $schema->getTable('lists');
            if (!$table->hasColumn('has_quantities')) {
                $table->addColumn('has_quantities', Types::SMALLINT, ['notnull' => true, 'default' => 0]);
            }
        }

        if ($schema->hasTable('lists_items')) {
            $table = $schema->getTable('lists_items');
            if (!$table->hasColumn('quantity')) {
                $table->addColumn('quantity', Types::INTEGER, ['notnull' => false, 'default' => null]);
            }
        }

        return $schema;
    }
}
