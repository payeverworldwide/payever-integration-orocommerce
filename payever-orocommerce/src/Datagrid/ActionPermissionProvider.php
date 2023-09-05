<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Datagrid;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Payever\Bundle\PaymentBundle\Method\Payever;

class ActionPermissionProvider
{
    /**
     * @var PaymentMethodProviderInterface
     */
    protected $paymentMethodProvider;

    /**
     * @var EntityManager
     */
    protected $manager;

    public function __construct(PaymentMethodProviderInterface $paymentMethodProvider, EntityManager $manager)
    {
        $this->paymentMethodProvider = $paymentMethodProvider;
        $this->manager = $manager;
    }

    public function getActionPermissions(ResultRecordInterface $record): array
    {
        $currentTransaction = $this->manager->getRepository(PaymentTransaction::class)
            ->find($record->getValue('id'));
        $paymentMethod = $this->paymentMethodProvider->getPaymentMethod($currentTransaction->getPaymentMethod());

        return [
            'transaction_info' => $paymentMethod instanceof Payever &&
                in_array(
                    $record->getValue('action'),
                    [PaymentMethodInterface::AUTHORIZE, PaymentMethodInterface::CAPTURE]
                )
        ];
    }
}
