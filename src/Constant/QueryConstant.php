<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Constant;

class QueryConstant
{
    public const PARAMETER_ACCESS_ID = 'accessIdentifier';
    public const PARAMETER_ACCESS_TOKEN = 'accessToken';
    public const PARAMETER_PAYMENT_ID = 'paymentId';
    public const PARAMETER_TYPE = 'type';
    public const PARAMETER_ORDER_REFERENCE = 'reference';
    public const PARAMETER_CART = 'cart';
    public const PAYMENT_ID_PLACEHODLER = '--PAYMENT-ID--';

    public const HEADER_SIGNATURE = 'X-PAYEVER-SIGNATURE';

    public const CALLBACK_TYPE_SUCCESS = 'success';
    public const CALLBACK_TYPE_FINISH = 'finish';
    public const CALLBACK_TYPE_CANCEL = 'cancel';
    public const CALLBACK_TYPE_FAILURE = 'failure';
    public const CALLBACK_TYPE_PENDING = 'pending';
}
