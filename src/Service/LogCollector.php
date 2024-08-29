<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Payever\Bundle\PaymentBundle\Service\Factory\ZipArchiveFactory;
use ZipArchive;

class LogCollector
{
    private const PAYEVER_LOG_PREFIX = 'payever_';
    private const PAYEVER_FILE_PATTERN = 'orocommerce-logs-%s%-%s';

    /**
     * @var string
     */
    private string $logsDirectory;

    /**
     * @var string
     */
    private $zipFile;

    /**
     * @var string|null
     */
    private $zipFileName;

    /**
     * @var ZipArchiveFactory
     */
    private ZipArchiveFactory $zipArchiveFactory;

    /**
     * @var ConfigManager
     */
    private ConfigManager $configManager;

    /**
     * Constructor.
     *
     * @param string $logsDirectory
     * @param ZipArchiveFactory $zipArchiveFactory
     * @param ConfigManager $configManager
     */
    public function __construct(
        string $logsDirectory,
        ZipArchiveFactory $zipArchiveFactory,
        ConfigManager $configManager
    ) {
        $this->logsDirectory = $logsDirectory;
        $this->zipArchiveFactory = $zipArchiveFactory;
        $this->configManager = $configManager;
    }

    /**
     * Collect and zip log files.
     *
     * @param bool $includeSystemLogs
     * @return $this
     */
    public function collect(bool $includeSystemLogs): self
    {
        $this->zipFile = $this->logsDirectory . DIRECTORY_SEPARATOR . $this->getFileName();

        // Make zip
        $zipArchive = $this->zipArchiveFactory->create();
        $zipArchive->open($this->zipFile, ZipArchive::CREATE);

        // Add log files
        foreach (glob($this->logsDirectory . DIRECTORY_SEPARATOR . '*.log') as $filename) {
            if (str_contains(basename($filename), self::PAYEVER_LOG_PREFIX)) {
                $zipArchive->addFile($filename, basename($filename));
                continue;
            }

            if ($includeSystemLogs && in_array(basename($filename), ['prod.log', 'dev.log'])) {
                $zipArchive->addFile($filename, basename($filename));
            }
        }

        $zipArchive->close();

        return $this;
    }

    /**
     * Get Zip contents.
     *
     * @return string|null
     */
    public function getContents(): ?string
    {
        if ($this->zipFile && file_exists($this->zipFile)) {
            return file_get_contents($this->zipFile);
        }

        return null;
    }

    /**
     * Remove Zip file.
     *
     * @return $this
     */
    public function remove(): self
    {
        if ($this->zipFile && file_exists($this->zipFile)) {
            unlink($this->zipFile);
        }

        return $this;
    }

    /**
     * Clean up logs.
     *
     * @return $this
     */
    public function cleanLogs(): self
    {
        foreach (glob($this->logsDirectory . DIRECTORY_SEPARATOR . '*.log') as $filename) {
            if (str_contains($filename, self::PAYEVER_LOG_PREFIX)) {
                unlink($filename);
            }
        }

        return $this;
    }

    /**
     * Get File Name.
     *
     * @return string
     */
    public function getFileName(): string
    {
        if ($this->zipFileName) {
            return $this->zipFileName;
        }

        $businessUuid = $this->getBusinessUuid();
        $this->zipFileName = sprintf(
            self::PAYEVER_FILE_PATTERN,
            $businessUuid ? $businessUuid : uniqid('payever'),
            (new \DateTime())->format('Y-m-d-H-i-s'),
        ) . '.zip';

        return $this->zipFileName;
    }

    /**
     * @return string|null
     */
    private function getBusinessUuid(): ?string
    {
        return $this->configManager->get('payever_payment.business_uuid');
    }
}
