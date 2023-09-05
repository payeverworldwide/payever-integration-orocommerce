<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Payever\Bundle\PaymentBundle\Entity\PayeverSettings;

class PayeverSettingsRepository extends EntityRepository
{
    /**
     * @return PayeverSettings[]
     */
    public function getEnabledSettings()
    {
        return $this->createQueryBuilder('settings')
            ->innerJoin('settings.channel', 'channel')
            ->andWhere('channel.enabled = true')
            ->getQuery()
            ->getResult();
    }
}
