<?php

namespace Wuunder\Wuunderconnector\AutoBooking;

use Wuunder\Wuunderconnector\Api\BookingProcessorInterface;
use Wuunder\Wuunderconnector\Exception\AutomaticBookingException;

class Processor implements BookingProcessorInterface
{
    /**
     * @var \Wuunder\Wuunderconnector\Api\ShipmentConfigBuilderInterface
     */
    private $shipmentConfigBuilder;

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
        \Wuunder\Wuunderconnector\Api\ShipmentConfigBuilderInterface $shipmentConfigBuilder,
        \Wuunder\Wuunderconnector\Helper\Api $api
    ) {
        $this->wuunderShipmentRepository = $wuunderShipmentRepository;
        $this->shipmentConfigBuilder = $shipmentConfigBuilder;
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

        $config = $this->shipmentConfigBuilder->build($order);

        $connector = new \Wuunder\Connector($this->api->getApiKey(), $this->api->testMode());
        $shipment = $connector->createShipment();

        if (!$config->validate()) {
            throw new AutomaticBookingException(__("Bookingconfig not complete"));
        }
        $shipment->setConfig($config);
        if ($shipment->fire()) {
            try {
                /** @var \Wuunder\Api\ShipmentApiResponse $response */
                $response = $shipment->getShipmentResponse();
                $response->getShipmentData();
                $this->saveWuunderShipment(
                    $order->getId(),
                    $shipment->getShipmentResponse()->getBookingUrl(),
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
