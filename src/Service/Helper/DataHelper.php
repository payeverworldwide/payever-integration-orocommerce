<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Helper;

use Oro\Bundle\PlatformBundle\Provider\PackageProvider;
use Oro\Bundle\PlatformBundle\Composer\VersionHelper;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

class DataHelper
{
    private const COMPOSER_PACKAGE_INTEGRATION = 'payever/payever-orocommerce';

    private const COMPOSER_PACKAGE_CMS = 'oro/commerce';

    /**
     * @var WebsiteManager
     */
    private WebsiteManager $websiteManager;

    /**
     * @var VersionHelper
     */
    private VersionHelper $versionHelper;

    /**
     * @var PackageProvider
     */
    private PackageProvider $packageProvider;

    public function __construct(
        WebsiteManager $websiteManager,
        VersionHelper $versionHelper,
        PackageProvider $packageProvider
    ) {
        $this->websiteManager = $websiteManager;
        $this->packageProvider = $packageProvider;
        $this->versionHelper = $versionHelper;
    }

    /**
     * @return Website
     */
    public function getWebsite(): Website
    {
        return $this->websiteManager->getCurrentWebsite() ?: $this->websiteManager->getDefaultWebsite();
    }

    /**
     * Retrieves integration (shop system) version.
     *
     * @return string Integration version.
     */
    public function getCmsVersion()
    {
        return $this->versionHelper->getVersion();
    }

    public function getPluginVersion()
    {
        $package = null;
        $packageInterfaces = $this->packageProvider->getThirdPartyPackages();
        if (array_key_exists(self::COMPOSER_PACKAGE_INTEGRATION, $packageInterfaces)) {
            $package = $packageInterfaces[self::COMPOSER_PACKAGE_INTEGRATION];
        }

        return $package ? $package['pretty_version'] : null;
    }
}
