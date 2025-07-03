<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @codeCoverageIgnore
 */
class PayeverPaymentBundle extends Bundle
{
    /**
     * @param ContainerBuilder $container
     * @return void
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function build(ContainerBuilder $container): void
    {
        // Make public aliases for authorization. They are used by `AuthHelper` class
        if (class_exists('\Oro\Bundle\SecurityBundle\Authentication\Authenticator\UsernamePasswordOrganizationAuthenticator')) { //@phpcs:ignore
            // OroCommerce 6
            $container
                ->setAlias(
                    'payever.api.frontend.authenticator',
                    'oro_customer.api.frontend.authenticator'
                )
                ->setPublic(true);
        } else {
            // OroCommerce 5
            $container
                ->setAlias(
                    'payever.api.frontend.authentication_provider',
                    'oro_customer.api.frontend.authentication_provider'
                )
                ->setPublic(true);
        }

        parent::build($container);
    }
}
