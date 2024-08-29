<?php

namespace Payever\Bundle\PaymentBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.ShortMethodName)
 */
class CreateInvoiceTable implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->createOrderInvoiceTable($schema);
    }

    /**
     * Creates the order_totals table in the given schema.
     *
     * @param Schema $schema The schema in which to create the table.
     *
     * @return void
     */
    private function createOrderInvoiceTable(Schema $schema): void
    {
        /**
         * If migration is already completed it should not run again
         */
        if ($schema->hasTable('payever_order_invoices')) {
            return;
        }

        $table = $schema->createTable('payever_order_invoices');
        $table->addColumn(
            'id',
            'integer',
            ['autoincrement' => true]
        );

        $table->addColumn(
            'order_id',
            'integer',
            ['notnull' => true]
        );

        $table->addColumn(
            'attachment_id',
            'integer',
            ['notnull' => true]
        );

        $table->addColumn(
            'payment_id',
            'string',
            ['notnull' => false, 'length' => 64]
        );

        $table->addColumn(
            'external_id',
            'string',
            ['notnull' => false, 'length' => 64]
        );

        $table->setPrimaryKey(['id']);
        $table->addIndex(['order_id']);
    }
}
