<?php

namespace Payever\Bundle\PaymentBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Payever\Bundle\PaymentBundle\Method\Provider\PayeverMethodProvider;

/**
 * Add 'reference' column to order payment transaction grid if Payever used as payment method.
 */
class OrderPaymentTransactionsGridListener
{
    private ManagerRegistry $managerRegistry;

    private PayeverMethodProvider $paymentProvider;

    public function __construct(
        ManagerRegistry $managerRegistry,
        PayeverMethodProvider $paymentProvider
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->paymentProvider = $paymentProvider;
    }

    public function onBuildBefore(BuildBefore $event): void
    {
        $config = $event->getConfig();
        $dataGrid = $event->getDatagrid();

        if ($this->isPayeverPaymentTransaction($dataGrid)) {
            $config->addColumn(
                'reference',
                [
                    'label' => 'oro.payment.paymenttransaction.reference.label'
                ],
                'payment_transaction.reference'
            );
        }
    }

    private function isPayeverPaymentTransaction(DatagridInterface $dataGrid): bool
    {
        $orderId = $dataGrid->getParameters()->get('order_id', null);
        if (!$orderId) {
            return false;
        }

        $orderPaymentMethods = $this->getOrderPaymentMethods($orderId);
        $activePayverPaymentMethods = $this->getActivePayeverPaymentMethods();
        $usedMethods = array_intersect($activePayverPaymentMethods, $orderPaymentMethods);
        return count($usedMethods);
    }

    private function getActivePayeverPaymentMethods(): array
    {
        $payeverPaymentMethods = $this->paymentProvider->getPaymentMethods();
        return array_keys($payeverPaymentMethods);
    }

    private function getOrderPaymentMethods(int $orderId): array
    {
        $repository = $this->managerRegistry->getRepository(PaymentTransaction::class);
        $paymentMethods = $repository->getPaymentMethods(Order::class, [$orderId]);

        return $paymentMethods[$orderId] ?? [];
    }
}
