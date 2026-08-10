<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Payment\Notification\Handler;

use Doctrine\ORM\EntityManager;
use Payever\Bundle\PaymentBundle\Service\Helper\TransactionHelper;
use Payever\Bundle\PaymentBundle\Service\Payment\InvoiceService;
use Payever\Sdk\Payments\Enum\Status;
use Payever\Sdk\Payments\Notification\MessageEntity\NotificationResultEntity;
use Psr\Log\LoggerInterface;

class B2BPaymentHandler implements HandlerInterface
{
    /**
     * @var EntityManager
     */
    private EntityManager $entityManager;

    /**
     * @var TransactionHelper
     */
    private TransactionHelper $transactionHelper;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Constructor.
     *
     * @param EntityManager $entityManager
     * @param TransactionHelper $transactionHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        EntityManager $entityManager,
        TransactionHelper $transactionHelper,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->transactionHelper = $transactionHelper;
        $this->logger = $logger;
    }

    /**
     * @inheridoc
     */
    public function execute(NotificationResultEntity $notificationResultEntity): array
    {
        $this->updateCompanyByNotification($notificationResultEntity);

        return [
            'successful' => true,
        ];
    }

    /**
     * @iheritdoc
     */
    public function isApplicable(NotificationResultEntity $notificationResultEntity): bool
    {
        return $notificationResultEntity->getCompany() && $notificationResultEntity->getCompany()->getExternalId();
    }

    /**
     * @param NotificationResultEntity $notificationResultEntity
     *
     * @return void
     */
    private function updateCompanyByNotification(NotificationResultEntity $notificationResultEntity): void
    {
        $externalId = $notificationResultEntity->getCompany()->getExternalId();
        $orderReference = $notificationResultEntity->getReference();

        try {
            $order = $this->transactionHelper->getOrderByIdentifier($orderReference);

            $billingAddress = $order->getBillingAddress();
            $billingAddress->setPayeverExternalId($externalId);

            if ($billingAddress->getCustomerUserAddress()) {
                $customerAddress = $billingAddress->getCustomerUserAddress();
                $customerAddress->setPayeverExternalId($externalId);
                $this->entityManager->persist($customerAddress);
            }

            $this->entityManager->persist($billingAddress);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->logger->critical(
                sprintf(
                    'Failed to update company search id from notification %s: %s.',
                    $notificationResultEntity->getReference(),
                    $externalId
                )
            );
        }
    }
}
