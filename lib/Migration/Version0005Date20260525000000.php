<?php

declare(strict_types=1);

namespace OCA\Lists\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version0005Date20260525000000 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if ($schema->hasTable('lists_categories')) {
            $table = $schema->getTable('lists_categories');
            if (!$table->hasColumn('icon')) {
                $table->addColumn('icon', Types::STRING, [
                    'notnull' => false,
                    'length'  => 32,
                    'default' => null,
                ]);
            }
        }

        return $schema;
    }
}
