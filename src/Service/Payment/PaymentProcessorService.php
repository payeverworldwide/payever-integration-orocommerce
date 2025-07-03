<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Payment;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Payever\Bundle\PaymentBundle\Constant\QueryConstant;
use Payever\Bundle\PaymentBundle\Method\Config\PayeverConfigInterface;
use Payever\Bundle\PaymentBundle\Service\Api\ServiceProvider;
use Payever\Bundle\PaymentBundle\Service\Helper\UrlHelper;
use Payever\Bundle\PaymentBundle\Service\Payment\Request\PopulatePaymentRequestV2;
use Payever\Bundle\PaymentBundle\Service\Payment\Request\PopulatePaymentRequestV3;
use Payever\Sdk\Payments\Http\MessageEntity\RetrievePaymentResultEntity;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Payever\Sdk\Payments\Http\ResponseEntity\RetrievePaymentResponse;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Payever\Bundle\PaymentBundle\Constant\SettingsConstant;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class PaymentProcessorService
{
    private ServiceProvider $serviceProvider;

    private ConfigManager $configManager;

    private UrlHelper $urlHelper;

    /**
     * @var DoctrineHelper
     */
    private DoctrineHelper $doctrineHelper;

    private TransactionStatusService $transactionStatusService;

    private PopulatePaymentRequestV2 $populatePaymentRequestV2;

    private PopulatePaymentRequestV3 $populatePaymentRequestV3;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var PayeverConfigInterface
     */
    private PayeverConfigInterface $config;

    public function __construct(
        ServiceProvider $serviceProvider,
        ConfigManager $configManager,
        UrlHelper $urlHelper,
        DoctrineHelper $doctrineHelper,
        TransactionStatusService $transactionStatusService,
        PopulatePaymentRequestV2 $populatePaymentRequestV2,
        PopulatePaymentRequestV3 $populatePaymentRequestV3,
        LoggerInterface $logger
    ) {
        $this->serviceProvider = $serviceProvider;
        $this->configManager = $configManager;
        $this->urlHelper = $urlHelper;
        $this->doctrineHelper = $doctrineHelper;
        $this->transactionStatusService = $transactionStatusService;
        $this->populatePaymentRequestV2 = $populatePaymentRequestV2;
        $this->populatePaymentRequestV3 = $populatePaymentRequestV3;
        $this->logger = $logger;
    }

    /**
     * Set Payment Config.
     *
     * @param PayeverConfigInterface $config
     *
     * @return $this
     */
    public function setConfig(PayeverConfigInterface $config): self
    {
        $this->config = $config;

        return $this;
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     *
     * @return string
     * @throws \Exception
     */
    public function getRedirectUrl(PaymentTransaction $paymentTransaction): string
    {
        if ($this->config->getIsSubmitMethod()) {
            return $this->populatePaymentRequestV3->setConfig($this->config)
                ->getSubmitRedirectUrl($paymentTransaction);
        }

        $redirectUrl = $this->createPayment($paymentTransaction);

        if (!$this->config->getIsRedirectMethod() && !$this->configManager->get('payever_payment.is_redirect')) {
            return $this->urlHelper->generateIframeUrl($redirectUrl);
        }

        return $redirectUrl;
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return string
     * @throws \Exception
     */
    private function createPayment(PaymentTransaction $paymentTransaction)
    {
        if (SettingsConstant::API_V2 === (int)$this->configManager->get('payever_payment.api_version')) {
            return $this->populatePaymentRequestV2->setConfig($this->config)->getRedirectUrl($paymentTransaction);
        }

        return $this->populatePaymentRequestV3->setConfig($this->config)->getRedirectUrl($paymentTransaction);
    }

    public function finalize(
        PaymentTransaction $paymentTransaction,
        Request $request
    ): void {
        $paymentId = $request->get(QueryConstant::PARAMETER_PAYMENT_ID);

        if (!$paymentId || QueryConstant::PAYMENT_ID_PLACEHODLER === $paymentId) {
            throw new \Exception('Payment ID is invalid.');
        }

        $order = $this->getOrder($paymentTransaction);
        if (!$order) {
            throw new \Exception('Order is not found.');
        }

        $response = $this->serviceProvider
            ->getPaymentsApiClient()
            ->retrievePaymentRequest($paymentId);

        /** @var RetrievePaymentResponse $responseEntity */
        $responseEntity = $response->getResponseEntity();

        /** @var RetrievePaymentResultEntity $payeverPayment */
        $payeverPayment = $responseEntity->getResult();

        $this->transactionStatusService->persistTransactionStatus($payeverPayment);
        $this->logger->debug('Payment has been finalized');
    }

    public function retrievePayment(PaymentTransaction $paymentTransaction): RetrievePaymentResultEntity
    {
        $paymentId = $paymentTransaction->getReference();
        if (empty($paymentId)) {
            throw new \InvalidArgumentException('Payment ID is missing.');
        }

        $response = $this->serviceProvider
            ->getPaymentsApiClient()
            ->retrievePaymentRequest($paymentId);

        /** @var RetrievePaymentResponse $responseEntity */
        $responseEntity = $response->getResponseEntity();

        /** @var RetrievePaymentResultEntity $payeverPayment */
        return $responseEntity->getResult();
    }

    /**
     * Get Order entity.
     *
     * @param PaymentTransaction $paymentTransaction
     *
     * @return Order|null
     */
    private function getOrder(PaymentTransaction $paymentTransaction): ?Order
    {
        /** @var Order $entity */
        return $this->doctrineHelper->getEntityReference(
            $paymentTransaction->getEntityClass(),
            $paymentTransaction->getEntityIdentifier()
        );
    }
}
