<?php

namespace Payever\Bundle\PaymentBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.ShortMethodName)
 */
class AddBusinessTypeField implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->addField($schema);
    }

    /**
     * Update the oro_integration_transport table in the given schema.
     *
     * @param Schema $schema
     * @throws SchemaException
     */
    private function addField(Schema $schema): void
    {
        $table = $schema->getTable('oro_integration_transport');
        if (!$table->hasColumn('payever_business_type')) {
            $table->addColumn(
                'payever_business_type',
                Type::STRING,
                [
                    'notnull' => false
                ]
            );
        }
    }
}
