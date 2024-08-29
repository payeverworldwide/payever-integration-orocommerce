<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Helper;

use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AddressBundle\Entity\Repository\RegionRepository;
use Oro\Bundle\AddressBundle\Utils\AddressApiUtils;

class AddressHelper extends AddressApiUtils
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
     * Constructor.
     *
     * @param ObjectManager $manager
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(
        ObjectManager $manager,
        ManagerRegistry $managerRegistry
    ) {
        $this->manager = $manager;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * Resolve Region.
     *
     * @param string $countryCode
     * @param string $region
     * @return Region|null
     */
    public function resolveRegion(string $countryCode, string $region): ?Region
    {
        $combinedCode = self::getRegionCombinedCodeByCode($countryCode, $region, $this->manager);
        if (!$combinedCode) {
            $combinedCode = self::getRegionCombinedCodeByName($countryCode, $region, $this->manager);
        }

        if (!$combinedCode) {
            return null;
        }

        /** @var RegionRepository $repository */
        $repository = $this->managerRegistry
            ->getManagerForClass('OroAddressBundle:Region')
            ->getRepository('OroAddressBundle:Region');

        return $repository->findOneBy(['combinedCode' => $combinedCode]);
    }
}
