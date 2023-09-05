<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service;

use ZipArchive;

class LogCollector
{
    private const PAYEVER_LOG_PREFIX = 'payever_';

    private string $logsDirectory;
    private string $zipFile;

    /**
     * @param string $logsDirectory
     */
    public function __construct(string $logsDirectory)
    {
        $this->logsDirectory = $logsDirectory;
        $this->zipFile = $this->logsDirectory . DIRECTORY_SEPARATOR . uniqid('payever_logs_') . '.zip';
    }

    /**
     * Collect and zip log files.
     *
     * @return $this
     */
    public function collect(): self
    {
        // Make zip
        $zipArchive = new ZipArchive();
        $zipArchive->open($this->zipFile, ZipArchive::CREATE);

        // Add log files
        foreach (glob($this->logsDirectory . DIRECTORY_SEPARATOR . '*.log') as $filename) {
            if (str_contains($filename, self::PAYEVER_LOG_PREFIX)) {
                $zipArchive->addFile($filename, basename($filename));
                continue;
            }

            if (in_array(basename($filename), ['prod.log', 'dev.log'])) {
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
        if (file_exists($this->zipFile)) {
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
        if (file_exists($this->zipFile)) {
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
}
