<?php

namespace Payever\Bundle\PaymentBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.ShortMethodName)
 */
class PayeverPaymentBundle implements Migration, ExtendExtensionAwareInterface
{
    /** @var ExtendExtension $extendExtension */
    protected $extendExtension;

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension): void
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->createPaymentActionTable($schema);
        $this->updateOrderTotalsTable($schema);
        $this->createOrderInvoiceTable($schema);
        $this->updateOroIntegrationTransportTable($schema);
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

    /**
     * Update the order_totals table in the given schema.
     *
     * @param Schema $schema The schema in which to create the table.
     *
     * @return void
     * @throws SchemaException
     */
    private function updateOrderTotalsTable(Schema $schema)
    {
        $table = $schema->getTable('payever_order_totals');

        if (!$table->hasColumn('settled_total')) {
            $table->addColumn(
                'settled_total',
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

    /**
     * Update the oro_integration_transport table in the given schema.
     *
     * @param Schema $schema
     * @throws SchemaException
     */
    protected function updateOroIntegrationTransportTable(Schema $schema): void
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
