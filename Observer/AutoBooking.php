<?php

namespace Wuunder\Wuunderconnector\Observer;

use Magento\Framework\Event\ObserverInterface;
use Wuunder\Wuunderconnector\Api\BookingProcessorInterface;

class AutoBooking implements ObserverInterface
{
    /** @var BookingProcessorInterface */
    protected $bookingProcessor;

    /**
     * @var \Magento\Framework\App\Request\DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Wuunder\Wuunderconnector\Helper\Config
     */
    protected $config;

    public function __construct(
        BookingProcessorInterface $bookingProcessor,
        \Magento\Framework\App\Request\DataPersistorInterface $dataPersistor,
        \Wuunder\Wuunderconnector\Helper\Config $config,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->bookingProcessor = $bookingProcessor;
        $this->dataPersistor = $dataPersistor;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            /** @var \Magento\Sales\Model\Order $order */
            $order = $observer->getData('order');

            if ($order->hasShipments()
                || $this->dataPersistor->get('observer_run_'.$order->getId()) === true
                || $this->config->isAutobookingEnabled() === false
            ) {
                return;
            }

            $this->dataPersistor->set('observer_run_'.$order->getId(), true);

            if ($order->getState() === $this->config->getAutobookingState()) {
                $shipment = $this->bookingProcessor->book($order);
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }
}
