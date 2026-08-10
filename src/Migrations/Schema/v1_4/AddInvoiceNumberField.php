<?php

namespace Payever\Bundle\PaymentBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.ShortMethodName)
 */
class AddInvoiceNumberField implements Migration
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
        $table = $schema->getTable('payever_order_invoices');
        if (!$table->hasColumn('invoice_number')) {
            $table->addColumn(
                'invoice_number',
                Type::STRING,
                ['notnull' => false, 'length' => 64]
            );
        }
    }
}
