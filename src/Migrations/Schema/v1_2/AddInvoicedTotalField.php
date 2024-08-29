<?php

namespace Payever\Bundle\PaymentBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.ShortMethodName)
 */
class AddInvoicedTotalField implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->addInvoicedField($schema);
    }

    /**
     * Update the order_totals table in the given schema.
     *
     * @param Schema $schema The schema in which to create the table.
     *
     * @return void
     * @throws SchemaException
     */
    private function addInvoicedField(Schema $schema): void
    {
        $table = $schema->getTable('payever_order_totals');
        if (!$table->hasColumn('invoiced_total')) {
            $table->addColumn(
                'invoiced_total',
                'money',
                [
                    'notnull' => false,
                    'precision' => 2,
                    'scale' => 4,
                    'default' => 0,
                    'comment' => '(DC2Type:money)',
                ]
            );
        }
    }
}
