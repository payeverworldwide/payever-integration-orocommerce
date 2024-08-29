<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Payment;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Payever\Bundle\PaymentBundle\Entity\OrderInvoice;
use Payever\Bundle\PaymentBundle\Entity\Repository\OrderInvoiceRepository;
use Payever\Bundle\PaymentBundle\Service\Generator\InvoiceGenerator;

class InvoiceService
{
    const INVOICE_PLACEHOLDER = 1001;

    const INVOICE_NUMBER = 'number';
    const INVOICE_DATE = 'date';
    const INVOICE_COMMENT = 'comment';
    const INVOICE_SEND = 'send';
    const INVOICE_PAYMENT_ID = 'payment_id';
    const INVOICE_EXTERNAL_ID = 'external_id';

    /**
     * @var InvoiceGenerator
     */
    private InvoiceGenerator $invoicePdfGenerator;

    /**
     * @var FileManager
     */
    private FileManager $fileManager;

    /**
     * @var ObjectManager
     */
    private ObjectManager $manager;

    /**
     * @param InvoiceGenerator $invoicePdfGenerator
     * @param FileManager $fileManager
     * @param ObjectManager $manager
     */
    public function __construct(InvoiceGenerator $invoicePdfGenerator, FileManager $fileManager, ObjectManager $manager)
    {
        $this->invoicePdfGenerator = $invoicePdfGenerator;
        $this->fileManager = $fileManager;
        $this->manager = $manager;
    }

    /**
     * @param Order $order
     * @param array $params
     *
     * @return Attachment
     */
    public function createInvoice(Order $order, array $params = []): Attachment
    {
        $attachment = $this->generateNewInvoice($order, $params);

        $this->createOrderInvoice($order, $attachment, $params);

        return $attachment;
    }

    /**
     * Prefill invoice number in the form
     *
     * @return int
     */
    public function getNextOrderInvoiceNumber(): int
    {
        /**@var OrderInvoiceRepository $repo */
        $repo = $this->manager->getRepository(OrderInvoice::class);

        return $repo->getLastInvoiceNumber() + self::INVOICE_PLACEHOLDER;
    }

    /**
     * Prefill invoice date in the form
     *
     * @return \DateTime
     */
    public function getOrderInvoiceDate(): \DateTime
    {
        return new \DateTime();
    }

    /**
     * @param string $externalId
     *
     * @return OrderInvoice[]
     */
    public function getInvoicesByExternalId(string $externalId): array
    {
        $repo = $this->manager->getRepository(OrderInvoice::class);

        return $repo->findBy(['externalId' => $externalId]);
    }

    /**
     * @param int $attachmentId
     * @return Attachment|null
     */
    public function getAttachmentById(int $attachmentId): ?Attachment
    {
        return $this->manager->getRepository(Attachment::class)->find($attachmentId);
    }

    /**
     * @param Attachment $attachment
     * @return Attachment|null
     */
    public function getInvoiceByAttachmentFile(Attachment $attachment): ?string
    {
        return $this->fileManager->getContent($attachment->getFile());
    }

    /**
     * Create a new PDF invoice for an order.
     *
     * @param Order $order
     * @param array $params
     *
     * @return Attachment
     */
    private function generateNewInvoice(Order $order, array $params = []): Attachment
    {
        $file = $this->createInvoiceFile($order, $params);
        $attachment = $this->createOroAttachment($order, $file, $params);

        $this->manager->persist($file);
        $this->manager->persist($attachment);
        $this->manager->flush();

        return $attachment;
    }

    /**
     * @param Order $order
     * @param array $params
     *
     * @return File
     */
    private function createInvoiceFile(Order $order, array $params = []): File
    {
        $number = $params[self::INVOICE_NUMBER] ?? $this->getNextOrderInvoiceNumber();
        $date = $params[self::INVOICE_DATE] ?? $this->getOrderInvoiceDate();
        $comment = $params[self::INVOICE_COMMENT] ?? '';

        $invoicePdfContent = $this->invoicePdfGenerator->generate($order, $number, $date, $comment);

        $file = $this->fileManager->writeToTemporaryFile(
            $invoicePdfContent,
            sprintf('invoice_%s.pdf', $number)
        );

        return $this->fileManager->createFileEntity($file->getPathname());
    }

    /**
     * @param Order $order
     * @param File $file
     * @param array $params
     *
     * @return Attachment
     */
    private function createOroAttachment(Order $order, File $file, array $params = []): Attachment
    {
        $comment = $params[self::INVOICE_COMMENT] ?? '';

        $attachment = new Attachment();
        $attachment->setFile($file);
        $attachment->setComment($comment);
        $attachment->setTarget($order);

        return $attachment;
    }

    /**
     * @param Order $order
     * @param Attachment $attachment
     * @param array $params
     * @return void
     */
    private function createOrderInvoice(Order $order, Attachment $attachment, array $params): void
    {
        $invoice = new OrderInvoice();
        $invoice->setOrderId($order->getId());
        $invoice->setAttachmentId($attachment->getId());
        $invoice->setPaymentId($params[self::INVOICE_PAYMENT_ID]);
        $invoice->setExternalId($params[self::INVOICE_EXTERNAL_ID]);

        $this->manager->persist($invoice);
        $this->manager->flush();
    }
}
