<?php

namespace Payever\Bundle\PaymentBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.ShortMethodName)
 */
class CreatePaymentActionsTable implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->createPaymentActionTable($schema);
    }

    /**
     * Creates the payment_actions table in the given schema.
     *
     * @param Schema $schema The schema in which to create the table.
     *
     * @return void
     */
    private function createPaymentActionTable(Schema $schema): void
    {
        /**
         * If migration is already completed it should not run again
         */
        if ($schema->hasTable('payever_payment_actions')) {
            return;
        }

        $table = $schema->createTable('payever_payment_actions');

        $table->addColumn(
            'id',
            'integer',
            ['autoincrement' => true]
        );

        $table->addColumn(
            'identifier',
            'string',
            ['notnull' => true, 'length' => 255]
        );

        $table->addColumn(
            'order_id',
            'integer',
            ['notnull' => true]
        );

        $table->addColumn(
            'type',
            'string',
            ['notnull' => false, 'length' => 64]
        );

        $table->addColumn(
            'source',
            'string',
            ['notnull' => false, 'length' => 64]
        );

        $table->addColumn(
            'amount',
            'money',
            [
                'notnull' => false,
                'precision' => 2,
                'scale' => 4,
                'comment' => '(DC2Type:money)',
            ]
        );

        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);

        $table->setPrimaryKey(['id']);
        $table->addIndex(['order_id'], 'IDX_2D261519EB185F9', []);
        $table->addUniqueIndex(['identifier'], 'IDX_6D1262519EB1F1F1', []);
    }
}
