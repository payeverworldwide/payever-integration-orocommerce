<?php

namespace Payever\Bundle\PaymentBundle\Form\Entity;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class OrderClaimUpload
{
    /**
     * @var UploadedFile[]
     */
    private array $invoices;

    /**
     * @return UploadedFile[]|null
     */
    public function getInvoices(): ?array
    {
        return $this->invoices;
    }

    /**
     * @param array $invoices
     *
     * @return self
     */
    public function setInvoices(array $invoices): self
    {
        $this->invoices = $invoices;

        return $this;
    }
}
