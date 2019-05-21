<?php

namespace Wuunder\Wuunderconnector\AutoBooking;

use Wuunder\Wuunderconnector\Api\BookingProcessorInterface;
use Wuunder\Wuunderconnector\Exception\AutomaticBookingException;

class Processor implements BookingProcessorInterface
{
    /**
     * @var Wuunder\Wuunderconnector\Api\BookingConfigBuilderInterface
     */
    private $bookingConfigBuilder;

    /**
     * @var \Wuunder\Wuunderconnector\Helper\Api
     */
    private $api;

    /**
     * @var \Wuunder\Wuunderconnector\Model\WuunderShipmentRepository
     */
    private $wuunderShipmentRepository;
    
    public function __construct(
        \Wuunder\Wuunderconnector\Api\WuunderShipmentRepositoryInterface $wuunderShipmentRepository,
        \Wuunder\Wuunderconnector\Api\BookingConfigBuilderInterface $bookingConfigBuilder,
        \Wuunder\Wuunderconnector\Helper\Api $api
    ) {
        $this->wuunderShipmentRepository = $wuunderShipmentRepository;
        $this->bookingConfigBuilder = $bookingConfigBuilder;
        $this->api = $api;
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @throws AutomaticBookingException
     */
    public function book(\Magento\Sales\Api\Data\OrderInterface $order): void
    {
        if ($order->canShip() !== true) {
            throw new AutomaticBookingException(
                __("Shipment can't be created or has already been shipped.")
            );
        }

        if ($this->alreadyBooked($order->getId())) {
            throw new AutomaticBookingException(
                __("Shipment already booked")
            );
        }

        $config = $this->bookingConfigBuilder->build($order);

        $connector = new \Wuunder\Connector($this->api->getApiKey(), $this->api->testMode());
        $booking = $connector->createBooking();

        if (!$config->validate()) {
            throw new AutomaticBookingException(__("Bookingconfig not complete"));
        }
        $booking->setConfig($config);
        if ($booking->fire()) {
            try {
                $this->saveWuunderShipment(
                    $order->getId(),
                    $booking->getBookingResponse()->getBookingUrl(),
                    "testtoken"
                );
            } catch (
                \Magento\Framework\Exception\AlreadyExistsException |
                \Magento\Framework\Exception\NoSuchEntityException $e
            ) {
                throw new AutomaticBookingException(__($e->getMessage()));
            }
        } else {
            throw new AutomaticBookingException(__($booking->getBookingResponse()->getError()));
        }
    }

    /**
     * @param int    $orderId
     * @param string $bookingUrl
     * @param string $bookingToken
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function saveWuunderShipment(int $orderId, string $bookingUrl, string $bookingToken): void
    {
        $wuunderShipment = $this->wuunderShipmentRepository->getByOrderId($orderId);
        $wuunderShipment->setOrderId($orderId);
        $wuunderShipment->setBookingUrl($bookingUrl);
        $wuunderShipment->setBookingToken($bookingToken);

        $this->wuunderShipmentRepository->save(
            $wuunderShipment
        );
    }

    /**
     * @return bool
     */
    private function alreadyBooked(int $orderId): bool
    {
        $booking = $this->wuunderShipmentRepository->getByOrderId($orderId);

        return $booking->getShipmentId() ? true : false;
    }
}
