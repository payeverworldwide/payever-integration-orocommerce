<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Helper;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Payever\Bundle\PaymentBundle\Entity\PayeverSettings as Transport;
use Payever\Bundle\PaymentBundle\Entity\Repository\PayeverSettingsRepository;
use Payever\Bundle\PaymentBundle\Method\Config\PayeverConfig;
use Payever\Bundle\PaymentBundle\Method\Config\Provider\PayeverConfigProvider;
use Payever\Bundle\PaymentBundle\Method\Provider\PayeverMethodProvider;

class PaymentMethodHelper
{
    /**
     * @var ManagerRegistry
     */
    private ManagerRegistry $managerRegistry;

    /**
     * @var PayeverMethodProvider
     */
    private PayeverMethodProvider $payeverMethodProvider;

    /**
     * @var PayeverConfigProvider
     */
    private PayeverConfigProvider $payeverConfigProvider;

    /**
     * @var PaymentTransactionProvider
     */
    private PaymentTransactionProvider $paymentTransactionProvider;

    /**
     * Constructor.
     *
     * @param PayeverMethodProvider $payeverMethodProvider
     * @param PayeverConfigProvider $payeverConfigProvider
     * @param PaymentTransactionProvider $paymentTransactionProvider
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        PayeverMethodProvider $payeverMethodProvider,
        PayeverConfigProvider $payeverConfigProvider,
        PaymentTransactionProvider $paymentTransactionProvider
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->payeverMethodProvider = $payeverMethodProvider;
        $this->payeverConfigProvider = $payeverConfigProvider;
        $this->paymentTransactionProvider = $paymentTransactionProvider;
    }

    /**
     * Get Payment Method Instance by Payever Payment Code.
     *
     * @return PaymentMethodInterface|null
     */
    public function getPaymentMethod(string $payeverPaymentMethod): ?PaymentMethodInterface
    {
        $methods = $this->payeverMethodProvider->getPaymentMethods();
        foreach ($methods as $method) {
            $config = $this->payeverConfigProvider->getPaymentConfig($method->getIdentifier());
            if ($config->getPaymentMethod() === $payeverPaymentMethod) {
                return $method;
            }
        }

        return null;
    }

    /**
     * Get Payment Method Instance by Payever Payment Identifier.
     *
     * @return PayeverConfig|null
     */
    public function getPaymentConfigByIdentifier(string $payeverPaymentIdentifier): ?PayeverConfig
    {
        $methods = $this->payeverMethodProvider->getPaymentMethods();
        foreach ($methods as $method) {
            if ($method->getIdentifier() === $payeverPaymentIdentifier) {
                return $this->payeverConfigProvider->getPaymentConfig($method->getIdentifier());
            }
        }

        return null;
    }

    /**
     * @param string $paymentMethod
     * @return Transport|null
     */
    public function getPaymentMethodSettings(string $paymentMethod): ?Transport
    {
        /** @var PayeverSettingsRepository $repository */
        $repository = $this->managerRegistry
            ->getManagerForClass(Transport::class)
            ->getRepository(Transport::class);

        return $repository->findOneBy(
            [
                'paymentMethod' => $paymentMethod
            ]
        );
    }

    /**
     * Identify Order that paid with B2B method.
     *
     * @param Order $order
     * @return bool
     */
    public function isB2BMethod(Order $order): bool
    {
        $transactions = $this->paymentTransactionProvider->getPaymentTransactions($order);
        foreach ($transactions as $transaction) {
            $paymentSettings = $this->getPaymentConfigByIdentifier($transaction->getPaymentMethod());
            if ($paymentSettings instanceof PayeverConfig && $paymentSettings->getIsB2BMethod()) {
                return true;
            }
        }

        return false;
    }
}
