<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Shipping;

use Payever\Bundle\PaymentBundle\Service\Generator\CustomerUserGenerator;
use Payever\Bundle\PaymentBundle\Service\Generator\OrderGenerator;

class ShippingCalcService
{
    /**
     * @var CustomerUserGenerator
     */
    private CustomerUserGenerator $customerUserGenerator;

    /**
     * @var OrderGenerator
     */
    private OrderGenerator $orderGenerator;

    /**
     * @var ShippingCostService
     */
    private ShippingCostService $shippingCostService;

    /**
     * @param CustomerUserGenerator $customerUserGenerator
     * @param OrderGenerator $orderGenerator
     * @param ShippingCostService $shippingCostService
     */
    public function __construct(
        CustomerUserGenerator $customerUserGenerator,
        OrderGenerator $orderGenerator,
        ShippingCostService $shippingCostService
    ) {
        $this->customerUserGenerator = $customerUserGenerator;
        $this->orderGenerator = $orderGenerator;
        $this->shippingCostService = $shippingCostService;
    }

    /**
     * Get Shipping rates.
     *
     * @param array $cartItems
     * @param string $customerUserEmail
     * @param string $shopperPhone
     * @param array $shippingAddress
     * @param string $currency
     * @param string|null $paymentMethod
     * @return array
     */
    public function getShippingRates(
        array $cartItems,
        string $customerUserEmail,
        string $shopperPhone,
        array $shippingAddress,
        string $currency,
        ?string $paymentMethod
    ): array {
        // Get Customer User
        $customerUser = $this->customerUserGenerator->getCustomerUser($customerUserEmail);
        if (!$customerUser) {
            $customerUser = $this->customerUserGenerator->generateGuestCustomer($customerUserEmail);
        }

        // Create Address
        $shippingAddress = $this->orderGenerator->createOrderAddress(
            [
                'label' => '',
                'country' => $shippingAddress['country'],
                'city' => $shippingAddress['city'],
                'region' => $shippingAddress['region'],
                'street' => $shippingAddress['line1'],
                'street2' => $shippingAddress['line2'],
                'postalCode' => $shippingAddress['zipCode'],
                'firstName' => $shippingAddress['firstName'],
                'lastName' => $shippingAddress['lastName'],
                'phone' => $shopperPhone,
            ]
        );

        // Estimate Shipping Rates
        $shippingRates = $this->shippingCostService->getShippingRates(
            $customerUser,
            $shippingAddress,
            $cartItems,
            $currency,
            $paymentMethod
        );

        $result = [];
        foreach ($shippingRates as $shippingRate) {
            $result[] = [
                'name'      => $shippingRate['label'],
                'countries' => [$shippingAddress->getCountry()->getIso2Code()],
                'reference' => $shippingRate['identifier'],
                'price'     => $shippingRate['types']['primary']['price']['value'],
            ];
        }

        return $result;
    }
}
