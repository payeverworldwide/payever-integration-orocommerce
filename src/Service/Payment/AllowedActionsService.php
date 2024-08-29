<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Payment;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Payever\Bundle\PaymentBundle\Service\Api\ServiceProvider;
use Payever\Bundle\PaymentBundle\Service\Helper\TransactionHelper;
use Payever\Sdk\Payments\Action\ActionDeciderInterface;
use Payever\Sdk\Payments\Http\MessageEntity\GetTransactionResultEntity;
use Payever\Sdk\Payments\Http\ResponseEntity\GetTransactionResponse;
use Psr\Log\LoggerInterface;

class AllowedActionsService
{
    private array $payments = [];
    private ServiceProvider $serviceProvider;
    private TransactionHelper $transactionHelper;
    private LoggerInterface $logger;

    public function __construct(
        ServiceProvider $serviceProvider,
        TransactionHelper $transactionHelper,
        LoggerInterface $logger
    ) {
        $this->serviceProvider = $serviceProvider;
        $this->transactionHelper = $transactionHelper;
        $this->logger = $logger;
    }

    /**
     * Checks if specified action is allowed.
     *
     * @param PaymentTransaction $paymentTransaction
     * @param string $action
     *
     * @return bool
     * @throws \Exception
     */
    public function isActionAllowed(PaymentTransaction $paymentTransaction, string $action): bool
    {
        // @todo Check if the payment transaction relates to Payever
        $paymentId = $this->transactionHelper->getPaymentId($paymentTransaction);
        if (!$paymentId) {
            $this->logger->critical(
                'Payment transaction ' . $paymentTransaction->getId() . ' does not have payment ID.'
            );

            return false;
        }

        $actions = $this->getAllowedActions($paymentId);
        $result = isset($actions[$action]) && $actions[$action];

        $this->logger->debug(
            sprintf(
                'Payment %s. Action %s is %s',
                $paymentId,
                $action,
                $result ? 'enabled' : 'disabled'
            )
        );

        return $result;
    }

    /**
     * Checks if specified action is allowed.
     * @param Order $order
     * @param string $action
     *
     * @return bool
     * @throws \Exception
     */
    public function isOrderActionAllowed(Order $order, string $action): bool
    {
        $this->logger->debug('isOrderActionAllowed', [$order->getId(), $action]);

        $paymentTransaction = null;
        $actions = [PaymentMethodInterface::AUTHORIZE, PaymentMethodInterface::CAPTURE];
        foreach ($actions as $item) {
            try {
                $paymentTransaction = $this->transactionHelper->getPaymentTransaction(
                    $order,
                    $item
                );

                break;
            } catch (\InvalidArgumentException $exception) {
                continue;
            }
        }

        if (!$paymentTransaction) {
            $this->logger->critical(
                'Order ' . $order->getId() . ' does not have any authorized or captured transactions.'
            );

            return false;
        }

        $result = $this->isActionAllowed($paymentTransaction, $action);
        $this->logger->debug(
            'isOrderActionAllowed: ' . var_export($result, true),
            [$order->getId(), $action]
        );

        return $result;
    }

    /**
     * Get Allowed actions.
     *
     * @param string $paymentId
     *
     * @return array
     * @throws \Exception
     */
    private function getAllowedActions(string $paymentId): array
    {
        if (isset($this->payments[$paymentId])) {
            return $this->payments[$paymentId];
        }

        $getTransactionResponse = $this->serviceProvider
            ->getPaymentsApiClient()
            ->getTransactionRequest($paymentId);

        /** @var GetTransactionResponse $getTransactionEntity */
        $getTransactionEntity = $getTransactionResponse->getResponseEntity();

        /** @var GetTransactionResultEntity $getTransactionResult */
        $getTransactionResult = $getTransactionEntity->getResult();

        $actions = $getTransactionResult->getActions();
        $this->logger->debug(sprintf('Actions for %s: %s', $paymentId, json_encode($actions)));

        $allowedActions = [];
        foreach ($actions as $action) {
            if (!$action->enabled) {
                continue;
            }

            $allowedActions[$action->action] = $action->enabled;
            if ($action->action == ActionDeciderInterface::ACTION_CANCEL) {
                $allowedActions['partialCancel'] = $action->partialAllowed;
            }

            if ($action->action == ActionDeciderInterface::ACTION_REFUND) {
                $allowedActions['partialRefund'] = $action->partialAllowed;
            }

            if ($action->action == ActionDeciderInterface::ACTION_SHIPPING_GOODS) {
                $allowedActions['partialShipping'] = $action->partialAllowed;
            }
        }

        $this->payments[$paymentId] = $allowedActions;

        return $allowedActions;
    }
}
