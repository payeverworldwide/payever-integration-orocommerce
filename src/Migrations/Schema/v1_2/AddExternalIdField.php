<?php

namespace Payever\Bundle\PaymentBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * @SuppressWarnings(PHPMD.ShortMethodName)
 */
class AddExternalIdField implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->addAddressExtensionId($schema, 'oro_order_address');
        $this->addAddressExtensionId($schema, 'oro_customer_address');
        $this->addAddressExtensionId($schema, 'oro_customer_user_address');
    }

    /**
     * @param Schema $schema The schema in which to create the table.
     * @param string $table
     *
     * @return void
     * @throws SchemaException
     */
    private function addAddressExtensionId(Schema $schema, string $table): void
    {
        if (!$schema->hasTable($table)) {
            return;
        }

        $table = $schema->getTable($table);

        if (!$table->hasColumn('payever_external_id')) {
            $table->addColumn('payever_external_id', 'string', [
                'length' => 32,
                'oro_options' => [
                    'extend' => [
                        'owner' => ExtendScope::OWNER_CUSTOM,
                        'is_extend' => true,
                        'nullable' => true,
                        'on_delete' => 'SET NULL',
                    ],
                    'form' => [
                        'is_enabled' => true,
                        'type' => HiddenType::class,
                    ],
                    'entity' => ['label' => 'Company External ID'],
                ],
            ]);
        }
    }
}
