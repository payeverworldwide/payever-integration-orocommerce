<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Generator;

use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\UserBundle\Entity\User;
use Payever\Bundle\PaymentBundle\Service\Helper\DataHelper;

class CustomerUserGenerator
{
    /**
     * @var ObjectManager
     */
    private ObjectManager $manager;

    /**
     * @var ManagerRegistry
     */
    private ManagerRegistry $managerRegistry;

    /**
     * @var DataHelper
     */
    private DataHelper $dataHelper;

    /**
     * Constructor.
     *
     * @param ObjectManager $manager
     * @param ManagerRegistry $managerRegistry
     * @param DataHelper $dataHelper
     */
    public function __construct(
        ObjectManager $manager,
        ManagerRegistry $managerRegistry,
        DataHelper $dataHelper
    ) {
        $this->manager = $manager;
        $this->managerRegistry = $managerRegistry;
        $this->dataHelper = $dataHelper;
    }

    /**
     * Create Guest Customer account.
     *
     * @param string $email
     * @param string $firstName
     * @param string $lastName
     * @return CustomerUser
     */
    public function createCustomerUser(string $email, string $firstName, string $lastName): CustomerUser
    {
        $customerUser = new CustomerUser();
        $customerUser->setCustomer((new Customer())->setName($firstName . ' ' . $lastName))
            ->setPassword(sha1(uniqid($email)))
            ->setIsGuest(true)
            ->setConfirmed(true)
            ->setEnabled(true)
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setEmail($email)
            ->setOwner($this->getDefaultUserOwner())
            ->setWebsite($this->dataHelper->getWebsite())
            ->addUserRole($this->getRole('ROLE_FRONTEND_ANONYMOUS'));

        $this->manager->persist($customerUser);
        $this->manager->flush($customerUser);

        return $customerUser;
    }

    /**
     * Returns Anonymous Customer.
     *
     * @param string $email
     * @return CustomerUser
     */
    public function generateGuestCustomer(string $email): CustomerUser
    {
        $customerUser = new CustomerUser();
        $customerUser->setCustomer((new Customer())->setName('Guest ' . $email))
            ->setPassword(sha1(uniqid($email)))
            ->setIsGuest(true)
            ->setConfirmed(true)
            ->setEnabled(true)
            ->setEmail($email)
            ->setOwner($this->getDefaultUserOwner())
            ->setWebsite($this->dataHelper->getWebsite())
            ->addUserRole($this->getRole('ROLE_FRONTEND_ANONYMOUS'));

        return $customerUser;
    }

    /**
     * Get Customer by email.
     *
     * @param string $email
     * @return CustomerUser|null
     */
    public function getCustomerUser(string $email): ?CustomerUser
    {
        return $this->getCustomerUserRepository()->findOneBy(['emailLowercase' => strtolower($email)]);
    }

    /**
     * @param string $roleName
     * @return CustomerUserRole|null
     */
    private function getRole(string $roleName): ?CustomerUserRole
    {
        return $this->managerRegistry->getRepository(CustomerUserRole::class)->findOneBy(['role' => $roleName]);
    }

    /**
     * @return EntityRepository
     */
    private function getCustomerUserRepository(): EntityRepository
    {
        return $this->managerRegistry
            ->getManagerForClass(CustomerUser::class)
            ->getRepository(CustomerUser::class);
    }

    /**
     * @return User
     */
    private function getDefaultUserOwner(): User
    {
        $userRepository = $this->managerRegistry
            ->getManagerForClass(User::class)
            ->getRepository(User::class);

        return $userRepository->findOneBy([], ['id' => 'ASC']);
    }
}
