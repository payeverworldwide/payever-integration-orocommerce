<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Helper;

use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Oro\Bundle\UserBundle\Security\UserLoader;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Oro\Bundle\UserBundle\Security\UserChecker;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Oro\Bundle\SecurityBundle\Authentication\Authenticator\UsernamePasswordOrganizationAuthenticator;
use Oro\Bundle\SecurityBundle\Authentication\Provider\UsernamePasswordOrganizationAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Psr\Container\ContainerInterface;

class AuthHelper
{
    private ContainerInterface $container;

    /**
     * @var UserLoader
     */
    private UserLoader $userLoader;

    /**
     * @var UserChecker|UserCheckerInterface
     */
    private $userChecker;

    /**
     * @var EventDispatcher
     */
    private EventDispatcher $eventDispatcher;

    private string $authenticationProviderKey;

    public function __construct(
        ContainerInterface $container,
        UserLoader $userLoader,
        UserCheckerInterface $userChecker,
        EventDispatcher $eventDispatcher,
        string $authenticationProviderKey
    ) {
        $this->container = $container;
        $this->userLoader = $userLoader;
        $this->userChecker = $userChecker;
        $this->eventDispatcher = $eventDispatcher;
        $this->authenticationProviderKey = $authenticationProviderKey;
    }

    /**
     * Validates user credentials.
     *
     * @param string $login The username.
     * @param string $password The password.
     *
     * @return bool Returns true if the user credentials are valid, false otherwise.
     */
    public function validateUserCredentials(string $login, string $password): bool
    {
        if ($this->container->has('payever.api.frontend.authenticator')) {
            return $this->validateUserCredentialsWithAuthenticator($login, $password);
        }

        return $this->validateUserWithPassword($login, $password);
    }

    private function validateUserCredentialsWithAuthenticator(string $login, string $password): bool
    {
        /** @var Passport $passport */
        $userBadge = new UserBadge($login, $this->userLoader->loadUser(...));
        $passport = new Passport($userBadge, new PasswordCredentials($password), [new RememberMeBadge()]);

        try {
            $user = $passport->getUser();
            $this->userChecker->checkPreAuth($user);

            /** @var UsernamePasswordOrganizationAuthenticator|AuthenticatorInterface $authenticator */
            $authenticator = $this->container->get('payever.api.frontend.authenticator');
            $event = new CheckPassportEvent($authenticator, $passport);
            $this->eventDispatcher->dispatch($event);
            $this->userChecker->checkPostAuth($user);
        } catch (AuthenticationException $exception) {
            return false;
        }

        return true;
    }

    private function validateUserWithPassword(string $login, string $password): bool
    {
        $user = $this->userLoader->loadUser($login);
        if (!$user) {
            return false;
        }

        $token = new UsernamePasswordToken(
            $user,
            $password,
            $this->authenticationProviderKey
        );

        /** @var UsernamePasswordOrganizationAuthenticationProvider|AuthenticationProviderInterface $authenticationProvider */
        $authenticationProvider = $this->container->get('payever.api.frontend.authentication_provider');
        if (!$authenticationProvider->supports($token)) {
            throw new \LogicException(sprintf(
                'Invalid authentication provider. The provider key is "%s".',
                $this->authenticationProviderKey
            ));
        }

        try {
            $this->userChecker->checkPreAuth($user);
            /** @var UsernamePasswordToken $result */
            $result = $authenticationProvider->authenticate($token);
            $this->userChecker->checkPostAuth($user);

            $encoder = new MessageDigestPasswordEncoder('sha512', true, 5000);
            if (
                !$result->isAuthenticated() ||
                !hash_equals($encoder->encodePassword($password, $user->getSalt()), $user->getPassword())
            ) {
                throw new AccessDeniedException('The user authentication fails');
            }

            return true;
        } catch (AuthenticationException $exception) {
            return false;
        }
    }
}
