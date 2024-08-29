<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Provider;

use Oro\Bundle\LocaleBundle\Twig\LocaleExtension;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Payever\Bundle\PaymentBundle\Form\Entity\OrderClaim;
use Payever\Bundle\PaymentBundle\Form\Entity\OrderLineItem;
use Payever\Bundle\PaymentBundle\Form\Entity\OrderPayment;
use Payever\Bundle\PaymentBundle\Service\Helper\OrderHelper;
use Payever\Bundle\PaymentBundle\Service\Management\OrderManager;
use Payever\Bundle\PaymentBundle\Service\Payment\Action\CancelAction;
use Payever\Bundle\PaymentBundle\Service\Payment\Action\ClaimAction;
use Payever\Bundle\PaymentBundle\Service\Payment\Action\ClaimUploadAction;
use Payever\Bundle\PaymentBundle\Service\Payment\Action\SettleAction;
use Payever\Bundle\PaymentBundle\Service\Payment\Action\InvoiceAction;
use Payever\Bundle\PaymentBundle\Service\Payment\Action\ShippingAction;
use Payever\Bundle\PaymentBundle\Service\Payment\Action\RefundAction;
use Payever\Bundle\PaymentBundle\Service\Payment\InvoiceService;
use Payever\Bundle\PaymentBundle\Service\Payment\TransactionBuilderService;
use Payever\Sdk\Core\Lock\FileLock;
use Payever\Sdk\Payments\Notification\NotificationRequestProcessor;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\Form;
use Symfony\Contracts\Translation\TranslatorInterface;

class PaymentActionProvider
{
    private LocaleExtension $localeExtension;
    private OrderManager $orderManager;
    private OrderHelper $orderHelper;
    private TransactionBuilderService $transactionBuilder;
    private FileLock $lock;
    private CancelAction $cancelAction;
    private ShippingAction $shippingAction;
    private RefundAction $refundAction;
    private SettleAction $settleAction;
    private ClaimAction $claimAction;
    private ClaimUploadAction $claimUploadAction;
    private InvoiceAction $invoiceAction;
    private TranslatorInterface $translationService;
    private InvoiceService $invoiceService;
    private LoggerInterface $logger;

    /**
     * @param LocaleExtension $localeExtension
     * @param OrderManager $orderManager
     * @param OrderHelper $orderHelper
     * @param TransactionBuilderService $transactionBuilder
     * @param FileLock $lock
     * @param CancelAction $cancelAction
     * @param ShippingAction $shippingAction
     * @param RefundAction $refundAction
     * @param SettleAction $settleAction
     * @param ClaimAction $claimAction
     * @param ClaimUploadAction $claimUploadAction
     * @param InvoiceAction $invoiceAction
     * @param InvoiceService $invoiceService
     * @param TranslatorInterface $translationService
     * @param LoggerInterface $logger
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        LocaleExtension $localeExtension,
        OrderManager $orderManager,
        OrderHelper $orderHelper,
        TransactionBuilderService $transactionBuilder,
        FileLock $lock,
        CancelAction $cancelAction,
        ShippingAction $shippingAction,
        RefundAction $refundAction,
        SettleAction $settleAction,
        ClaimAction $claimAction,
        ClaimUploadAction $claimUploadAction,
        InvoiceAction $invoiceAction,
        InvoiceService $invoiceService,
        TranslatorInterface $translationService,
        LoggerInterface $logger
    ) {
        $this->localeExtension = $localeExtension;
        $this->orderManager = $orderManager;
        $this->orderHelper = $orderHelper;
        $this->transactionBuilder = $transactionBuilder;
        $this->lock = $lock;
        $this->cancelAction = $cancelAction;
        $this->shippingAction = $shippingAction;
        $this->refundAction = $refundAction;
        $this->settleAction = $settleAction;
        $this->claimAction = $claimAction;
        $this->claimUploadAction = $claimUploadAction;
        $this->invoiceAction = $invoiceAction;
        $this->invoiceService = $invoiceService;
        $this->translationService = $translationService;
        $this->logger = $logger;
    }

    /**
     * @see actions.yml
     * @param Order $order
     *
     * @return OrderPayment
     */
    public function getOrderPayment(Order $order): OrderPayment
    {
        $orderTotal = $this->orderManager->getOrderTotal($order);

        $entity = new OrderPayment();
        $entity->setTotal((float) $order->getTotal())
            ->setCurrency($order->getCurrency())
            ->setCurrencySymbol($this->localeExtension->getCurrencySymbolByCurrency($order->getCurrency()))
            ->setTotalCancelled($orderTotal->getCancelledTotal())
            ->setTotalCaptured($orderTotal->getCapturedTotal())
            ->setTotalRefunded($orderTotal->getRefundedTotal());

        $orderItemEntities = [];
        $orderItems = $this->orderManager->getOrderItems($order);
        foreach ($orderItems as $item) {
            $orderItemEntity = new OrderLineItem();
            $orderItemEntity->setId($item->getId())
                ->setOrderId($order->getId())
                ->setItemType($item->getItemType())
                ->setItemReference($item->getItemReference())
                ->setName($item->getName())
                ->setUnitPrice($item->getUnitPrice())
                ->setTotalPrice($item->getTotalPrice())
                ->setQuantity($item->getQuantity())
                ->setQtyCaptured($item->getQtyCaptured())
                ->setQtyCancelled($item->getQtyCancelled())
                ->setQtyRefunded($item->getQtyRefunded());

            $orderItemEntities[] = $orderItemEntity;
        }

        $entity->setOrderLines($orderItemEntities);

        return $entity;
    }

    /**
     * Handle Refund Form.
     * @see actions.yml
     *
     * @param Form $form
     *
     * @return array
     * @throws \Throwable
     */
    public function processRefundForm(Form $form): array
    {
        /** @var Order $order */
        $order = $form->getData()->data;
        $orderId = $order->getIdentifier();
        $amount = 0;
        $items = [];
        /** @var OrderPayment $entity */
        $entity = $form->getData()->peRefund;
        foreach ($entity->getOrderLines() as $orderLineItem) {
            /** @var OrderLineItem $orderLineItem */
            $items[$orderLineItem->getItemReference()] = $orderLineItem->getQuantityToRefund();
            $amount += $orderLineItem->getUnitPrice() * $orderLineItem->getQuantityToRefund();
        }

        $this->logger->info('processRefundForm', [$orderId, $items, $amount]);

        $paymentId = $this->orderHelper->getPaymentId($order);
        $this->lock->acquireLock($paymentId, NotificationRequestProcessor::NOTIFICATION_LOCK_SECONDS);

        try {
            $result = $this->refundAction->executeItems($order, $items);
            $paymentTransaction = $this->transactionBuilder->registerRefundTransaction(
                $order,
                $paymentId,
                $amount,
                $result->getResult()->toArray()
            );

            $this->logger->info('Transaction has been registered', [$paymentTransaction->getId()]);
        } catch (\Exception $exception) {
            $this->logger->critical('processRefundForm Exception: ' . $exception->getMessage());
            $this->lock->releaseLock($paymentId);

            return [
                'success' => false,
                'message' => $this->translationService->trans(
                    'payever.actions.refund.errorMessage',
                    ['{error}' => $exception->getMessage()]
                ),
            ];
        }

        $this->lock->releaseLock($paymentId);

        return [
            'success' => true,
            'message' => $this->translationService->trans('payever.actions.refund.successMessage'),
        ];
    }

    /**
     * Handle Cancel Form.
     * @see actions.yml
     *
     * @param Form $form
     *
     * @return array
     * @throws \Throwable
     */
    public function processCancelForm(Form $form): array
    {
        /** @var Order $order */
        $order = $form->getData()->data;
        $orderId = $order->getIdentifier();
        $amount = 0;
        $items = [];
        /** @var OrderPayment $entity */
        $entity = $form->getData()->peCancel;
        foreach ($entity->getOrderLines() as $orderLineItem) {
            /** @var OrderLineItem $orderLineItem */
            $items[$orderLineItem->getItemReference()] = $orderLineItem->getQuantityToCancel();
            $amount += $orderLineItem->getUnitPrice() * $orderLineItem->getQuantityToCancel();
        }

        $this->logger->info('processCancelForm', [$orderId, $items, $amount]);

        $paymentId = $this->orderHelper->getPaymentId($order);
        $this->lock->acquireLock($paymentId, NotificationRequestProcessor::NOTIFICATION_LOCK_SECONDS);

        try {
            $result = $this->cancelAction->executeItems($order, $items);
            $paymentTransaction = $this->transactionBuilder->registerCancelTransaction(
                $order,
                $paymentId,
                $amount,
                $result->getResult()->toArray()
            );

            $this->logger->info('Transaction has been registered', [$paymentTransaction->getId()]);
        } catch (\Exception $exception) {
            $this->logger->critical('processCancelForm Exception: ' . $exception->getMessage());
            $this->lock->releaseLock($paymentId);

            return [
                'success' => false,
                'message' => $this->translationService->trans(
                    'payever.actions.cancel.errorMessage',
                    ['{error}' => $exception->getMessage()]
                ),
            ];
        }

        $this->lock->releaseLock($paymentId);

        return [
            'success' => true,
            'message' => $this->translationService->trans('payever.actions.cancel.successMessage'),
        ];
    }

    /**
     * Handle Ship Form.
     * @see actions.yml
     *
     * @param Form $form
     *
     * @return array
     * @throws \Throwable
     */
    public function processShipForm(Form $form): array
    {
        /** @var Order $order */
        $order = $form->getData()->data;
        $orderId = $order->getIdentifier();
        $amount = 0;
        $items = [];
        /** @var OrderPayment $entity */
        $entity = $form->getData()->peShip;
        foreach ($entity->getOrderLines() as $orderLineItem) {
            /** @var OrderLineItem $orderLineItem */
            $items[$orderLineItem->getItemReference()] = $orderLineItem->getQuantityToCapture();
            $amount += $orderLineItem->getUnitPrice() * $orderLineItem->getQuantityToCapture();
        }

        $this->logger->info('processShipForm', [$orderId, $items, $amount]);

        $paymentId = $this->orderHelper->getPaymentId($order);
        $this->lock->acquireLock($paymentId, NotificationRequestProcessor::NOTIFICATION_LOCK_SECONDS);

        try {
            $result = $this->shippingAction->executeItemsWithDetails(
                $order,
                $items,
                $entity->getTrackingNumber(),
                $entity->getTrackingUrl(),
                $entity->getShippingDate()
            );
            $paymentTransaction = $this->transactionBuilder->registerCaptureTransaction(
                $order,
                $paymentId,
                $amount,
                $result->getResult()->toArray()
            );

            $this->logger->info('Transaction has been registered', [$paymentTransaction->getId()]);
        } catch (\Exception $exception) {
            $this->logger->critical('processShipForm Exception: ' . $exception->getMessage());
            $this->lock->releaseLock($paymentId);

            return [
                'success' => false,
                'message' => $this->translationService->trans(
                    'payever.actions.ship.errorMessage',
                    ['{error}' => $exception->getMessage()]
                ),
            ];
        }

        $this->lock->releaseLock($paymentId);

        return [
            'success' => true,
            'message' => $this->translationService->trans('payever.actions.ship.successMessage'),
        ];
    }

    /**
     * Handle Settle Form.
     * @see actions.yml
     *
     * @param Form $form
     *
     * @return array
     * @throws \Throwable
     */
    public function processSettleForm(Form $form): array
    {
        /** @var PaymentTransaction $paymentTransaction */
        $paymentTransaction = $form->getData()->data;
        $orderId = $paymentTransaction->getEntityIdentifier();

        $this->logger->info('processSettleForm', [$orderId]);

        $order = $this->orderHelper->getOrderByID($orderId);
        $paymentId = $this->orderHelper->getPaymentId($order);
        $this->lock->acquireLock($paymentId, NotificationRequestProcessor::NOTIFICATION_LOCK_SECONDS);

        try {
            $result = $this->settleAction->execute($order);
            $paymentTransaction = $this->transactionBuilder->registerSettleTransaction(
                $order,
                $paymentId,
                (float) $order->getTotal(),
                $result->getResult()->toArray()
            );

            $this->logger->info('Transaction has been settled', [$paymentTransaction->getId()]);
        } catch (\Exception $exception) {
            $this->logger->critical('processSettleForm Exception: ' . $exception->getMessage());
            $this->lock->releaseLock($paymentId);

            return [
                'success' => false,
                'message' => $this->translationService->trans(
                    'payever.actions.settle.errorMessage',
                    ['{error}' => $exception->getMessage()]
                ),
            ];
        }

        $this->lock->releaseLock($paymentId);

        return [
            'success' => true,
            'message' => $this->translationService->trans('payever.actions.settle.successMessage'),
        ];
    }

    /**
     * Handle Claim Upload Form.
     * @see actions.yml
     *
     * @param Form $form
     *
     * @return array
     * @throws \Throwable
     */
    public function processClaimUploadForm(Form $form): array
    {
        /** @var Order $order */
        $order = $form->getData()->data;
        $orderId = $order->getIdentifier();

        $entity = $form->getData()->peClaimUpload;

        $this->logger->info('processClaimUploadForm', [$orderId]);

        $paymentId = $this->orderHelper->getPaymentId($order);
        $this->lock->acquireLock($paymentId, NotificationRequestProcessor::NOTIFICATION_LOCK_SECONDS);

        try {
            $result = $this->claimUploadAction->execute($order, $entity);
            $paymentTransaction = $this->transactionBuilder->registerClaimUploadTransaction(
                $order,
                $paymentId,
                $result->getResult()->toArray()
            );

            $this->logger->info('Transaction has been registered', [$paymentTransaction->getId()]);
        } catch (\Exception $exception) {
            $this->logger->critical('processClaimForm Exception: ' . $exception->getMessage());
            $this->lock->releaseLock($paymentId);

            return [
                'success' => false,
                'message' => $this->translationService->trans(
                    'payever.actions.claim.errorMessage',
                    ['{error}' => $exception->getMessage()]
                ),
            ];
        }

        $this->lock->releaseLock($paymentId);

        return [
            'success' => true,
            'message' => $this->translationService->trans('payever.actions.claim.successMessage'),
        ];
    }

    /**
     * Handle Claim Form.
     * @see actions.yml
     *
     * @param Form $form
     *
     * @return array
     * @throws \Throwable
     */
    public function processClaimForm(Form $form): array
    {
        /** @var Order $order */
        $order = $form->getData()->data;
        $orderId = $order->getIdentifier();

        $this->logger->info('processClaimForm', [$orderId]);

        $paymentId = $this->orderHelper->getPaymentId($order);
        $this->lock->acquireLock($paymentId, NotificationRequestProcessor::NOTIFICATION_LOCK_SECONDS);

        try {
            $result = $this->claimAction->execute($order, $form);
            $paymentTransaction = $this->transactionBuilder->registerClaimTransaction(
                $order,
                $paymentId,
                $result->getResult()->toArray()
            );

            $this->logger->info('Transaction has been registered', [$paymentTransaction->getId()]);
        } catch (\Exception $exception) {
            $this->logger->critical('processClaimForm Exception: ' . $exception->getMessage());
            $this->lock->releaseLock($paymentId);

            return [
                'success' => false,
                'message' => $this->translationService->trans(
                    'payever.actions.claim.errorMessage',
                    ['{error}' => $exception->getMessage()]
                ),
            ];
        }

        $this->lock->releaseLock($paymentId);

        return [
            'success' => true,
            'message' => $this->translationService->trans('payever.actions.claim.successMessage'),
        ];
    }

    /**
     * Handle Invoice Form.
     * @param Form $form
     * @param float $amount
     *
     * @return array
     * @throws \Throwable
     * @see actions.yml
     *
     */
    public function processInvoiceForm(Form $form, float $amount): array
    {
        /** @var PaymentTransaction $paymentTransaction */
        $paymentTransaction = $form->getData()->data;
        $orderId = $paymentTransaction->getEntityIdentifier();

        $this->logger->info('processInvoiceForm', [$orderId, $amount]);

        $order = $this->orderHelper->getOrderByID($orderId);
        $paymentId = $this->orderHelper->getPaymentId($order);
        $this->lock->acquireLock($paymentId, NotificationRequestProcessor::NOTIFICATION_LOCK_SECONDS);

        try {
            $result = $this->invoiceAction->execute($order, $amount);
            $paymentTransaction = $this->transactionBuilder->registerInvoiceTransaction(
                $order,
                $paymentId,
                $amount,
                $result->getResult()->toArray()
            );

            $this->logger->info('Transaction has been registered', [$paymentTransaction->getId()]);
        } catch (\Exception $exception) {
            $this->logger->critical('processInvoiceForm Exception: ' . $exception->getMessage());
            $this->lock->releaseLock($paymentId);

            return [
                'success' => false,
                'message' => $this->translationService->trans(
                    'payever.actions.invoice.errorMessage',
                    ['{error}' => $exception->getMessage()]
                ),
            ];
        }

        $this->lock->releaseLock($paymentId);

        return [
            'success' => true,
            'message' => $this->translationService->trans('payever.actions.invoice.successMessage'),
        ];
    }

    /**
     * Handle Invoice File.
     * @see actions.yml
     *
     * @param Form $form
     *
     * @return array
     * @throws \Throwable
     */
    public function processInvoicePdf(Form $form): array
    {
        /** @var Order $order */
        $paymentTransaction = $form->getData()->data;
        $orderId = $paymentTransaction->getEntityIdentifier();

        $this->logger->info('processInvoiceForm', [$orderId]);

        $order = $this->orderHelper->getOrderByID($orderId);
        $paymentId = $this->orderHelper->getPaymentId($order);
        $externalId = $this->orderHelper->getExternalId($order);

        $params = [
            InvoiceService::INVOICE_NUMBER => $form->getData()->invoiceNumber,
            InvoiceService::INVOICE_DATE => $form->getData()->invoiceDate,
            InvoiceService::INVOICE_COMMENT => $form->getData()->invoiceComment,
            InvoiceService::INVOICE_SEND => $form->getData()->invoiceSend,
            InvoiceService::INVOICE_PAYMENT_ID => $paymentId,
            InvoiceService::INVOICE_EXTERNAL_ID => $externalId,
        ];

        try {
            $this->invoiceService->createInvoice($order, $params);

            $this->logger->info('Invoice document has been created', [$paymentTransaction->getId()]);
        } catch (\Exception $exception) {
            $this->logger->critical('processInvoiceForm Exception: ' . $exception->getMessage());
            $this->lock->releaseLock($paymentId);

            return [
                'success' => false,
                'message' => $this->translationService->trans(
                    'payever.actions.invoice.errorMessage',
                    ['{error}' => $exception->getMessage()]
                ),
            ];
        }

        $this->lock->releaseLock($paymentId);

        return [
            'success' => true,
            'message' => $this->translationService->trans('payever.actions.invoice.successMessage'),
        ];
    }
}
