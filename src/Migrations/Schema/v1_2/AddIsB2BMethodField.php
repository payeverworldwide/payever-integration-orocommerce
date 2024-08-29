<?php

namespace Payever\Bundle\PaymentBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.ShortMethodName)
 */
class AddIsB2BMethodField implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->addIsB2bField($schema);
    }

    /**
     * Update the oro_integration_transport table in the given schema.
     *
     * @param Schema $schema
     * @throws SchemaException
     */
    private function addIsB2bField(Schema $schema): void
    {
        $table = $schema->getTable('oro_integration_transport');
        if (!$table->hasColumn('payever_is_b2b_method')) {
            $table->addColumn(
                'payever_is_b2b_method',
                'boolean',
                [
                    'default' => '0',
                    'notnull' => false
                ]
            );
        }
    }
}
