<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Helper;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Payever\Bundle\PaymentBundle\Entity\PayeverSettings as Transport;
use Payever\Bundle\PaymentBundle\Entity\Repository\PayeverSettingsRepository;
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
     * Constructor.
     *
     * @param PayeverMethodProvider $payeverMethodProvider
     * @param PayeverConfigProvider $payeverConfigProvider
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        PayeverMethodProvider $payeverMethodProvider,
        PayeverConfigProvider $payeverConfigProvider
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->payeverMethodProvider = $payeverMethodProvider;
        $this->payeverConfigProvider = $payeverConfigProvider;
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
}
