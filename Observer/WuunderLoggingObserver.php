<?php

/**
 * @author Vendor
 * @copyright Copyright (c) 2019 Vendor (https://www.vendor.com/)
 */

declare(strict_types=1);

namespace Vendor\Wuunderconnector\Observer;

use Psr\Log\LoggerInterface as PsrLoggerInterface;
use Exception;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

/**
 * Class WuunderLoggingObserver
 */
class WuunderLoggingObserver implements ObserverInterface
{
    /**
     * @var PsrLoggerInterface
     */
    private $logger;

    /**
     * WuunderLoggingObserver constructor.
     *
     * @param PsrLoggerInterface $logger
     */
    public function __construct(
        PsrLoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        try {
            // some code goes here
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
