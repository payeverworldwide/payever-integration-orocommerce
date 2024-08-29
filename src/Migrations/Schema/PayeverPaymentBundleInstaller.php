<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.ShortMethodName)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class PayeverPaymentBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion(): string
    {
        return 'v1_2';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        /** Tables generation */
        $this->alterOroIntegrationTransportTable($schema);
        $this->createPayeverShortLabelTable($schema);
        $this->createPayeverTransLabelTable($schema);
        $this->createPayeverPaymentActionsTable($schema);

        /** Foreign keys generation */
        $this->addPayeverShortLabelForeignKeys($schema);
        $this->addPayeverTransLabelForeignKeys($schema);

        $this->createPayeverOrderItemsTable($schema);
        $this->createPayeverOrderTotalsTable($schema);
    }

    /**
     * Alter `oro_integration_transport` table
     *
     * @param Schema $schema
     */
    protected function alterOroIntegrationTransportTable(Schema $schema): void
    {
        $table = $schema->getTable('oro_integration_transport');

        if (!$table->hasColumn('payever_payment_method')) {
            $table->addColumn(
                'payever_payment_method',
                'string',
                [
                    'notnull' => false,
                    'length' => 255
                ]
            );
        }


        if (!$table->hasColumn('payever_variant_id')) {
            $table->addColumn(
                'payever_variant_id',
                'string',
                [
                    'notnull' => false,
                    'length' => 255
                ]
            );
        }

        if (!$table->hasColumn('payever_description_offer')) {
            $table->addColumn(
                'payever_description_offer',
                Type::TEXT,
                [
                    'notnull' => false,
                ]
            );
        }
        $table->changeColumn('payever_description_offer', ['length' => 500]);

        if (!$table->hasColumn('payever_description_fee')) {
            $table->addColumn(
                'payever_description_fee',
                Type::TEXT,
                [
                    'notnull' => false,
                ]
            );
        }

        if (!$table->hasColumn('payever_is_redirect_method')) {
            $table->addColumn(
                'payever_is_redirect_method',
                'boolean',
                [
                    'default' => '0',
                    'notnull' => false
                ]
            );
        }

        if (!$table->hasColumn('payever_is_submit_method')) {
            $table->addColumn(
                'payever_is_submit_method',
                'boolean',
                [
                    'default' => '0',
                    'notnull' => false
                ]
            );
        }

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

        if (!$table->hasColumn('payever_instruction_text')) {
            $table->addColumn(
                'payever_instruction_text',
                Type::TEXT,
                [
                    'notnull' => false
                ]
            );
        }

        if (!$table->hasColumn('payever_thumbnail')) {
            $table->addColumn(
                'payever_thumbnail',
                'string',
                [
                    'notnull' => false,
                    'length' => 255
                ]
            );
        }

        if (!$table->hasColumn('payever_currencies')) {
            $table->addColumn(
                'payever_currencies',
                Type::JSON,
                [
                    'notnull' => false
                ]
            );
        }
        $table->changeColumn('payever_currencies', ['length' => 4000]);

        if (!$table->hasColumn('payever_countries')) {
            $table->addColumn(
                'payever_countries',
                Type::JSON,
                [
                    'notnull' => false
                ]
            );
        }
        $table->changeColumn('payever_countries', ['length' => 4000]);

        if (!$table->hasColumn('payever_is_shipping_address_allowed')) {
            $table->addColumn(
                'payever_is_shipping_address_allowed',
                'boolean',
                [
                    'default' => '0',
                    'notnull' => false
                ]
            );
        }

        if (!$table->hasColumn('payever_is_shipping_address_equality')) {
            $table->addColumn(
                'payever_is_shipping_address_equality',
                'boolean',
                [
                    'default' => '0',
                    'notnull' => false
                ]
            );
        }

        if (!$table->hasColumn('payever_min')) {
            $table->addColumn(
                'payever_min',
                'money',
                [
                    'notnull' => false,
                    'precision' => 2,
                    'scale' => 4,
                    'comment' => '(DC2Type:money)',
                ]
            );
        }

        if (!$table->hasColumn('payever_max')) {
            $table->addColumn(
                'payever_max',
                'money',
                [
                    'notnull' => false,
                    'precision' => 2,
                    'scale' => 4,
                    'comment' => '(DC2Type:money)',
                ]
            );
        }

        if (!$table->hasColumn('payever_is_accept_fee')) {
            $table->addColumn(
                'payever_is_accept_fee',
                'boolean',
                [
                    'default' => '0',
                    'notnull' => false
                ]
            );
        }

        if (!$table->hasColumn('payever_fixed_fee')) {
            $table->addColumn(
                'payever_fixed_fee',
                'money',
                [
                    'notnull' => false,
                    'precision' => 2,
                    'scale' => 4,
                    'comment' => '(DC2Type:money)',
                ]
            );
        }

        if (!$table->hasColumn('payever_variable_fee')) {
            $table->addColumn(
                'payever_variable_fee',
                'money',
                [
                    'notnull' => false,
                    'precision' => 2,
                    'scale' => 4,
                    'comment' => '(DC2Type:money)',
                ]
            );
        }
    }

    /**
     * Create `payever_short_label` table
     *
     * @param Schema $schema
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    protected function createPayeverShortLabelTable(Schema $schema): void
    {
        if ($schema->hasTable('payever_short_label')) {
            $table = $schema->getTable('payever_short_label');
        } else {
            $table = $schema->createTable('payever_short_label');
        }
        if (!$table->hasColumn('transport_id')) {
            $table->addColumn('transport_id', 'integer', []);
        }
        if (!$table->hasColumn('localized_value_id')) {
            $table->addColumn('localized_value_id', 'integer', []);
            $table->setPrimaryKey(['transport_id', 'localized_value_id']);
            $table->addIndex(['transport_id'], 'oro_payment_payever_short_label_transport_id', []);
            $table->addUniqueIndex(['localized_value_id'], 'oro_payment_payever_short_label_localized_value_id', []); //phpcs:ignore
        }
    }

    /**
     * Create `payever_trans_label` table
     *
     * @param Schema $schema
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    protected function createPayeverTransLabelTable(Schema $schema): void
    {
        if ($schema->hasTable('payever_trans_label')) {
            $table = $schema->getTable('payever_trans_label');
        } else {
            $table = $schema->createTable('payever_trans_label');
        }

        if (!$table->hasColumn('transport_id')) {
            $table->addColumn('transport_id', 'integer', []);
        }
        if (!$table->hasColumn('localized_value_id')) {
            $table->addColumn('localized_value_id', 'integer', []);
            $table->setPrimaryKey(['transport_id', 'localized_value_id']);
            $table->addIndex(['transport_id'], 'oro_payment_payever_trans_label_transport_id', []);
            $table->addUniqueIndex(['localized_value_id'], 'oro_payment_payever_trans_label_localized_value_id', []); //phpcs:ignore
        }
    }

    /**
     * Create `payever_payment_actions` table
     *
     * @param Schema $schema
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    protected function createPayeverPaymentActionsTable(Schema $schema): void
    {
        if ($schema->hasTable('payever_payment_actions')) {
            $table = $schema->getTable('payever_payment_actions');
        } else {
            $table = $schema->createTable('payever_payment_actions');
        }

        if (!$table->hasColumn('id')) {
            $table->addColumn(
                'id',
                'integer',
                ['autoincrement' => true]
            );
            $table->setPrimaryKey(['id']);
        }

        if (!$table->hasColumn('identifier')) {
            $table->addColumn(
                'identifier',
                'string',
                ['notnull' => true, 'length' => 255]
            );
            $table->addUniqueIndex(['identifier'], 'IDX_6D1262519EB1F1F1', []);
        }

        if (!$table->hasColumn('order_id')) {
            $table->addColumn(
                'order_id',
                'integer',
                ['notnull' => true]
            );
            $table->addIndex(['order_id'], 'IDX_2D261519EB185F9', []);
        }

        if (!$table->hasColumn('type')) {
            $table->addColumn(
                'type',
                'string',
                ['notnull' => false, 'length' => 64]
            );
        }

        if (!$table->hasColumn('source')) {
            $table->addColumn(
                'source',
                'string',
                ['notnull' => false, 'length' => 64]
            );
        }

        if (!$table->hasColumn('amount')) {
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
        }

        if (!$table->hasColumn('created_at')) {
            $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        }

        if (!$table->hasColumn('updated_at')) {
            $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        }
    }

    /**
     * Add payever_short_label foreign keys.
     *
     * @param Schema $schema
     */
    protected function addPayeverShortLabelForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('payever_short_label');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_transport'),
            ['transport_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add payever_trans_label foreign keys.
     *
     * @param Schema $schema
     */
    protected function addPayeverTransLabelForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('payever_trans_label');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_transport'),
            ['transport_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Create `payever_order_items` table
     *
     * @param Schema $schema
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    protected function createPayeverOrderItemsTable(Schema $schema): void
    {
        if ($schema->hasTable('payever_order_items')) {
            $table = $schema->getTable('payever_order_items');
        } else {
            $table = $schema->createTable('payever_order_items');
        }

        if (!$table->hasColumn('id')) {
            $table->addColumn(
                'id',
                'integer',
                ['autoincrement' => true]
            );
            $table->setPrimaryKey(['id']);
        }

        if (!$table->hasColumn('order_id')) {
            $table->addColumn(
                'order_id',
                'integer',
                ['notnull' => true]
            );
            $table->addIndex(['order_id'], 'IDX_1D161518EB185F8', []);
        }

        if (!$table->hasColumn('item_type')) {
            $table->addColumn(
                'item_type',
                'string',
                ['notnull' => false, 'length' => 255]
            );
        }

        if (!$table->hasColumn('item_reference')) {
            $table->addColumn(
                'item_reference',
                'string',
                ['notnull' => false, 'length' => 255]
            );
        }

        if (!$table->hasColumn('name')) {
            $table->addColumn(
                'name',
                'string',
                ['notnull' => false, 'length' => 255]
            );
        }

        if (!$table->hasColumn('unit_price')) {
            $table->addColumn(
                'unit_price',
                'money',
                [
                    'notnull' => false,
                    'precision' => 2,
                    'scale' => 4,
                    'comment' => '(DC2Type:money)',
                ]
            );
        }

        if (!$table->hasColumn('total_price')) {
            $table->addColumn(
                'total_price',
                'money',
                [
                    'notnull' => false,
                    'precision' => 2,
                    'scale' => 4,
                    'comment' => '(DC2Type:money)',
                ]
            );
        }

        if (!$table->hasColumn('quantity')) {
            $table->addColumn(
                'quantity',
                'float',
                ['notnull' => false]
            );
        }

        if (!$table->hasColumn('qty_captured')) {
            $table->addColumn(
                'qty_captured',
                'float',
                [
                    'notnull' => false,
                    'precision' => 2,
                ]
            );
        }

        if (!$table->hasColumn('qty_cancelled')) {
            $table->addColumn(
                'qty_cancelled',
                'float',
                [
                    'notnull' => false,
                    'precision' => 2,
                ]
            );
        }

        if (!$table->hasColumn('qty_refunded')) {
            $table->addColumn(
                'qty_refunded',
                'float',
                [
                    'notnull' => false,
                    'precision' => 2,
                ]
            );
        }
    }

    /**
     * Create `payever_order_totals` table
     *
     * @param Schema $schema
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    protected function createPayeverOrderTotalsTable(Schema $schema): void
    {
        if ($schema->hasTable('payever_order_totals')) {
            $table = $schema->getTable('payever_order_totals');
        } else {
            $table = $schema->createTable('payever_order_totals');
        }

        if (!$table->hasColumn('id')) {
            $table->addColumn(
                'id',
                'integer',
                ['autoincrement' => true]
            );
            $table->setPrimaryKey(['id']);
        }

        if (!$table->hasColumn('order_id')) {
            $table->addColumn(
                'order_id',
                'integer',
                ['notnull' => true]
            );
            $table->addIndex(['order_id'], 'IDX_1E161518EB185E8', []);
        }

        if (!$table->hasColumn('captured_total')) {
            $table->addColumn(
                'captured_total',
                'money',
                [
                    'notnull' => false,
                    'precision' => 2,
                    'scale' => 4,
                    'comment' => '(DC2Type:money)',
                ]
            );
        }

        if (!$table->hasColumn('cancelled_total')) {
            $table->addColumn(
                'cancelled_total',
                'money',
                [
                    'notnull' => false,
                    'precision' => 2,
                    'scale' => 4,
                    'comment' => '(DC2Type:money)',
                ]
            );
        }

        if (!$table->hasColumn('refunded_total')) {
            $table->addColumn(
                'refunded_total',
                'money',
                [
                    'notnull' => false,
                    'precision' => 2,
                    'scale' => 4,
                    'comment' => '(DC2Type:money)',
                ]
            );
        }

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

        if (!$table->hasColumn('manual')) {
            $table->addColumn(
                'manual',
                'integer',
                [
                    'notnull' => false,
                ]
            );
        }
    }
}
