<?php

namespace Payever\Bundle\PaymentBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.ShortMethodName)
 */
class CreatePayeverCheckoutTable implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->createPayeverCheckoutTable($schema);
    }

    /**
     * Creates the payever_payment_checkout table in the given schema.
     *
     * @param Schema $schema The schema in which to create the table.
     *
     * @return void
     */
    private function createPayeverCheckoutTable(Schema $schema): void
    {
        /**
         * If migration is already completed it should not run again
         */
        if ($schema->hasTable('payever_payment_checkout')) {
            return;
        }

        $table = $schema->createTable('payever_payment_checkout');

        $table->addColumn(
            'id',
            Types::INTEGER,
            ['autoincrement' => true]
        );

        $table->addColumn(
            'order_id',
            Types::INTEGER,
            ['notnull' => false]
        );

        $table->addColumn(
            'checkout_id',
            Types::INTEGER,
            ['notnull' => true]
        );

        $table->addColumn(
            'payment_id',
            Types::STRING,
            ['notnull' => true, 'length' => 64]
        );

        $table->addColumn(
            'data',
            Types::ARRAY,
            [
                'notnull' => false
            ]
        );

        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);

        $table->setPrimaryKey(['id']);
        $table->addIndex(['checkout_id']);
    }
}
