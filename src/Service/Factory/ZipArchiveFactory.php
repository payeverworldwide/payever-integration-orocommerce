<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Factory;

use ZipArchive;

class ZipArchiveFactory
{
    /**
     * @return ZipArchive
     */
    public function create(): ZipArchive
    {
        return new ZipArchive();
    }
}
