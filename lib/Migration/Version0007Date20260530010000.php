<?php

declare(strict_types=1);

namespace OCA\Lists\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Drops the now-legacy lists.position column. Per-user ordering lives in
 * lists_user_positions since Version0006.
 */
class Version0007Date20260530010000 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if ($schema->hasTable('lists')) {
            $table = $schema->getTable('lists');
            if ($table->hasColumn('position')) {
                $table->dropColumn('position');
            }
        }

        return $schema;
    }
}
