<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Payment;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Payever\Bundle\PaymentBundle\Service\FinanceExpressConfig;
use Payever\Bundle\PaymentBundle\Service\Api\ServiceProvider;
use Payever\Bundle\PaymentBundle\Service\Generator\CheckoutGenerator;
use Payever\Bundle\PaymentBundle\Service\Generator\CustomerUserGenerator;
use Payever\Bundle\PaymentBundle\Service\Generator\OrderGenerator;
use Payever\Bundle\PaymentBundle\Service\Helper\ShoppingListHelper;
use Payever\Bundle\PaymentBundle\Service\Helper\TransactionHelper;
use Payever\Bundle\PaymentBundle\Service\Payment\Notification\NotificationRequestProcessor;
use Payever\Sdk\Core\Lock\LockInterface;
use Payever\Sdk\Payments\Enum\Status;
use Payever\Sdk\Payments\Http\MessageEntity\AddressEntity;
use Payever\Sdk\Payments\Http\MessageEntity\CartItemEntity;
use Payever\Sdk\Payments\Http\MessageEntity\RetrievePaymentResultEntity;
use Payever\Sdk\Payments\Http\ResponseEntity\RetrievePaymentResponse;
use Psr\Log\LoggerInterface;

class FinanceExpressService
{
    /**
     * @var ServiceProvider
     */
    private ServiceProvider $serviceProvider;

    /**
     * @var OrderGenerator
     */
    private OrderGenerator $orderGenerator;

    /**
     * @var CustomerUserGenerator
     */
    private CustomerUserGenerator $customerUserGenerator;

    /**
     * @var CheckoutGenerator
     */
    private CheckoutGenerator $checkoutGenerator;

    /**
     * @var TransactionHelper
     */
    private TransactionHelper $transactionHelper;

    /**
     * @var PaymentTransactionProvider
     */
    private PaymentTransactionProvider $paymentTransactionProvider;

    /**
     * @var FinanceExpressConfig
     */
    private FinanceExpressConfig $financeExpressConfig;

    /**
     * @var ShoppingListHelper
     */
    private ShoppingListHelper $shoppingListHelper;

    /**
     * @var TransactionStatusService
     */
    private TransactionStatusService $transactionStatusService;

    /**
     * @var NotificationRequestProcessor
     */
    private NotificationRequestProcessor $notificationRequestProcessor;

    /**
     * @var LockInterface
     */
    private LockInterface $lock;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param ServiceProvider $serviceProvider
     * @param OrderGenerator $orderGenerator
     * @param CustomerUserGenerator $customerUserGenerator
     * @param CheckoutGenerator $checkoutGenerator
     * @param TransactionHelper $transactionHelper
     * @param PaymentTransactionProvider $paymentTransactionProvider
     * @param FinanceExpressConfig $financeExpressConfig
     * @param ShoppingListHelper $shoppingListHelper
     * @param TransactionStatusService $transactionStatusService
     * @param NotificationRequestProcessor $notificationRequestProcessor
     * @param LockInterface $lock
     * @param LoggerInterface $logger
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ServiceProvider $serviceProvider,
        OrderGenerator $orderGenerator,
        CustomerUserGenerator $customerUserGenerator,
        CheckoutGenerator $checkoutGenerator,
        TransactionHelper $transactionHelper,
        PaymentTransactionProvider $paymentTransactionProvider,
        FinanceExpressConfig $financeExpressConfig,
        ShoppingListHelper $shoppingListHelper,
        TransactionStatusService $transactionStatusService,
        NotificationRequestProcessor $notificationRequestProcessor,
        LockInterface $lock,
        LoggerInterface $logger
    ) {
        $this->serviceProvider = $serviceProvider;
        $this->orderGenerator = $orderGenerator;
        $this->customerUserGenerator = $customerUserGenerator;
        $this->checkoutGenerator = $checkoutGenerator;
        $this->transactionHelper = $transactionHelper;
        $this->paymentTransactionProvider = $paymentTransactionProvider;
        $this->financeExpressConfig = $financeExpressConfig;
        $this->shoppingListHelper = $shoppingListHelper;
        $this->transactionStatusService = $transactionStatusService;
        $this->notificationRequestProcessor = $notificationRequestProcessor;
        $this->lock = $lock;
        $this->logger = $logger;
    }

    /**
     * Handle Payment.
     *
     * @param string $paymentId
     * @return Checkout
     * @throws \Throwable
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function handlePayment(string $paymentId): Checkout
    {
        $this->lock->acquireLock($paymentId, NotificationRequestProcessor::NOTIFICATION_LOCK_SECONDS);
        $payment = $this->retrievePayment($paymentId);

        $orderReference = $payment->getReference();
        $paymentStatus = $payment->getStatus();
        if (!$this->isSuccessfulPaymentStatus($paymentStatus)) {
            throw new \LogicException('The payment has not been successful.');
        }

        /** @var $paymentAddress AddressEntity */
        $paymentAddress = $payment->getAddress();

        $customerEmail = $payment->getCustomerEmail();
        $customerUser = $this->customerUserGenerator->getCustomerUser($customerEmail);
        if (!$customerUser) {
            $customerUser = $this->customerUserGenerator->createCustomerUser(
                $customerEmail,
                $paymentAddress->getFirstName(),
                $paymentAddress->getLastName()
            );

            $this->logger->info('Created customer user: ' .  $customerEmail, [$customerUser->getId()]);
        }

        $billingAddress = [
            'label' => '',
            'country' => $paymentAddress->getCountry(),
            'city' => $paymentAddress->getCity(),
            'region' => $paymentAddress->getRegion(),
            'street' => $paymentAddress->getStreet(),
            'street2' => $paymentAddress->getStreetNumber(),
            'postalCode' => $paymentAddress->getZipCode(),
            'firstName' => $paymentAddress->getFirstName(),
            'lastName' => $paymentAddress->getLastName(),
            'phone' => $paymentAddress->getPhone(),
        ];

        /** @var $paymentShippingAddress AddressEntity */
        $paymentShippingAddress = $payment->getShippingAddress();

        $shippingAddress = [
            'label' => '',
            'country' => $paymentShippingAddress->getCountry(),
            'city' => $paymentShippingAddress->getCity(),
            'region' => $paymentShippingAddress->getRegion(),
            'street' => $paymentShippingAddress->getStreet(),
            'street2' => $paymentShippingAddress->getStreetNumber(),
            'postalCode' => $paymentShippingAddress->getZipCode(),
            'firstName' => $paymentShippingAddress->getFirstName(),
            'lastName' => $paymentShippingAddress->getLastName(),
            'phone' => $paymentShippingAddress->getPhone(),
        ];

        $orderLines = [];
        $paymentItems = $payment->getItems();
        foreach ($paymentItems as $paymentItem) {
            /** @var CartItemEntity $paymentItem */
            $orderLines[] = $this->orderGenerator->getOrderLineItem(
                $paymentItem->getIdentifier(),
                $paymentItem->getPrice(),
                $paymentItem->getQuantity(),
                $payment->getCurrency()
            );
        }

        $order = $this->transactionHelper->getOrderByIdentifier($orderReference);
        if (!$order) {
            $shippingOption = $payment->getShippingOption();
            $shippingMethod = $shippingOption?->getCarrier();
            $shippingPrice = $shippingOption?->getPrice();

            $order = $this->orderGenerator->createOrder(
                $customerUser,
                $billingAddress,
                $shippingAddress,
                $orderReference,
                $payment->getCurrency(),
                $payment->getTotal(),
                $payment->getAmount(),
                $orderLines,
                $shippingMethod,
                $shippingPrice
            );

            $this->logger->info('Created order: ' .  $orderReference, [$order->getId()]);
        }

        $paymentTransactions = $this->paymentTransactionProvider->getPaymentTransactions(
            $order,
            [
                'action' => [
                    PaymentMethodInterface::PURCHASE,
                    PaymentMethodInterface::AUTHORIZE,
                    PaymentMethodInterface::CAPTURE
                ]
            ]
        );
        if (count($paymentTransactions) === 0) {
            $paymentMethod = $this->getPaymentMethod()->getIdentifier();
            $transactionType = $this->getTransactionStatus($paymentStatus);
            $paymentTransaction = $this->paymentTransactionProvider->createPaymentTransaction(
                $paymentMethod,
                $transactionType,
                $order
            );

            $paymentTransaction->setAmount($payment->getTotal())
                ->setCurrency($payment->getCurrency())
                ->setReference($paymentId)
                ->setTransactionOptions($payment->toArray())
                ->setSuccessful(true)
                ->setActive(true)
                ->setAction(PaymentMethodInterface::PURCHASE);

            $this->paymentTransactionProvider->savePaymentTransaction($paymentTransaction);

            $this->logger->info('Created payment transaction: ' .  $paymentTransaction->getId(), [$order->getId()]);
        }

        $this->transactionStatusService->persistTransactionStatus($payment);

        $this->lock->releaseLock($paymentId);

        $checkout = $this->checkoutGenerator->createCheckout($order);
        $this->shoppingListHelper->clear();

        return $checkout;
    }

    /**
     * Handle Notification.
     *
     * @param string $payload
     * @return void
     * @throws \Throwable
     */
    public function handleNotification(string $payload)
    {
        $this->logger->info('[FE Notification] Payload: ' . $payload);

        $data = json_decode($payload, true);
        $order = $this->transactionHelper->getOrderByIdentifier($data['data']['payment']['reference']);
        if (!$order) {
            $this->handlePayment($data['data']['payment']['id']);
        }

        $this->notificationRequestProcessor->processNotification(
            $payload
        );
    }

    /**
     * Retrieve payment.
     *
     * @param string $paymentId
     * @return RetrievePaymentResultEntity
     * @throws \Exception
     */
    public function retrievePayment(string $paymentId): RetrievePaymentResultEntity
    {
        $response = $this->serviceProvider
            ->getPaymentsApiClient()
            ->retrievePaymentRequest($paymentId);

        /** @var RetrievePaymentResponse $getTransactionEntity */
        $responseEntity = $response->getResponseEntity();

        /** @var RetrievePaymentResultEntity $getTransactionResult */
        $result = $responseEntity->getResult();

        $this->logger->debug(sprintf('Retrieve payment %s: %s', $paymentId, json_encode($result->toArray())));

        return $result;
    }

    /**
     * Get Configured Payment Method.
     *
     * @return PaymentMethodInterface
     */
    private function getPaymentMethod(): PaymentMethodInterface
    {
        $method = $this->financeExpressConfig->getConfiguredPaymentMethod();
        if (!$method) {
            throw new \InvalidArgumentException('The Payment method of the widget is not configured.');
        }

        return $method;
    }

    /**
     * Checks if the payment status is successful.
     *
     * @param string $status
     * @return bool
     */
    private function isSuccessfulPaymentStatus(string $status): bool
    {
        return in_array(
            $status,
            [
                Status::STATUS_NEW,
                Status::STATUS_IN_PROCESS,
                Status::STATUS_ACCEPTED,
                Status::STATUS_PAID,
            ]
        );
    }

    /**
     * Get Transaction status.
     *
     * @param string $status
     * @return string
     */
    private function getTransactionStatus(string $status): string
    {
        switch ($status) {
            case Status::STATUS_NEW:
            case Status::STATUS_IN_PROCESS:
                return PaymentMethodInterface::PENDING;
            case Status::STATUS_ACCEPTED:
                return PaymentMethodInterface::AUTHORIZE;
            case Status::STATUS_PAID:
                return PaymentMethodInterface::CAPTURE;
        }

        return PaymentMethodInterface::CANCEL;
    }
}
