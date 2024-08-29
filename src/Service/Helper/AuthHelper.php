<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Helper;

use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class AuthHelper
{
    private string $authenticationProviderKey;
    private AuthenticationProviderInterface $authenticationProvider;

    public function __construct(
        string $authenticationProviderKey,
        AuthenticationProviderInterface $authenticationProvider,
    ) {
        $this->authenticationProviderKey = $authenticationProviderKey;
        $this->authenticationProvider = $authenticationProvider;
    }

    /**
     * Validates user credentials.
     *
     * @param string $user The username.
     * @param string $password The password.
     *
     * @return bool Returns true if the user credentials are valid, false otherwise.
     */
    public function validateUserCredentials(string $user, string $password): bool
    {
        $token = new UsernamePasswordToken(
            $user,
            $password,
            $this->authenticationProviderKey
        );

        if (!$this->authenticationProvider->supports($token)) {
            throw new \LogicException(sprintf(
                'Invalid authentication provider. The provider key is "%s".',
                $this->authenticationProviderKey
            ));
        }

        try {
            return (bool) $this->authenticationProvider->authenticate($token);
        } catch (AuthenticationException $e) {
            return false;
        }
    }
}
